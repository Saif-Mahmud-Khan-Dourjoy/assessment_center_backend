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
use Dompdf\Image\Cache;
use GuzzleHttp\Client;
use http\Env\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use mysql_xdevapi\Exception;
use phpDocumentor\Reflection\Types\Null_;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Cache as CacheCandidates;

class RoundCandidatesController extends Controller
{
    public $successStatus = 200;
    public $failedStatus = 500;
    public $invalidStatus = 400;
    public $out;
    public $cacheKey;

    function __construct(){
        $this->out = new \Symfony\Component\Console\Output\ConsoleOutput();
        $this->cacheKey = env("CACHE_KEY")."_round_candidates_";
    }

    public function index(Request $request){
        try{
            Log::channel("ac_info")->info(__CLASS__."@".__FUNCTION__."# Fetching Round candidates.");
            $roundCandidates = RoundCandidates::all();
            Log::channel("ac_info")->info(__CLASS__."@".__FUNCTION__."# Fetching all round-candidates successful!");
            return response()->json(['success'=>true, 'round-candidates'=>$roundCandidates],$this->successStatus);
        }catch (\Exception $e){
            Log::channel("ac_error")->info(__CLASS__."@".__FUNCTION__."# Unable to fetch round-candidates! error: ".$e->getMessage());
            return response()->json(['success'=>false, "message"=>"Failed to fetch round-candidates!", "error"=>$e->getMessage()], $this->failedStatus);
        }
    }


    /**
     * Frsher students who have not been registered any round
     * @return \Illuminate\Http\JsonResponse
     */

    public function fresherCandidates(){
        try{
            Log::channel("ac_info")->info(__CLASS__."@".__FUNCTION__."# Fetching fresher-students who currently not in any round.");
            $user= UserProfile::where('user_id','=',Auth::id())->first();
            $students = UserProfile::with('student')->where('institute_id','=',$user->institute_id)->where('id','!=',$user->id)->get();
            $new_student = [];
            $roleList = RoleSetup::first();
            $roleName = Role::where('id','=',$roleList->student_role_id)->first();
            foreach ($students as $student) {
                if(User::with(['user_profile'])->where('id','=',$student->user_id)->role($roleName->name)->first()){
                    if(RoundCandidates::where('student_id','=',$student->id)->exists()){
                        continue;
                    }
                    array_push($new_student, $student);
                }
            }
            Log::channel("ac_info")->info(__CLASS__."@".__FUNCTION__."# Fetching fresher student successful.");
            return response()->json(['success'=>true, 'students'=>$new_student],$this->successStatus);
        }catch(\Exception $e){
            Log::channel("ac_error")->info(__CLASS__."@".__FUNCTION__."# Unable to fetch fresher-candidates! error: ".$e->getMessage());
            return response()->json(['success'=>false, "message"=>"Unable to Fetch fresher-candidates!", "error"=>$e->getMessage()], $this->failedStatus);
        }
    }

    public function cacheIn($round_id, $value){
        try{
            Log::channel("ac_info")->info(__CLASS__."@".__FUNCTION__."# Caching round: $round_id");
            $key = $this->cacheKey.$round_id;
            CacheCandidates::forget($key);
            CacheCandidates::put($key, $value);
            Log::channel("ac_info")->info(__CLASS__."@".__FUNCTION__."# Caching successful.");
        }catch (\Exception $e){
            Log::channel("ac_error")->info(__CLASS__."@".__FUNCTION__."# Unable to cached! error: ".$e->getMessage());
            return false;
        }
    }

    public function candidatesOnly($round_id){
        try{
            $round_candidates = RoundCandidates::select('student_id')->where('round_id','=',$round_id)->get();
            $candidates = [];
            foreach ($round_candidates as $rc){
                array_push($candidates, $rc['student_id']);
            }
            return $candidates;
        }catch (\Exception $e){
            return false;
        }

    }

    public function store(Request $request){
        try{
            Log::channel("ac_info")->info(__CLASS__."@".__FUNCTION__."# Storing Candidates to round(id): ".$request['round_id']);
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
//                    $userProfile = UserProfile::where('id',$student)->first();
//                    array_push($candidate_profiles, $userProfile);
                }
                else{
                    $failed_candidates=$failed_candidates.$student.'|';
                }
            }
            if(!is_null($confirm_candidates)){
                $round = Round::with('question_set')->where('id',$input['round_id'])->first();
                $temp_string = substr($confirm_candidates,0,strlen($confirm_candidates)-1);
//                $candidates = $this->candidatesOnly($round->id);
//                $this->cacheIn($round->id, $candidates);
                $this->roundConfirmFromServer($round, $confirm_candidates);
//                if (CacheCandidates::has($this->cacheKey.$round->id)) {
//                    return CacheCandidates::get($this->cacheKey.$round->id);
//                }
                Log::channel("ac_info")->info(__CLASS__."@".__FUNCTION__."# Successfully stored Candidates to round(id): ".$request['round_id']);
                return response()->json(['success'=>true, 'confirmed_candidates'=>$confirm_candidates, 'failed_candidates'=>$failed_candidates],$this->successStatus);
            }
            Log::channel("ac_info")->info(__CLASS__."@".__FUNCTION__."# Failed to store Candidates to round(id): ".$request['round_id']);
            return response()->json(['success'=>false, 'confirmed_candidates'=>$confirm_candidates, 'failed_candidates'=>$failed_candidates], $this->failedStatus);
        }catch (\Exception $e){
            Log::channel("ac_error")->info(__CLASS__."@".__FUNCTION__."# Unable to enroll Round-candidates! error: ".$e->getMessage());
            return response()->json(['success'=>false, "message"=>"Unable to Enroll the Round-candidates", "error"=>$e->getMessage()], $this->failedStatus);
        }
    }

    public function roundConfirmMail($round, $users){
        try{
            Log::channel("ac_info")->info(__CLASS__."@".__FUNCTION__."# Confirming round-candidates!");
            $round_name = $round->name;
            $title = $round_name;
            if(!is_null($round->question_set)){
                $this->out->writeln("There is no assessment for this project!");
                $title = $round->question_set['title'];
                $assessment_start_time = Carbon::parse($round->question_set->start_time);
                $body= "You are promoted to the round **\"".$round_name."\"**. Exam Title: **\"".$round->question_set['title']."\"**. Your Exam will start at: **".$assessment_start_time->toDayDateTimeString()."**.";
            }
            else{
                $body= "You are promoted to the round **\"".$round_name."\".** Your Exam Time and Date will send you later.";
            }
            foreach ($users as $user){
                try{
                    $email = Mail::to(trim($user->email))
                        ->send(new ExamConfirmation($title, $body, $user->first_name, $user->last_name));
                }catch(\Exception $e){
                    $this->out->writeln("Failed to email: $user->email, error: ".$e->getMessage());
                    continue;
                }
            }
        }catch (\Exception $e){
            $this->out->writeln("Unable to Confirm student through email, error: ".$e->getMessage());
            Log::channel("ac_error")->info(__CLASS__."@".__FUNCTION__."#Unable to confirm round-candidates!");
        }
    }

    public function roundConfirmFromServer($round, $candidates){
        try{
            Log::channel("ac_info")->info(__CLASS__."@".__FUNCTION__."# Sending information to email-server.");
            $delay = env("EMAIL_SERVER_JOB_DELAY");
            $url = env("EMAIL_SERVER_URL").'assessment-confirmation';
            $home_url = env('FRONT_END_HOME');
            $client = new Client();
            $body = [
                'round_name'=>$round->name,
                'title'=>$round->name,
                'candidates'=>substr($candidates,0,strlen($candidates)-1),
                'home_url'=>$home_url,
                "delay"=>$delay,
            ];
            if(!is_null($round->question_set)){
                $this->out->writeln("There is no assessment for this round!");
                $title = $round->question_set['title'];
                $assessment_start_time = Carbon::parse($round->question_set->start_time);
                $body['body']= "You are promoted to the round **\"".$body['round_name']."\"**. Exam Title: **\"".$round->question_set['title']."\"**. Your Exam will start at: **".$assessment_start_time->toDayDateTimeString()."**.";
            }
            else{
                $body['body']= "You are promoted to the round **\"".$body['round_name']."\".** Your Exam Time and Date will send you later.";
            }

            $response = $client->post($url, ["form_params"=>$body, 'http_errors' => false]);
            $this->out->writeln("Url: $url");
            if($response->getStatusCode()!=200)
                throw new \Exception("Mail server is not responding, its may be down or something else!");
            Log::channel("ac_info")->info(__CLASS__."@".__FUNCTION__."# Successfully sent confirmation info to email-server.");
            return true;
        }catch(\Exception $e){
            Log::channel("ac_error")->info(__CLASS__."@".__FUNCTION__."# Unable to sent round-confirm info to the candidates! error: ".$e->getMessage());
            return false;
        }
    }

    public function update(Request $request, $id){
        try{
            Log::channel("ac_info")->info(__CLASS__."@".__FUNCTION__."# Updating Round-candidate");
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
                Log::channel("ac_info")->info(__CLASS__."@".__FUNCTION__."# Student is already valid candidate for this round!");
                return response()->json(['success'=>false, 'message'=>'Student is already valid candidate for this round!'],$this->invalidStatus);
            }
            $round_candidate =RoundCandidates::find($id);
            if(!$round_candidate){
                Log::channel("ac_info")->info(__CLASS__."@".__FUNCTION__."# Round & Candidate combination is not found!");
                return response()->json(['success'=>false, 'message'=>'Round & candidate combination are not found with this id: '.$id],$this->invalidStatus);
            }
//            $round_candidate->update($data);
            if(!$round_candidate->update($data))
                throw new \Exception("Unable to update round-candidates!");
            Log::channel("ac_info")->info(__CLASS__."@".__FUNCTION__."# Successfully upgraded round-candidates.");
            return response()->json(['success'=>true, 'round_candidate'=>$round_candidate],$this->successStatus);
        }catch(\Exception $e){
            Log::channel("ac_error")->info(__CLASS__."@".__FUNCTION__."# Unable to upgrade round-candidates! error: ".$e->getMessage());
            return response()->json(["success"=>false, "message"=>"Failed to upgrade round-candidates!", "error"=>$e->getMessage()], $this->failedStatus);
        }
    }
    public function show($id){
        Log::channel("ac_info")->info(__CLASS__."@".__FUNCTION__."# ");
        try{
            $round_candidate = RoundCandidates::find($id);
            if($round_candidate){
                Log::channel("ac_info")->info(__CLASS__."@".__FUNCTION__."# Fetched round-with candidate successfully!");
                return response()->json(['success'=>true, 'round_candidate'=>$round_candidate],$this->successStatus);
            }
            Log::channel("ac_info")->info(__CLASS__."@".__FUNCTION__."# Round with candidate not found!");
            return response()->json(['success'=>false, 'message'=>'Round with candidates are not found!, id: '.$id], $this->invalidStatus);
        }catch (\Exception $e){
            return response()->json(['success'=>false, "message"=>"Fetching round-candidate is unsuccessful!", "error"=>$e->getMessage()], $this->failedStatus);
        }
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
        try{
            Log::channel("ac_info")->info(__CLASS__."@".__FUNCTION__."# Fetching Each round-candidates with round-id: ".$round_id);
            $round_candidates = RoundCandidates::with('user_profile','academic_info')->where('round_id',$round_id)->get();
            if(!$round_candidates)
                throw new \Exception("Not found candidates");
            Log::channel("ac_info")->info(__CLASS__."@".__FUNCTION__."# Successfully Fetched round-candidates.");
            return response()->json(['success'=>true, 'round_candidates'=>$round_candidates],$this->successStatus);
        }catch (\Exception $e){
            Log::channel("ac_error")->info(__CLASS__."@".__FUNCTION__."# Unable to fetch round-candidates! error: ".$e->getMessage());
            return response()->json(['success'=>false, 'message'=>'Unable to find candidates with this round-id: '.$round_id, "error"=>$e->getMessage()],$this->failedStatus);
        }
    }
}
