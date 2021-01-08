<?php

namespace App\Http\Controllers\API\Broadcast;

use App\Http\Controllers\Controller;
use App\UserProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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

    public function store(Request $request){
        request()->validate([
            'type'=>'required',
            'broadcast_group'=>'required',
            'title'=>'required',
            'broadcast_to'=>'required',

        ]);
        $input = $request->all();
        $user = Auth::id();
        $user_profile = UserProfile::where('user_id','=',$user)->first();
        $data = [
            'title'=>$input['title'],
            'type'=>$input['type'],
            'broadcast_group'=>$input['broadcast_group'],
            'broadcast_to'=>$input['broadcast_to'],
        ];
//        if($input['type']==1){                                                                  // Broadcast  Result
//            if($input['broadcast_group'==1]){                                                                  // Broadcast to certain group student under question-set
//                // todo for broadcasting any result to student under specific round
//            }
//            if($input['broadcast_group'==1]){                                                                      // Broadcast to certain group student under specific round
//                //todo for sending notice result under institution
//            }
//        }
//        if($input['type']==2){                                                                      // Broadcast Certificate
//            if($input['broadcast_group'==1]){                                                                   // Broadcast to certain group student under question-set
//                // todo for broadcasting any notice to student under specific round
//            }
//            if($input['broadcast_group'==2]){                                                                       // Broadcast to certain group student under specific round
//                //todo for sending notice everyone under institution
//            }
//        }
        if($input['type']==0){                                                                       // Broadcast  Notice
            $data['body']=$input['body'];
            if($input['broadcast_group'==1]){                                                                      // Broadcast to certain group student under specific round
                // todo for broadcasting any notice to student under specific round
            }
            if($input['broadcast_group']==2){                                                                     // Broadcast to certain group student under specific round
                //todo for sending notice everyone under institution
            }
            if($input['broadcast_group']==0){                                                                      // Broadcast to everyone under this institute
                $data['broadcast_to']=$user_profile->institute_id;

                //todo for broadcasting notice under any specific question-set
            }
        }

    }


    public function result_publish(Request $request){
        reqeust()->validate([
            ''
        ]);
    }
}

