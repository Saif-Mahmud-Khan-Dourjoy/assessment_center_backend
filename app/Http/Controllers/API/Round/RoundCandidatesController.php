<?php

namespace App\Http\Controllers\API\Round;

use App\Http\Controllers\Controller;
use App\Mail\BroadcastNotice;
use App\Mail\ExamConfirmation;
use App\RoleSetup;
use App\Round;
use App\RoundCandidates;
use App\User;
use App\UserProfile;
use Carbon\Carbon;
use http\Env\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use phpDocumentor\Reflection\Types\Null_;
use Spatie\Permission\Models\Role;

class RoundCandidatesController extends Controller
{
    public $successStatus = 200;
    public $failedStatus = 500;
    public $invalidStatus = 400;
    public $out;
    function __construct(){
        $this->out = new \Symfony\Component\Console\Output\ConsoleOutput();
    }

    public function index(Request $request){
        $this->out->writeln('Fetching Round Candidates...');
        $roundCandidates = RoundCandidates::all();
        if($roundCandidates){
            return response()->json(['success'=>true, 'round-candidates'=>$roundCandidates],$this->successStatus);
        }
        return response()->json(['success'=>false, 'message'=>'Unable to fetch round with candidates'], $this->failedStatus);
    }


    /**
     * Frsher students who have not been registered any round
     * @return \Illuminate\Http\JsonResponse
     */

    public function fresherCandidates(){
        try{
            $this->out->writeln("Fetching Fresher-students who currently not in any round!");
            $user= UserProfile::where('user_id','=',Auth::id())->first();
            $students = UserProfile::with('student')->where('institute_id','=',$user->institute_id)->where('id','!=',$user->id)->get();
            $new_student = [];
            $roleList = RoleSetup::first();
            $roleName = Role::where('id','=',$roleList->student_role_id)->first();
            $this->out->writeln('role name: '.$roleName->name);
            foreach ($students as $student) {
                if(User::with(['user_profile'])->where('id','=',$student->user_id)->role($roleName->name)->first()){
                    if(RoundCandidates::where('student_id','=',$student->id)->exists()){
                        continue;
                    }
                    array_push($new_student, $student);
                }
            }
            return response()->json(['success'=>true, 'students'=>$new_student],$this->successStatus);
        }catch(\Exception $e){
            $this->out->writeln("Unable to Fetch fresher-candidates, error: ".$e->getMessage());
            return response()->json(['success'=>false, "message"=>"Unable to Fetch fresher-candidates!", "error"=>$e->getMessage()], $this->failedStatus);
        }
    }

    public function store(Request $request){
        $this->out->writeln('Storing Candidates based on the round-id');
        try{
            request()->validate([
                'round_id'=> 'required',
                'candidate_students'=>'required',
            ]);
            $input= $request->all();
            $students = explode('|',$input['candidate_students']);
            $confirm_candidates=null;
            $failed_candidates = null;
            $candidate_profiles = [];
            $data=[
                'round_id'=>$input['round_id'],
            ];
            foreach ($students as $student){
                $data['student_id']=$student;
                if(RoundCandidates::firstOrCreate($data)){
                    $confirm_candidates=$confirm_candidates.$student.'|';
                    $userProfile = UserProfile::where('id',$student)->first();
                    array_push($candidate_profiles, $userProfile);
                }
                else{
                    $failed_candidates=$failed_candidates.$student.'|';
                }
            }
            if(!is_null($confirm_candidates)){
                $round = Round::with('question_set')->where('id',$input['round_id'])->first();
                $this->out->writeln('Rounds: '.$round);
                $this->roundConfirmMail($round, $candidate_profiles);
                return response()->json(['success'=>true, 'confirmed_candidates'=>$confirm_candidates, 'failed_candidates'=>$failed_candidates],$this->successStatus);
            }
            return response()->json(['success'=>false, 'confirmed_candidates'=>$confirm_candidates, 'failed_candidates'=>$failed_candidates], $this->failedStatus);
        }catch (\Exception $e){
            $this->out->writeln("Unable to Enroll the Round-candidates, error");
            return response()->json(['success'=>false, "message"=>"Unable to Enroll the Round-candidates", "error"=>$e->getMessage()], $this->failedStatus);
        }
    }

    public function roundConfirmMail($round, $users){
        $round_name = $round->name;
        $title = $round_name;
        if(!is_null($round->question_set)){
            $this->out->writeln("There is no assessment for this project!");
            $title = $round->question_set['title'];
            $assessment_start_time = Carbon::parse($round->question_set->start_time);
            $body= "You are promoted to the round **\"".$round_name."\"**. Exam Title: **\"".$round->question_set['title']."\"**. Your Exam will start at: **".$assessment_start_time->toDayDateTimeString()."**.";
        }
        else{
            $body = $body= "You are promoted to the round **\"".$round_name."\".** Your Exam Time and Date will send you later.";
        }

        foreach ($users as $user){
            $this->out->writeln('User email: '.$user->email);
            $email = Mail::to(trim($user->email))
                ->send(new ExamConfirmation($title, $body, $user->first_name, $user->last_name));
            $this->out->writeln('Email confirm: '.$email);
        }
    }

    public function update(Request $request, $id){
        $this->out->writeln('Updating Round Candidates...');
        request()->validate([
            'round_id'=>'required',
            'student_id'=>'required',
        ]);
        $input = $request->all();
        $data=[
            'round_id'=>$input['round_id'],
            'student_id'=>$input['student_id']
        ];
        if(RoundCandidates::where('round_id',$input['round_id'])->where('student_id','=',$input['student_id'])->where('id','!=',$id)->exists()){
            return response()->json(['success'=>false, 'message'=>'Student is already valid candidate for this round!'],$this->invalidStatus);
        }
        $round_candidate =RoundCandidates::find($id);
        if(!$round_candidate){
            return response()->json(['success'=>false, 'message'=>'Round & candidate combination are not found with this id: '.$id],$this->invalidStatus);
        }
        $round_candidate->update($data);
        if($round_candidate->save()){
            return response()->json(['success'=>true, 'round_candidate'=>$round_candidate],$this->successStatus);
        }
        return response()->json(['success'=>false, 'message'=>'unable to update round candidates, id: '.$input['id']], $this->failedStatus);
    }
    public function show($id){
        $this->out->writeln('Fetching round with candidates, id: '.$id);
        $round_candidate = RoundCandidates::find($id);
        if($round_candidate){
            return response()->json(['success'=>true, 'round_candidate'=>$round_candidate],$this->successStatus);
        }
        return response()->json(['success'=>false, 'message'=>'Round with candidates are not found!, id: '.$id], $this->invalidStatus);
    }

    public function destroy($id){
        $this->out->writeln('Deleting Round Candidates, id'.$id);
        $round_candidate = RoundCandidates::find($id);
        if(!$round_candidate){
            return response()->json(['success'=>false, 'message'=>'Round with candidate not found, id: '.$id],$this->invalidStatus);
        }
        if($round_candidate->delete()){
            return response()->json(['success'=>true, 'message'=>'Round with candidate is deleted, id: '.$id], $this->successStatus);
        }
        return response()->json(['success'=>false, 'message'=>'Unable to delete round candidate, id: '.$id],$this->failedStatus);
    }

    public function eachRoundCandidates($round_id){
        $this->out->writeln('Fetching candidates based on the round-id: '.$round_id);
        $round_candidates = RoundCandidates::with('user_profile','academic_info')->where('round_id',$round_id)->get();
        if($round_candidates){
            return response()->json(['success'=>true, 'round_candidates'=>$round_candidates],$this->successStatus);
        }
        return response()->json(['success'=>false, 'message'=>'Unable to find candidates with this round-id: '.$round_id],$this->invalidStatus);
    }

}
