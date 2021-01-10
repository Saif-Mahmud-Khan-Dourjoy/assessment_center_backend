<?php

namespace App\Http\Controllers\API\Broadcast;

use App\Broadcast;
use App\Http\Controllers\Controller;
use App\Mail\BroadcastNotice;
use App\UserProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

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

}

