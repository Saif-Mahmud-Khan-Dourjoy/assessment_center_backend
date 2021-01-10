<?php

namespace App\Http\Controllers\API\Broadcast;

=======
use App\Http\Controllers\Controller;
use App\UserProfile;
>>>>>>> 8c1df042f9d231a43129829ea99c596145e35efc
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

