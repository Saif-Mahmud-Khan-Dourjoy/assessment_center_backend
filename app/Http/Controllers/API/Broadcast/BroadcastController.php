<?php

namespace App\Http\Controllers\API\Broadcast;

use App\Broadcast;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BroadcastController extends Controller
{
    public $successStatus=200;
    public $failedStatus =500;
    public $invalidStatus=400;

    protected $out;

    protected $type=[
      'notice'=>0,
      'result'=>1,
      'certificate'=>2,
    ];
    protected $group=[
      'institute'=>0,
      'round'=>1,
      'question_set'=>2,
    ];
    function __construct(){
        $this->out = new \Symfony\Component\Console\Output\ConsoleOutput();
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
            'to'=>$input['to'],
            'broadcast_to'=>$input['broadcast_to'],
            'broadcast_by'=>$user_id,
        ];
        $broadcast = Broadcast::create($data);
        if($broadcast){
            //
        }
    }
}

