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
use http\Env\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
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

    public function mailNotice($title, $body, $users){
        foreach ($users as $user){
            try{
                $this->out->writeln('User email: '.$user->email);
                $email = Mail::to(trim($user->email))
                    ->send(new BroadcastNotice($title, $body, $user->first_name, $user->last_name));
                $this->out->writeln('Email confirm: '.$email);
            }catch (\Exception $e){
                $this->out->writeln("Error: Unable to email notice $user->email, exception: $e");
                continue;
            }
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
        $user_id=Auth::id();
        $data = [
            'title'=>$input['title'],
            'body'=>$input['body'],
            'type'=>$this->type['notice'],
            'group'=>$input['group'],
            'broadcast_to'=>$input['broadcast_to'],
            'broadcast_by'=>$user_id,
        ];
        try{
            if($input['group']==0){
                $all_profiles = UserProfile::where('institute_id','=',$input['broadcast_to'])->get();
            }else if($input['group']==1){
                $round = QuestionSet::selec('round_id')->where('round_id','=',$input['broadcast_to'])->first();
                $all_profiles = RoundCandidates::with(['user_profiles'])->where('round_id',$round->round_id)->get('email');
            }else if($input['group']==2){
                $all_profiles = RoundCandidates::with(['user_profiles'])->where('round_id',$input['broadcast_to'])->get('email');
            }else{
                throw new \Exception('No User Found to Broadcast!');
            }
            $broadcast = Broadcast::create($data);
            $this->mailNotice($input['title'],$input['body'], $all_profiles);
            return response()->json(['success'=>true, 'broadcast'=>$broadcast],$this->successStatus);
        }catch (\Exception $e){
            return response()->json(['success'=>false, 'message'=>"Broadcasting Notice Unsuccessful!", 'error'=>$e->getMessage()], $this->failedStatus);
        }
    }

    /**
     * student ranking based on the mark and time-taken during exam
     * @param $question_answers
     * @return $question_answers
     */
    public function studentRank($total_mark, $question_answers){
        $position = 1;
        for($i=0;$i<sizeof($question_answers)-1;$i++){
            for($j=$i+1;$j<sizeof($question_answers);$j++){
                if($question_answers[$i]->total_mark<$question_answers[$j]->total_mark){
                    $temp = $question_answers[$i];
                    $question_answers[$i]=$question_answers[$j];
                    $question_answers[$j]=$temp;
                }else if($question_answers[$i]->total_mark==$question_answers[$j]->total_mark  && $question_answers[$i]->time_taken>$question_answers[$j]->time_taken){
                    $this->out->writeln('swap by time');
                    $temp = $question_answers[$i];
                    $question_answers[$i]=$question_answers[$j];
                    $question_answers[$j]=$temp;
                }
            }
            $this->out->writeln('Student rank list: '.$i);
            $achieved_mark = $question_answers[$i]->total_mark;
            $percentage= ($achieved_mark*100)/$total_mark;
            $question_answers[$i]['rank']=$i+1;
            $question_answers[$i]['percentage']=$percentage;
            if($i>0 && $question_answers[$i-1]->total_mark==$question_answers[$i]->total_mark && $question_answers[$i-1]->time_taken==$question_answers[$i]->time_taken){
                $this->out->writeln('Position must be same!');
                $question_answers[$i]['position']=$position-1;
            }else{
                $question_answers[$i]['position']=$position++;
            }
        }
        $question_answers[$i]['rank']=$i+1;
        if($question_answers[$i-1]->total_mark==$question_answers[$i]->total_mark && $question_answers[$i-1]->time_taken==$question_answers[$i]->time_taken){
            $question_answers[$i]['position']=$position-1;
        }else{
            $question_answers[$i]['position']=$position++;
        }
        foreach ($question_answers as $qs){
            $this->out->writeln('question set ans id: '.$qs->id);
        }
        return $question_answers;
    }
    /**
     * Broadcasting result
     * @param $question_answers
     * @return $question_answers
     */
    public function resultEmail($question_set, $question_set_answers , $institute_name){
        $email_info=[
            'question_set_title'=>trim($question_set->title),
            'start_time'=>trim($question_set->start_time),
            'end_time'=>trim($question_set->end_time),
            'assessment_time'=>trim($question_set->assessment_time),
            'total_mark'=>trim($question_set->total_mark),
            'number_of_participation'=>sizeof($question_set_answers),
            'institute_name'=>trim($institute_name),
        ];
        foreach ($question_set_answers as $participant){
            $this->out->writeln(
                'user result with email'
            );
            $email_info ['user_email']= trim($participant->user_profile->email);
            $email_info['time_taken'] = trim($participant->time_taken);
            $email_info['marks']=trim($participant->total_mark);
            $email_info['first_name']=trim($participant->user_profile->first_name);
            $email_info['last_name']=trim($participant->user_profile->last_name);
            $email_info['percentage']=trim($participant->percentage);
            $email_info['position']=trim($participant->position);
//            dd($email_info);
//            $this->out->writeln('Email info: '.$email_info);
            $email = Mail::to($email_info['user_email'])
                ->send(new BroadcastResult($email_info));

        }
    }

    public function broadcastResult(Request $request){
        $this->out->writeln('Broadcasting result according to the Assessment-id');
        request()->validate([
            'institute_id'=>'required',
            'question_set_id'=>'required',
        ]);
        $input = $request->all();
        $user = Auth::id();
        $institution = Institute::find($input['institute_id']);
        $data = [
            'title'=>'Assessment Result',
            'body'=>'Assessment Result',
            'type'=>$this->type['result'],
            'group'=>$this->group['question_set'],
            'broadcast_to'=>$input['question_set_id'],
            'broadcast_by'=>$user,
        ];
        if(!QuestionSetAnswer::where('question_set_id','=',$input['question_set_id'])->exists()){
            return response()->json(['success'=>false, 'message'=>'No student Participated on this Assessment!'], $this->invalidStatus);
        }
        $broadcast = Broadcast::create($data);
        if($broadcast){
            $this->out->writeln('Emailing result, Broadcast: '.$broadcast);
            $question_set = QuestionSet::find($input['question_set_id']);
            $question_set_answer = QuestionSetAnswer::with(['user_profile'])->where('question_set_id','=',$input['question_set_id'])->get();
            if(sizeof($question_set_answer)==1){
                $mark_achieved = $question_set_answer[0]->total_mark;
                $total_mark = $question_set->total_mark;
                $mark_percentage = ($mark_achieved/$total_mark)*100;
                $question_set_answer[0]['rank']=1;
                $question_set_answer[0]['position']=1;
                $question_set_answer[0]['percentage']=$mark_percentage;
                $this->resultEmail($question_set, $question_set_answer, $institution->name);
                return response()->json(['success' => true, 'broadcast'=>$broadcast , 'question_set_answer' => $question_set_answer], $this->successStatus);
            }
            $sorted_result = $this->studentRank($question_set->total_mark, $question_set_answer);
            $this->resultEmail($question_set, $sorted_result, $institution->name);
            return response()->json(['success'=>true, 'broadcast'=>$broadcast, 'question_set_answer'=>$question_set_answer], $this->successStatus);
        }
        return response()->json(['success'=>false, 'message'=>'Unable to broadcast Result'], $this->failedStatus);
    }

    public function certificateEmail($question_set, $question_set_answers){

        $email_info=[
            'question_set_title'=>$question_set->title,
        ];
        foreach ($question_set_answers as $participant){
            $this->out->writeln('user certificate email');
            $email_info ['user_email']= trim($participant->user_profile->email);
            $email_info['first_name']=$participant->user_profile->first_name;
            $email_info['last_name']=$participant->user_profile->last_name;
            $data=[
                'name'=>$email_info['first_name'].' '.$email_info['last_name'],
            ];
            $pdf = PDF::loadView('assessment.certificate', $data)->setPaper('a4', 'landscape');
            Storage::put('certificate/1.pdf', $pdf->output());

            mail::to($email_info['user_email'])
                ->send(new BroadcastCertificate($email_info));
        }

    }

    public function broadcastCertificate(Request $request){
        $this->out->writeln('Broadcasting Certificate according to the Assessment-id');
        request()->validate([
            'institute_id'=>'required',
            'question_set_id'=>'required',
        ]);
        $input = $request->all();
        $user = Auth::id();
        $data = [
            'title'=>'Assessment Result',
            'body'=>'Assessment Result',
            'type'=>$this->type['result'],
            'group'=>$this->group['question_set'],
            'broadcast_to'=>$input['question_set_id'],
            'broadcast_by'=>$user,
        ];
        if(!QuestionSetAnswer::where('question_set_id','=',$input['question_set_id'])->exists()){
            return response()->json(['success'=>false, 'message'=>'No student participated on this Assessment!'], $this->invalidStatus);
        }
        $broadcast = Broadcast::create($data);
        if($broadcast){
            $this->out->writeln('Emailing result, Broadcast: '.$broadcast);
            $question_set = QuestionSet::find($input['question_set_id']);
            $question_set_answer = QuestionSetAnswer::with(['user_profile'])->where('question_set_id','=',$input['question_set_id'])->get();
            $this->certificateEmail($question_set, $question_set_answer);
            return response()->json(['success'=>true, 'broadcast'=>$broadcast, 'question_set_answer'=>$question_set_answer], $this->successStatus);
        }
        return response()->json(['success'=>false, 'message'=>'Unable to broadcast Result'], $this->failedStatus);
    }
}

