<?php

namespace App\Http\Controllers\API\Broadcast;

use App\Broadcast;
use App\Http\Controllers\Controller;
use App\Mail\BroadcastCertificate;
use App\Mail\BroadcastNotice;
use App\Mail\BroadcastResult;
use App\Mail\WelcomeMail;
use App\QuestionSet;
use App\QuestionSetAnswer;
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
        $email_confirm ='';
        $email_failed='';
        foreach ($users as $user){
            $this->out->writeln('User email: '.$user->email);
            $email = Mail::to(trim($user->email))
                ->send(new BroadcastNotice($title, $body, $user->first_name, $user->last_name));
            $this->out->writeln('Email confirm: '.$email);
        }
    }

    public function store(Request $request){
        $this->out->writeln('Broadcasting a notice to everyone');
        request()->validate([
           'title'=>'required',
           'body'=>'required',
            'institute_id'=>'required',
            'broadcast_to'=>'required',
        ]);
        $input = $request->all();
        $user_id=Auth::id();
        $data = [
            'title'=>$input['title'],
            'body'=>$input['body'],
            'type'=>$this->type['notice'],
            'group'=>$this->group['institute'],
            'broadcast_to'=>$input['broadcast_to'],
            'broadcast_by'=>$user_id,
        ];
        $broadcast = Broadcast::create($data);
        if($broadcast){
            $this->out->writeln('Message broadcast successful');
            $all_profiles = UserProfile::where('institute_id','=',$input['institute_id'])->get();
//            dd($all_profiles);
            $this->mailNotice($input['title'],$input['body'], $all_profiles);
            return response()->json(['success'=>true, 'broadcast'=>$broadcast],$this->successStatus);
        }
        return reponse()->json(['success'=>false, 'message'=>'Message broadcasting failed!'], $this->failedStatus);
    }

    public function resultEmail($question_set, $question_set_answers){
        $email_info=[
            'question_set_title'=>$question_set->title,
            'start_time'=>$question_set->start_time,
            'end_time'=>$question_set->end_time,
            'assessment_time'=>$question_set->assessment_time,
        ];
        foreach ($question_set_answers as $participant){
            $this->out->writeln(
                'user result with email'
            );
            $email_info ['user_email']= trim($participant->user_profile->email);
            $email_info['time_taken'] = $participant->time_taken;
            $email_info['marks']=$participant->total_mark;
            $email_info['first_name']=$participant->user_profile->first_name;
            $email_info['last_name']=$participant->user_profile->last_name;
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
        $data = [
            'title'=>'Assessment Result',
            'body'=>'Assessment Result',
            'type'=>$this->type['result'],
            'group'=>$this->group['question_set'],
            'broadcast_to'=>$input['question_set_id'],
            'broadcast_by'=>$user,
        ];
        $broadcast = Broadcast::create($data);
        if($broadcast){
            $this->out->writeln('Emailing result, Broadcast: '.$broadcast);
            $question_set = QuestionSet::find($input['question_set_id']);
            $question_set_answer = QuestionSetAnswer::with(['user_profile'])->where('question_set_id','=',$input['question_set_id'])->get();
            $this->resultEmail($question_set, $question_set_answer);
            return response()->json(['success'=>true, 'broadcast'=>$broadcast, 'question_set_answer'=>$question_set_answer], $this->successStatus);
        }
        return response()->json(['success'=>false, 'message'=>'Unable to broadcast Result'], $this->failedStatus);
    }

    public function certificateEmail($question_set, $question_set_answers){

        $email_info=[
            'question_set_title'=>$question_set->title,
        ];
        foreach ($question_set_answers as $participant){
            $this->out->writeln(
                'user certificate email'
            );
            $email_info ['user_email']= trim($participant->user_profile->email);
            $email_info['first_name']=$participant->user_profile->first_name;
            $email_info['last_name']=$participant->user_profile->last_name;
            $data=[
                'name'=>$email_info['first_name'].' '.$email_info['last_name'],
            ];
            $pdf = PDF::loadView('assessment.certificate', $data)->setPaper('a4', 'landscape');
            Storage::put('certificate/1.pdf', $pdf->output());

            mail::to($email_info['user_email'])
                ->cc('hemayet.nirjhoy@icloud.com')
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

