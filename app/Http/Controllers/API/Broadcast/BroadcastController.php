<?php

namespace App\Http\Controllers\API\Broadcast;

use App\Broadcast;
use App\Http\Controllers\Controller;
use App\Institute;
use App\Mail\BroadcastCertificate;
use App\Mail\BroadcastNotice;
use App\Mail\BroadcastResult;
use App\Mail\WelcomeMail;
use App\QuestionSet;
use App\QuestionSetAnswer;
use App\Round;
use App\RoundCandidates;
use App\User;
use App\UserProfile;
use Carbon\Carbon;
use GuzzleHttp\Client;
use http\Env\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use \Illuminate\Http\JsonResponse;
use mysql_xdevapi\Exception;
use PDF;
class BroadcastController extends Controller
{

    protected $type = [
        'notice'=>0,
        'result'=>1,
        'certificate'=>2,
    ];
    protected $group=[
        'institute'=>0,
        'question_set'=>1,
        'round'=>2,
    ];

    public $successStatus = 200;
    public $failedStatus = 500;
    public $invalidStatus = 400;

    public $out;
    function __construct(){
        $this->out = new \Symfony\Component\Console\Output\ConsoleOutput();
    }

    public function index(){
        try{
            $user = Auth::user();
            if($user->can('super-admin')){
                $broadcasts = Broadcast::all();
                return response()->json(['success'=>true, 'broadcast'=>$broadcasts],$this->successStatus);
            }
            if($user->institute_id){
                $broadcasts = Broadcast::where('institute_id','=',$user->institute_id);
                return response()->json(['success'=>true, 'broadcast'=>$broadcasts], $this->successStatus);
            }
        }catch (\Exception $e){
            return response()->json(['success'=>false, 'message'=>"Unable to fetch Broadcast lists!", 'error'=> $e->getMessage()], $this->failedStatus);
        }
    }

    public function mailNotice($title, $body, $users){
        foreach ($users as $user){
            try{
                $this->out->writeln('User email: '.$user->user_profile->email);
                $email = Mail::to(trim($user->user_profile->email))
                    ->send(new BroadcastNotice($title, $body, $user->user_profile->first_name, $user->user_profile->last_name));
                $this->out->writeln('Email confirm: '.$email);
            }catch (\Exception $e){
                $this->out->writeln("Error: Unable to email notice $user->user_profile->email, exception: $e");
                continue;
            }
        }
    }

    public function mailNoticeFromServer($title, $body, $profiles){
        try {
            $this->out->writeln("Requesting email server for Bulk-email..");
            $delay = env("EMAIL_SERVER_JOB_DELAY");
            $url = env("EMAIL_SERVER_URL").'assessment-info';
            $client = new Client();
            $institute = Institute::find(Auth::user()->institute_id);
            $body = [
                "title"=>$title,
                "body"=>$body,
                "profiles"=>$profiles->toArray(),
                "delay"=>$delay,
                "institute"=>$institute->name,
            ];
            $response = $client->post($url, ["form_params"=>$body, 'http_errors' => false]);
            $this->out->writeln("Url: $url");
            if($response->getStatusCode()!=200)
                throw new \Exception("Mail server is not responding, its may be down or something else! error: ".$response->getBody());
            return true;
        }catch(\Exception $e){
            $this->out->writeln("Unable to mail notice form email-server! error: ".$e->getMessage());
            return false;
        }
    }

    public function store(Request $request){
        $this->out->writeln('Broadcasting a notice to everyone');
        request()->validate([
           'title'=>'required',
           'body'=>'required',
            'institute_id'=>'required',
            'broadcast_to'=>'required',
            'group'=>'required'
        ]);
        $input = $request->all();
        $user=Auth::user();
        $data = [
            'title'=>$input['title'],
            'body'=>$input['body'],
            'type'=>$this->type['notice'],
            'group'=>$input['group'],
            'broadcast_to'=>$input['broadcast_to'],
            'broadcast_by'=>$user->id,
            'institute_id'=>$user->institute_id,
        ];
        try{
            if($input['group']==0){  // Everyone under the given institution.
                if(!Institute::where('id',$input['broadcast_to'])->exists())
                    throw new \Exception("Institution not Found!");
                $all_profiles = User::with('user_profile')->where('institute_id','=',$input['broadcast_to'])->get();
            }else if($input['group']==1){ // Everyone Under given Assessment.
                $round = QuestionSet::select('round_id')->where('round_id','=',$input['broadcast_to'])->first();
                if(!$round)
                    throw new \Exception("Question-Set Not Found!");
                $all_profiles = RoundCandidates::with(['user_profile'])->where('round_id',$round->round_id)->get();
            }else if($input['group']==2){   // Everyone Under one round
                if(!Round::where('id',$input['broadcast_to'])->exists())
                    throw new \Exception("Round Not Found!");
                $all_profiles = RoundCandidates::with(['user_profile'])->where('round_id',$input['broadcast_to'])->get();
            }else{
                throw new \Exception('No User Found to Broadcast!');
            }
            $broadcast = Broadcast::create($data);
//            return $all_profiles;
            $this->mailNoticeFromServer($input['title'],$input['body'], $all_profiles);
            return response()->json(['success'=>true, 'broadcast'=>$broadcast],$this->successStatus);
        }catch (\Exception $e){
            return response()->json(['success'=>false, 'message'=>"Broadcasting Notice Unsuccessful!", 'error'=>$e->getMessage()], $this->failedStatus);
        }
    }

    /**
     * Broadcasting result
     * @param $question_answers
     * @return $question_answers
     */
    public function resultEmail($question_set, $question_set_answers , $institute_name){
        try{
            $st_time = Carbon::parse($question_set->start_time);
            $end_time = Carbon::parse($question_set->end_time);
            $email_info=[
                'question_set_title'=>trim($question_set->title),
                'start_time'=>trim($st_time->toDayDateTimeString()),
                'end_time'=>trim($end_time->toDayDateTimeString()),
                'assessment_time'=>trim($question_set->assessment_time),
                'total_mark'=>trim($question_set->total_mark),
                'number_of_participation'=>sizeof($question_set_answers),
                'institute_name'=>trim($institute_name),
                'total_time'=>trim($question_set->assessment_time),
            ];
            $total_student=sizeof($question_set_answers);
            $total_mark = $question_set->total_mark;
            for($rank=0, $position=0; $rank<$total_student;$rank++){
                $mark_achieved = $question_set_answers[$rank]['total_mark'];
                $email_info ['user_email']= trim($question_set_answers[$rank]['user_profile']['email']);
                $email_info['time_taken'] = trim($question_set_answers[$rank]['time_taken']);
                $email_info['marks']= trim($question_set_answers[$rank]['total_mark']);
                $email_info['first_name']= trim($question_set_answers[$rank]['user_profile']['first_name']);
                $email_info['last_name']= trim($question_set_answers[$rank]['user_profile']['last_name']);
                $email_info['percentage']= ($mark_achieved/$total_mark)*100;
                $email_info['position']=$position+1;
                try{
                    $email = Mail::to($email_info['user_email'])->send(new BroadcastResult($email_info));
                }catch(\Exception $e){
                    $this->out->writeln("Mailing to \"".$email_info['user_email']."\" Unsuccessful! error: ".$e->getMessage());
                }
                if($rank+1<$total_student && $question_set_answers[$rank]['total_mark'] ==$question_set_answers[$rank+1]['total_mark'] && $question_set_answers[$rank]['time_taken']==$question_set_answers[$rank+1]['time_taken'])
                    continue;
                $position++;
            }
            return true;
        }catch(\Exception $e){
            return response()->json(['success'=>false, "message"=>"Email result unsuccessful!", "error"=>$e->getMessage()]);
        }
    }

    public function resultEmailFromServer($question_set, $question_set_answers, $institute_name)
    {
        try{
            $this->out->writeln("Requesting email server for Bulk-email..");
            $delay = env("EMAIL_SERVER_JOB_DELAY");
            $url = env("EMAIL_SERVER_URL").'students-result';
            $client = new Client();
            $body = [
                "question_set"=>$question_set->toArray(),
                "question_set_answers"=>$question_set_answers->toArray(),
                "institute_name"=>$institute_name,
                "delay"=>$delay,
            ];
            $response = $client->post($url, ["form_params"=>$body, 'http_errors' => false]);
            $this->out->writeln("Url: $url");
            if($response->getStatusCode()!=200)
                throw new \Exception("Mail server is not responding, its may be down or something else!");
            return true;
        }catch(\Exception $e){
            $this->out->writeln("Unable to send-credential to user! error: ".$e->getMessage());
            return false;
        }
    }

    public function broadcastResult(Request $request): JsonResponse
    {
        $this->out->writeln('Broadcasting result according to the Assessment-id');
        try{
            request()->validate([
                'institute_id'=>'required',
                'question_set_id'=>'required',
            ]);
            $input = $request->all();
            $user = Auth::user();
            $institution = Institute::find($input['institute_id']);
            $data = [
                'title'=>'Assessment Result',
                'body'=>'Assessment Result',
                'type'=>$this->type['result'],
                'group'=>$this->group['question_set'],
                'broadcast_to'=>$input['question_set_id'],
                'broadcast_by'=>$user->id,
                'institute_id'=>$user->institute_id,
            ];
            if(!QuestionSetAnswer::where('question_set_id','=',$input['question_set_id'])->exists()){
                return response()->json(['success'=>false, 'message'=>'No student Participated on this Assessment!'], $this->invalidStatus);
            }
            $broadcast = Broadcast::create($data);
            if(!$broadcast)
                throw new Exception("Broadcast Insertion Failed!");
            $this->out->writeln('Emailing result, Broadcast: '.$broadcast);
            $question_set = QuestionSet::where('id',$input['question_set_id'])->first();
            $question_set_answer = QuestionSetAnswer::with(['user_profile'])->where('question_set_id','=',$input['question_set_id'])->get();
            if(sizeof($question_set_answer)==1){
                $mark_achieved = $question_set_answer[0]->total_mark;
                $total_mark = $question_set->total_mark;
                $mark_percentage = ($mark_achieved/$total_mark)*100;
                $question_set_answer[0]['rank']=1;
                $question_set_answer[0]['position']=1;
                $question_set_answer[0]['percentage']=$mark_percentage;
                if(!$this->resultEmailFromServer($question_set, $question_set_answer, $institution->name))
                    throw new \Exception("Emailing Result Unsuccessful!");
                return response()->json(['success' => true, 'broadcast'=>$broadcast , 'question_set_answer' => $question_set_answer], $this->successStatus);
            }
//            $question_set_answer =json_decode($question_set_answer, true);
//            usort($question_set_answer, function($student1, $student2){
//                return ($student1['total_mark'] < $student2['total_mark']) || ($student1['total_mark'] == $student2['total_mark'] && $student1['time_taken'] > $student2['time_taken']);
//            });
            $response = $this->resultEmailFromServer($question_set, $question_set_answer, $institution->name);
            return response()->json(['success'=>true, 'broadcast'=>$broadcast, 'question_set_answer'=>$question_set_answer, "response"=>$response], $this->successStatus);
        }catch(\Exception $e){
            return response()->json(['success'=>false, "message"=>"Broadcasting Result Failed!", "error"=>$e->getMessage()], $this->failedStatus);
        }
    }

    public function certificateEmail($question_set, $question_set_answers){
        try{
            $email_info=[
                'question_set_title'=>$question_set->title,
            ];
            foreach ($question_set_answers as $participant){
                $email_info ['user_email']= trim($participant->user_profile->email);
                $email_info['first_name']=$participant->user_profile->first_name;
                $email_info['last_name']=$participant->user_profile->last_name;
                $data=[
                    'name'=>$email_info['first_name'].' '.$email_info['last_name'],
                ];
                $pdf = PDF::loadView('assessment.certificate', $data)->setPaper('a4', 'landscape');
                Storage::put('certificate/1.pdf', $pdf->output());
                try{
                    mail::to($email_info['user_email'])->send(new BroadcastCertificate($email_info));
                }catch (\Exception $e){
                    $this->out->writeln("Mailing ".$email_info["email"]." Unsuccessful! error: ".$e->getMessage());
                }
            }
            return true;
        }catch(\Exception $e){
            $this->out->writeln("Emailing Certificate unsuccessful!".$e->getMessage());
            return response()->json(['success'=>false, "message"=>"Emailing Certificate unsuccessful!", "error"=>$e->getMessage()],$this->failedStatus);
        }
    }

    public function certificateEmailFromServer($question_set, $question_set_answers)
    {
        try{
            $this->out->writeln("Requesting email server for Bulk-email..");
            $delay = env("EMAIL_SERVER_JOB_DELAY");
            $url = env("EMAIL_SERVER_URL").'assessment-certificate';
            $client = new Client();
            $institute = Institute::find(Auth::user()->institute_id);
            $body = [
                "question_set"=>$question_set->toArray(),
                "question_set_answers"=>$question_set_answers->toArray(),
                "delay"=>$delay,
                "institute"=>$institute->name,
            ];
//            $this->out->writeln($question_set_answers);
            $response = $client->post($url, ["form_params"=>$body, 'http_errors' => false]);
            $this->out->writeln("Url: $url");
            if($response->getStatusCode()!=200)
                throw new \Exception("Mail server is not responding, its may be down or something else!");
            return true;
        }catch(\Exception $e){
            $this->out->writeln("Unable to send-credential to user! error: ".$e->getMessage());
            return false;
        }
    }

    public function broadcastCertificate(Request $request){
        try{
            request()->validate([
                'institute_id'=>'required',
                'question_set_id'=>'required',
            ]);
            $input = $request->all();
            $user = Auth::user();
            $data = [
                'title'=>'Assessment Result',
                'body'=>'Assessment Result',
                'type'=>$this->type['result'],
                'group'=>$this->group['question_set'],
                'broadcast_to'=>$input['question_set_id'],
                'broadcast_by'=>$user->id,
                'institute_id'=>$user->institute_id,
            ];
            if(!QuestionSetAnswer::where('question_set_id','=',$input['question_set_id'])->exists())
                return response()->json(['success'=>false, 'message'=>'No student participated on this Assessment!'], $this->invalidStatus);
            $broadcast = Broadcast::create($data);
            if(!$broadcast)
                throw new \Exception("Broadcast insertion failed!");
            $question_set = QuestionSet::find($input['question_set_id']);
            if(!$question_set)
                throw new \Exception("Question-set Not Found");
            $question_set_answer = QuestionSetAnswer::with(['user_profile'])->where('question_set_id','=',$input['question_set_id'])->get();
            if(!$question_set_answer)
                throw new \Exception("Question-Set-Answer Not Found");
            $this->certificateEmailFromServer($question_set, $question_set_answer);
            return response()->json(['success'=>true, 'broadcast'=>$broadcast, 'question_set_answer'=>$question_set_answer], $this->successStatus);
        }catch(\Exception $e){
            return response()->json(['success'=>false, "message"=>"Broadcasting Certification is unsuccessful!", "error"=>$e->getMessage()], $this->failedStatus);
        }
    }
}


