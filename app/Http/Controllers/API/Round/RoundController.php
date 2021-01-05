<?php

namespace App\Http\Controllers\API\Round;

use App\Http\Controllers\Controller;
use App\Round;
use App\UserProfile;
use http\Env\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RoundController extends Controller
{
    public $successStatus = 200;
    public $failedStatus = 500;
    public $invalidStatus = 400;
    public $out;

    function __construct(){
        $this->out = new \Symfony\Component\Console\Output\ConsoleOutput();
    }

    public function index(Request $request){
        $this->out->writeln('Fetching all the rounds');
        $rounds = Round::all();
        if($rounds){
            return response()->json(['success'=>true, 'rounds'=>$rounds], $this->successStatus);
        }
        return response()->json(['success'=>false, 'message'=>'Unable to fetch rounds'], $this->failedStatus);
    }
    public function store(Request $request){
        $this->out->writeln('Storing the rounds...');
        request()->validate([
            'name'=>'required',
            'passing_criteria'=>'required',
            'number'=>'required',
        ]);
        $input = $request->all();
        $user = UserProfile::where('user_id',Auth::id())->first();
//        dd($user);
//        return response()->json(['user'=>$user]);
        $this->out->writeln($user);
        $data = [
            'name'=>$input['name'],
            'institute_id'=> $user->institute_id,
            'passing_criteria'=>$input['passing_criteria'],
            'number'=>$input['number'],
            'created_by'=>$user->user_id,
            'updated_by'=>$user->user_id,
        ];
        if(Round::where('name',$input['name'])->where('institute_id','=',$user->institute_id)->exists()){
            return response()->json(['success'=>false, 'message'=>'Round Name is already available for this institution!'],$this->invalidStatus);
        }
        $round = Round::create($data);
        if($round){
            return response()->json(['success'=>true, 'round'=>$round], $this->successStatus);
        }
        return reseponse()->json(['success'=>false, 'message' =>'Unable to fetch rounds'],$this->failedStatus);

    }

    public function getInstituteRound(Request $request, $institute){
        $this->out->writeln('Fetching institutions based rounds, institute-id: '.$institute);
//        $institute = UserProfile::select('institute_id')->where('user_id', Auth::id())->find();
        $rounds = Round::where('institute_id', $institute)->get();
        $this->out->writeln('All rounds of this institutes: '.$rounds);
        if($rounds){
            return response()->json(['success'=>true, 'rounds'=>$rounds], $this->successStatus);
        }
        return response()->json(['success'=>false, 'message'=>'Unable to fetch rounds'], $this->failedStatus);
    }

    public function show($id){
        $this->out->writeln('Fetching round, whose id is: '.$id);
        $round = Round::find($id);
        if($round){
            return response()->json(['success'=>true, 'round'=>$round],$this->successStatus);
        }
        return response()->json(['success'=>false, 'message'=>'Unable to find this round!'], $this->invalidStatus);
    }

    public function update(Request $request, $id){
        $this->out->writeln('Updating Round...');
        request()->validate([
            'name'=>'required',
            'passing_criteria'=>'required',
            'number'=>'required',
            'institute_id'=>'required',
        ]);
        $input = $request->all();
        $user_id = Auth::id();
        $data = [
            'name'=> $input['name'],
            'passing_criteria'=> $input['passing_criteria'],
            'number'=>$input['number'],
            'updated_by'=>$user_id,
        ];
        if(Round::where('name',$input['name'])->where('institute_id','=',$input['institute_id'])->exists()){
            return response()->json(['success'=>false, 'message'=>'Round Name is already available for this institution!'],$this->invalidStatus);
        }
        $round = Round::find($id);
        $round->update($data);
        $round->save();
        if($round){
            return response()->json(['success'=>true, 'round'=>$round], $this->successStatus);
        }
        return response()->json(['success'=>false, 'message'=>'Unable to update Round!'], $this->failedStatus);
    }

    public function destroy($id){
        $this->out->writeln('Deleting Round: '.$id);
        $round = Round::find($id);
        if(!$round){
            return response()->json(['success'=>false, 'message'=>'Round not found, id: '.$id],$this->invalidStatus);
        }
        if($round->delete()){
            return response()->json(['success'=>true, 'message'=>'Round is deleted, id: '.$id], $this->successStatus);
        }
        return reponse()->json(['success'=>false, 'message'=>'Unable to delete round, id: '.$id], $this->failedStatus);
    }
    public function status($id){
        $this->out->writeln('Updating Status, id: '.$id);
        $round = Round::find($id);
//        return response()->json(['success'=>true, 'round'=>$round],$this->successStatus);
        if(!$round){
            return response()->json(['success'=>false, 'message'=>'Round not found, id: '.$id], $this->invalidStatus);
        }
        $round->status = ($round['status']==0 ? 1:0);
        if($round->save()){
            return response()->json(['success'=>true, 'round'=>$round],$this->successStatus);
        }
        return reponse()->json(['success'=>false, 'message'=>'Unable to update round\'s status'], $this->successStatus);
    }
}
