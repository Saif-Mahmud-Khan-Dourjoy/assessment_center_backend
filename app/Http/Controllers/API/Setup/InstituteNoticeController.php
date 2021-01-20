<?php

namespace App\Http\Controllers\API\Setup;

use App\Notice;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
class InstituteNoticeController extends Controller
{
    public $successStatus = 200;
    public $failedStatus = 500;
    public $invalidStatus = 400;
    public $out;

    function __construct(){
       $this->out = new \Symfony\Component\Console\Output\ConsoleOutput();
    }

    public function index(Request $request){
        $this->out->writeln('Fetching all notices...');
        $notices = Notice::all();
        if($notices){
            return response()->json(['success'=>true, 'notices'=> $notices], $this->successStatus);
        }
        $this->out->writeln('Unable to fetch notices.');
        return response()->json(['success'=>false, 'message'=> 'Unable to fetch Notices', $this->failedStatus]);
    }

    public function store(Request $request){
        $this->out->writeln("Inside Institutes notice store.");
        request()->validate([
            'title'=> 'required',
            'body'=> ['required', 'max:200'],
            'institutes_id'=> 'required'
        ]);
        $input = $request->all();
        $user_id =  Auth::id();
        $data = [
            'title'=>$input['title'],
            'body'=> $input['body'],
            'created_by'=> $user_id,
            'updated_by' =>$user_id,
            'institute_id'=> $input['institutes_id'],
            'status'=>(!empty($input['status'])) ? $input['status'] : 0,
        ];
        $notice = Notice::create($data);
        if($notice){
            $this->out->writeln("Successfully addedd notice, ".$notice);
            return response()->json(['success'=> true, 'notice'=>$notice], $this->successStatus);
        }
        return response()->json(['success'=>false, 'message'=>'Failed to add Notice', $this->failedStatus]);
    }

    public function getNotice($id){
        $this->out->writeln("Fetching notice: ".$id);
        if($id){
            $notice = Notice::find($id);
            $this->out->writeln("Notice".$notice);
            return response()->json(['success'=>true, 'notice'=>$notice],$this->successStatus);
        }
        return response()->json(['success'=> false, 'message'=>'Unable to find this notice'],$this->invalidStatus);
    }

    public function update(Request $request){
        $this->out->writeln('Updating Notices...');
        request()->validate([
            'id'=> 'required',
            'title'=> 'required',
            'body'=> ['required', 'max:200'],
            'institutes_id'=> 'required',
            'status' =>'required'
        ]);
        $input = $request->all();
        $user_id =  Auth::id();
        $data = [
            'title'=>$input['title'],
            'body'=> $input['body'],
            'created_by'=> $user_id,
            'updated_by' =>$user_id,
            'institute_id'=> $input['institutes_id'],
            'status'=> $input['status'],
        ];
        $notice = Notice::find($input['id']);
        $notice->update($data);
        $notice->save();
        if($notice){
            $this->out->writeln("Successfully addedd notice, ".$notice);
            return response()->json(['success'=> true, 'notice'=>$notice], $this->successStatus);
        }
        return response()->json(['success'=>false, 'message'=>'Failed to update Notice', $this->failedStatus]);
    }
    
    public function instituteNotice($id)
    {
        $this->out->writeln('Fetching Institute notices...');
        if($id){
            $notices = Notice::where('institute_id',$id)->get();
            $this->out->writeln("Notice".$notices);
            return response()->json(['success'=>true, 'notice'=>$notices],$this->successStatus);
        }
        return response()->json(['success'=> false, 'message'=>'Unable to find this notice'],$this->invalidStatus);
    }

    public function delete($id){
        $this->out->writeln('Deleting notice...');
        if($id){
            $notice = Notice::find($id);
            $notice->delete();
            return response()->json(['success'=> true, 'message'=>$notice],$this->successStatus);
        }
        return response()->json(['success'=> false, 'message'=>'Unable to delete this notice'],$this->invalidStatus);
    }

    public function status($id){
        $this->out->writeln('Updating Status...');
        if($id){
            $notice = Notice::find($id);
            $notice->status = ($notice['status']==0 ? 1:0);
            $notice->save();
            return response()->json(['success'=> true, 'message'=>$notice],$this->successStatus);
        }
        return response()->json(['success'=> false, 'message'=>'Unable to change status of this notice'],$this->invalidStatus);
    }


}
