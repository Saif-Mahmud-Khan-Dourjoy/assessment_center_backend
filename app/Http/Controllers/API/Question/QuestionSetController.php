<?php

namespace App\Http\Controllers\API\Question;

use App\Contributor;
use App\Http\Controllers\Controller;
use App\Question;
use App\QuestionDetail;
use App\QuestionSet;
use App\QuestionSetCandidate;
use App\QuestionSetDetail;
use App\QuestionSetAnswer;
use App\QuestionSetAnswerDetail;
use App\RoundCandidates;
use App\Student;
use App\User;
use App\UserProfile;
use Carbon\Carbon;
use http\Env\Response;
use Illuminate\Foundation\Bootstrap\LoadEnvironmentVariables;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Composer;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
//use Illuminate\Support\Facades\Cache as CacheCandidates;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use mysql_xdevapi\Exception;

class QuestionSetController extends Controller
{
    public $successStatus = 200;
    public $failedStatus = 500;
    public $invalidStatus = 400;
    protected $cacheKey;
    protected $roundCachekey;

    public $out;
    function __construct()
    {
//        $this->middleware('api_permission:question-set-list|question-set-create|question-set-edit|question-set-delete', ['only' => ['index','show']]);
//        $this->middleware('api_permission:question-set-create', ['only' => ['store']]);
//        $this->middleware('api_permission:question-set-edit', ['only' => ['update']]);
//        $this->middleware('api_permission:question-set-delete', ['only' => ['destroy']]);
        $this->out = new \Symfony\Component\Console\Output\ConsoleOutput();
        $this->cacheKey= env("CACHE_KEY")."_question_set_";
        $this->roundCachekey = env('CACHE_KEY')."_round_candidates_";
    }


    /**
     * Display a listing of the resource.
     *
     * @return JsonResponse
     */
    public function index()
    {
        try{
            Log::channel("ac_info")->info(__CLASS__."@".__FUNCTION__."# Fetching all assessments.");
            $user = Auth::user();
            $userProfile = UserProfile::where('user_id', $user->id)->first();
            $i=0;
            if($user->can('super-admin')){
                $question_sets = QuestionSet::with(['question_set_details'])->get();
                foreach($question_sets as $question_set){
                    $question_set_id = $question_set->id;
                    if(QuestionSetAnswer::where('question_set_id','=',$question_set_id)->where('profile_id','=',$userProfile->id)->exists()){
                        $question_sets[$i]['attended']=1;
                    }
                    else{
                        $question_sets[$i]['attended']=0;
                    }
                    $i++;
                }
                Log::channel("ac_info")->info(__CLASS__."@".__FUNCTION__."# Returning all the assessments for super-admin.");
                return response()->json(['success' => true, 'question_set' => $question_sets], $this-> successStatus);
            }

            if($userProfile->institute_id) {
                $question_sets = QuestionSet::with(['question_set_details'])
                    ->where('institute_id', '=', $userProfile->institute_id)
                    ->get();

                foreach ($question_sets as $question_set) {
                    $question_set_id = $question_set->id;
                    if (QuestionSetAnswer::where('question_set_id', '=', $question_set_id)->where('profile_id', '=', $userProfile->id)->exists()) {
                        $question_sets[$i]['attended'] = 1;
                    } else {
                        $question_sets[$i]['attended'] = 0;
                    }
                    $i++;
                }
                Log::channel("ac_info")->info(__CLASS__."@".__FUNCTION__."# Returning all the assessments for inst-id: ".$user->institute_id);
                return response()->json(['success' => true, 'question_set' => $question_sets], $this-> successStatus);
            }
            Log::channel("ac_info")->info(__CLASS__."@".__FUNCTION__."# No Assessment Found!");
            return response()->json(['success' => true, 'question_set' => []], $this-> successStatus);
        }catch (\Exception $e){
            Log::channel("ac_error")->info(__CLASS__."@".__FUNCTION__."# Unable to fetch assessments! error: ".$e->getMessage());
            return response()->json(['success'=>false, "message"=>"Unable to fetch assessments!", "error"=>$e->getMessage()], $this->failedStatus);
        }
    }

    public function index_old(){
        $user = Auth::user();
        $userProfile = UserProfile::where('user_id', $user->id)->first();
        $i=0;
        if($userProfile->institute_id){
            $question_sets = QuestionSet::with(['question_set_details'])
                ->where('privacy', '=', 0)
                ->orWhere('privacy', '=', 1)
                ->where('institute_id', '=', $userProfile->institute_id)
                ->orWhere('privacy', '=', 2)
                ->where('created_by', '=', $userProfile->id)
                ->orWhere('created_by', '=', $userProfile->id)
                ->get();

            foreach($question_sets as $question_set){
                $question_set_id = $question_set->id;
                if(QuestionSetAnswer::where('question_set_id','=',$question_set_id)->where('profile_id','=',$userProfile->id)->exists()){
                    $question_sets[$i]['attended']=1;
                }
                else{
                    $question_sets[$i]['attended']=0;
                }
                $i++;
            }
        }else{
            $question_sets = QuestionSet::with(['question_set_details'])
                ->where('privacy', '=', 0)
                ->orWhere('privacy', '=', 2)
                ->where('created_by', '=', $userProfile->id)
                ->orWhere('created_by', '=', $userProfile->id)
                ->get();
            foreach($question_sets as $question_set){
                $question_set_id = $question_set->id;
                if(QuestionSetAnswer::where('question_set_id','=',$question_set_id)->where('profile_id','=',$userProfile->id)->exists()){
                    $question_sets[$i]['attended']=1;
                }
                else{
                    $question_sets[$i]['attended']=0;
                }
                $i++;
            }
        }
        return response()->json(['success' => true, 'question_set' => $question_sets], $this-> successStatus);
    }


    /**
     * Assessment Time validator
     * @param $start_time
     * @param $end_time
     * @param $duration
     * @return bool
     */
    public function assessmentTimeValidator($start_time, $end_time, $duration){
        try{
            Log::channel("ac_info")->info(__CLASS__."@".__FUNCTION__."# Validating assessment time.");
            $startTime = Carbon::parse($start_time);
            $endTime = Carbon::parse($end_time);
            $this->out->writeln('Start time: '.$startTime." ****** End time: ".$endTime);
            if ($startTime->addMinutes($duration)>$endTime){
                Log::channel("ac_info")->info(__CLASS__."@".__FUNCTION__."# Invalid assessment time.");
                return false;
            }
            Log::channel("ac_info")->info(__CLASS__."@".__FUNCTION__."# Assessment time is valid.");
            return true;
        }catch(\Exception $e){
            Log::channel("ac_error")->info(__CLASS__."@".__FUNCTION__."# Unable to validate assessment time! error: ".$e->getMessage());
            $this->out->writeln("Unable to validate Assessment Time! error: ".$e->getMessage());
            return null;
        }
    }
    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request)
    {
        try{
            Log::channel("ac_info")->info(__CLASS__."@".__FUNCTION__."# Storing Assessment.");
            $user = Auth::user();
            $userProfile = UserProfile::where('user_id', $user->id)->first();
            request()->validate([
                'title' => 'required',
            ]);
            $input = $request->all();
            $institute_id = NULL;
            $privacy = (!empty($_POST["privacy"])) ? $input['privacy'] : 0;
            if($privacy == 1 && $userProfile->institute_id){
                $institute_id = $userProfile->institute_id;
            }
            // Time calculations
            $assessment_time = $input['assessment_time'];
            $start_time = (!empty($input['start_time']) || !is_null($input['start_time'])? $input['start_time'] : '');
            $end_time = (!empty('end_time') || !is_null($input['end_time'])? $input['end_time']: '');
            $this->out->writeln('Start time: '.$start_time." End time: ". $end_time." lLaravel timestamp: ".now());
            if(!($this->assessmentTimeValidator($start_time, $end_time, $input['assessment_time']))){
                return response()->json(['success'=>false, 'message'=>'Invalid Exam time and duration!'], $this->invalidStatus);
            }
            // Add question set
            $questionData = [
                'title' => $input['title'],
                'type' => $input['type'],
                'institute' => (!empty($_POST["institute"])) ? $input['institute'] : '',
                'institute_id' => (!(empty($input['institute_id'] or is_null($input['institute_id'])))? $input['institute_id']:null),
                'assessment_time' => $input['assessment_time'],
                'start_time' => $start_time,
                'end_time'=>$end_time,
                'each_question_time' => (!empty('each_question_time') || !is_null($input['each_question_time'])? $input['each_question_time']: 0),
                'total_question' => $input['total_question'],
                'total_mark' => $input['total_mark'],
                'status' => $input['status'],
                'privacy' => $privacy,
                'approved_by' => $userProfile->id,//Profile ID
                'round_id'=>$input['round_id'],
                'created_by' => $user->id,//User ID
                'updated_by' => $user->id,
            ];
            $question = QuestionSet::create($questionData);
            if(!$question)
                throw new \Exception("Question-set Creation unsuccessful!");
            // Add question detail
            $questionOptionData = [];
            $question_id = explode( ',', $input['question_id']);
            $mark = explode( ',', $input['mark']);
            $partial_marking_status = explode( ',', $input['partial_marking_status']);
            if(!(is_null($input['question_time']) && $questionData['each_question_time']==0)){
                $question_time = explode(',',$input['question_time']);
            }
            for($i = 0; $i < count($question_id); $i++){
                $questionOptionData = [
                    'question_set_id' => $question->id,
                    'question_id' => $question_id[$i],
                    'mark' => $mark[$i],
                    'question_time'=>($questionData['each_question_time']==0?0:$question_time[$i]),
                    'partial_marking_status' => $partial_marking_status[$i],
                ];
                QuestionSetDetail::create($questionOptionData);
                // Increment question total no of use
                Question::find($question_id[$i])->increment('no_of_used');
            }
            $question_sets = QuestionSet::with(['question_set_details'])->where('id', $question->id)->get();
            if(!$question_sets)
                throw new \Exception("Question-set not found!");
            Log::channel("ac_info")->info(__CLASS__."@".__FUNCTION__."# Storing Assessment successful.");
            return response()->json(['success' => true, 'question_set' => $question_sets], $this->successStatus);
        }catch (\Exception $e){
            Log::channel("ac_error")->info(__CLASS__."@".__FUNCTION__."# Unable to store Assessment! error: ".$e->getMessage());
            return response()->json(['success'=>false, "message"=>"Unable create Assessment!", "error"=>$e->getMessage()], $this->failedStatus);
        }
    }


    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param $id
     * @return JsonResponse
     */
    public function update(Request $request, $id)
    {
        try{
            Log::channel("ac_info")->info(__CLASS__."@".__FUNCTION__."# Updating Assessment.");
            $user = Auth::user();
            $userProfile = UserProfile::where('user_id', $user->id)->first();
            request()->validate([
                'title' => 'required',
            ]);
            $input = $request->all();
            $institute_id = NULL;
            $privacy = (!empty($_POST["privacy"])) ? $input['privacy'] : 0;
            if($privacy == 1 && $userProfile->institute_id){
                $institute_id = $userProfile->institute_id;
            }
            // Add question set
            $questionsetData = [
                'title' => $input['title'],
                'type' => $input['type'],
                'institute' => (!empty($_POST["institute"])) ? $input['institute'] : '',
                'institute_id' => (!(empty($input['institute_id'] or is_null($input['institute_id'])))? $input['institute_id']:null),
                'assessment_time' => $input['assessment_time'],
                'start_time' => (!empty($input['start_time']) || !is_null($input['start_time'])? $input['start_time'] : ''),
                'end_time'=>(!empty('end_time') || !is_null($input['end_time'])? $input['end_time']: ''),
                'each_question_time' => (!empty($input['each_question_time']) || !is_null($input['each_question_time'])? $input['each_question_time']: 0),
                'total_question' => $input['total_question'],
                'total_mark' => $input['total_mark'],
                'status' => $input['status'],
                'privacy' => $privacy,
                'approved_by' => $userProfile->id,//Profile ID
                'round_id'=>$input['round_id'],
                'updated_by'=>$user->id,
            ];
            // Question-Set Details data
            $question_id = explode( ',', $input['question_id']);
            $mark = explode( ',', $input['mark']);
            $partial_marking_status = explode( ',', $input['partial_marking_status']);
            if(!(is_null($input['question_time']) && $questionsetData['each_question_time']==0)){
                $question_time = explode(',',$input['question_time']);
            }
            DB::beginTransaction();
            try{
                $questionset = QuestionSet::find($id);
                // if( ! UserAcademicHistory::where(['profile_id' => $input['profile_id'], 'check_status' => $input['check_status']])->first() )
                // UserAcademicHistory::where('profile_id', $input['profile_id'])->delete();
                $questionset_status =  $questionset->update($questionsetData);
                if(!$questionset_status)
                    throw new \Exception("Question-Set Update failed!");
                QuestionSetDetail::where(['question_set_id'=>$id])->delete();                                           // Remove question set details
                for($i = 0; $i < count($question_id); $i++){                                                            // Add question set detail
                    $questionOptionData = [
                        'question_set_id' => $questionset->id,
                        'question_id' => $question_id[$i],
                        'mark' => $mark[$i],
                        'question_time'=>($questionsetData['each_question_time']==0?0:$question_time[$i]),
                        'partial_marking_status' => $partial_marking_status[$i],
                    ];
                    QuestionSetDetail::create($questionOptionData);
                    Question::find($question_id[$i])->increment('no_of_used');                                              // Increment question total no of use
                }
            }catch(\Exception $inner){
                DB::rollback();
                $this->out->writeln("Question-set update failed! error: ".$inner->getMessage());
                Log::channel("ac_error")->info(__CLASS__."@".__FUNCTION__."# Unable to upgrade Assessment! error: ".$inner->getMessage());
                return response()->json(['success'=>false, "message"=>"Question-Set Update is unsuccessful!", "error"=>"Sequence of any transaction is failed & rollback everything! coz: ".$inner->getMessage()], $this->failedStatus);
            }
            Db::commit();
            $question_sets = QuestionSet::with(['question_set_details'])->where('id', $questionset->id)->get();
            Cache::forget($this->cacheKey.$id);
            Log::channel("ac_info")->info(__CLASS__."@".__FUNCTION__."# Assessment Upgrading successful.");
            return response()->json(['success' => true, 'question_set' => $question_sets], $this->successStatus);
        }catch (\Exception $e){
            $this->out->writeln("Question-Set update is unsuccessful! error: ".$e->getMessage());
            Log::channel("ac_error")->info(__CLASS__."@".__FUNCTION__."# Unable to upgrade Assessment! error: ".$e->getMessage());
            return response()->json(['success'=>false, "message"=>"Question-Set Update is unsuccessful!", "error"=>$e->getMessage()], $this->failedStatus);
        }
    }


    /**
     * Display the specified resource.
     *
     * @param $id
     * @return JsonResponse
     */
    public function show($id)
    {
        try{
            Log::channel("ac_info")->info(__CLASS__."@".__FUNCTION__."# Fetching Question-set.");
            $this->out->writeln('Fetching Question set with all questions, question-set id: '.$id);
            $userProfile = UserProfile::where('user_id','=',Auth::id())->first();
            $question_set = QuestionSet::inRandomOrder()->with(['question_set_details'])
                ->where('id', $id)
                ->get();
            if (sizeof($question_set)<1)
                return response()->json(['success' => false, 'message' => 'Question set not found'], $this->invalidStatus);
            $i = 0;
//        $question_set_details = $question_set[0]->question_set_details;
            $question_set_details = json_decode($question_set[0]->question_set_details, true);
            shuffle($question_set_details);
            foreach ($question_set[0]->question_set_details as $question_detail){
                $this->out-> writeln('Question set details: '.$question_detail);
                $this->out->writeln('Question ID: '.$question_detail->question_id);
                $question = Question::with(['question_details', 'question_answer', 'question_tag'])
                    ->where('id', $question_detail->question_id)
                    ->get();
                $question_set[0]->question_set_details[$i++]['question']=$question;
            }
            Log::channel("ac_info")->info(__CLASS__."@".__FUNCTION__."# Fetching Question-set successful.");
            return response()->json(['success' => true, 'question_set' => $question_set], $this->successStatus);
        }catch(\Exception $e){
            Log::channel("ac_error")->info(__CLASS__."@".__FUNCTION__."# Unable to fetch Question-set! error: ".$e->getMessage());
            return response()->json(['success'=>false, "message"=>"Unable to show Assessment!", "error"=>$e->getMessage()], $this->failedStatus);
        }
    }

    public function attendQuestionSet($id)
    {   $st_time = microtime(true);
        try{
            $userProfile = UserProfile::where('user_id','=',Auth::id())->first();
            Log::channel("ac_info")->info(__CLASS__."@".__FUNCTION__."# Attending Question-set: $id, Profile-id: ".$userProfile->id);
            if(QuestionSetCandidate::where('question_set_id',$id)->where('profile_id',$userProfile->id)->exists())
                return response()->json(['success'=>false, "message"=>"You have already attended!"],$this->failedStatus);
            Student::where('profile_id','=',$userProfile->id)->increment('total_complete_assessment');
//            if (Cache::has($this->roundCachekey.)) {
//                return CacheCandidates::get($this->cacheKey.$round->id);
//            }
            /*** Remove Comment if time-validation seems necessary ***
            $now = now();
            if(Carbon::parse($question_set[0]->start_time)>$now || Carbon::parse($question_set[0]->end_time)<$now)
                return response()->json(['success' => false, 'message' => 'Invalid entering time, Assessment time may over or its too early to participate on this Assessment!'], $this->invalidStatus);
            *****/
            $this->out->writeln("Attending Student for the assessment-id: ".$id);
            $question_set = Cache::remember($this->cacheKey.$id, 60*60*24, function () use ($id) {
                $this->out->writeln("Question-set Not found in cache!");
                $question_set = QuestionSet::with(['question_set_details'])
                    ->where('id', $id)
                    ->get();
                if (sizeof($question_set)<1)
                    return response()->json(['success' => false, 'message' => 'Question set not found'], $this->invalidStatus);
                $i = 0;
                foreach ($question_set[0]->question_set_details as $question_detail){
                    $question = Question::with(['question_details', 'question_answer', 'question_tag'])
                        ->where('id', $question_detail->question_id)
                        ->get();
                    $question_set[0]->question_set_details[$i++]['question']=$question;
                }
                return $question_set;
            });
            $time_taken= microtime(true)-$st_time;
            $this->out->writeln("Total time taken: ".$time_taken);
            $question_set_candidates = QuestionSetCandidate::create(['question_set_id'=>$question_set[0]->id, 'profile_id'=>$userProfile->id, 'attended'=>1]);
            Log::channel("ac_info")->info(__CLASS__."@".__FUNCTION__."# Successfully attended on Question-set: $id, Profile-id: ".$userProfile->id);
            return response()->json(['success' => true, 'question_set' => $question_set], $this->successStatus);
        }catch(\Exception $e){
            Log::channel("ac_error")->info(__CLASS__."@".__FUNCTION__."# Successfully attended on Question-set: $id, Profile-id: ".$userProfile->id.", error: ".$e->getMessage());
            return response()->json(['success'=>false, "message"=>"Unable to fetch Question-set for attend!","error"=>$e->getMessage()], $this->failedStatus);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param $id
     * @return JsonResponse
     * @throws Exception
     */
    public function destroy($id)
    {
        try{
            Log::channel("ac_info")->info(__CLASS__."@".__FUNCTION__."# Deleting Question-set: $id.");
            $this->out->writeln("Question-set Deleting: ".$id);
            $question_set = QuestionSet::find($id);
            if(!$question_set)
                throw new \Exception("Question-Set not found!");
            if ($question_set->delete())
                Cache::forget($this->cacheKey.$id);
            Log::channel("ac_info")->info(__CLASS__."@".__FUNCTION__."# Successfully deleted Question-set: $id");
            return response()->json(['success' => true, 'message' => 'Question set deleted'], $this->successStatus);
        }catch(\Exception $e){
            Log::channel("ac_error")->info(__CLASS__."@".__FUNCTION__."# Unable to delete question-set: $id! error: ".$e->getMessage());
            return response()->json(['success'=>false, "message"=>"Question-Set Deletion unsuccessful!", "error"=>$e->getMessage()], $this->failedStatus);
        }
    }

    public function status($id){
        try{
            Log::channel("ac_info")->info(__CLASS__."@".__FUNCTION__."# Changing Assessment-Status: $id");
            $question_st = QuestionSet::find($id);
            if(!$question_st){
                return response()->json(['success'=>false, 'message'=>'Question Set Not Found, id: '.$id],$this->invalidStatus);
            }
//        $question_st->status = ($question_st['status']==0 ? 1:0);
            $question_st['status']=1-$question_st['status'];
            if($question_st->save()){
                return response()->json(['success'=>true, 'question_set'=>$question_st],$this->successStatus);
            }
            Log::channel("ac_info")->info(__CLASS__."@".__FUNCTION__."# Changing Assessment-Status: $id");
            return response()->json(['success'=>false, 'message'=>'Failed to change question set publish status'],$this->failedStatus);
        }catch (\Exception $e){
            Log::channel("ac_error")->info(__CLASS__."@".__FUNCTION__."# Failed to change ac-status: $id! error: ".$e->getMessage());
            return response()->json(['success'=>false, "message"=>"Changing assessment status unsuccessful!", "error"=>$e->getMessage()], $this->failedStatus);
        }
    }

    /**
     * Get all assessment list for this student
     * @param student_id (user-profile id)
     * @return JsonResponse assessment list (question set)
     */

    public function studentHaveAssessments(Request $request){
        try{
            Log::channel("ac_info")->info(__CLASS__."@".__FUNCTION__."# Request accepted!");
            $input = $request->all();
            $user = Auth::user();
            $userProfile = UserProfile::where('user_id', $user->id)->first();
            $i=0;
            $question_sets =[];
            if(is_null($input['is_student']) or empty($input['is_student']) or !$input['is_student']){
                return response()->json(['success'=>false, 'question_sets'=>$question_sets],$this->invalidStatus);
            }
            $rounds = RoundCandidates::where('student_id','=',$userProfile->id)->get(['round_id']);
            if(!$rounds){
                return response()->json(['success'=>true, 'question_sets'=>$question_sets],$this->successStatus);
            }
            $this->out->writeln('Rounds: '.$rounds. 'user id: '.$user->id);
            foreach ($rounds as $round){
                $round_id = $round->round_id;
                $this->out->writeln('Round Id: '.$round_id);
                $question_set = QuestionSet::with('rounds')
                    ->where('round_id','=',$round_id)
                    ->first();
                if(!$question_set){
                    continue;
                }
                if(QuestionSetAnswer::where('question_set_id','=',$question_set->id)
                    ->where('profile_id','=',$userProfile->id)
                    ->exists()
                ){
                    $this->out->writeln('Attended status: 1>'.$question_set->id);
                    $question_set['submitted']=1;
                }else{
                    $question_set['submitted']=0;
                    $this->out->writeln('Attended status: 0>'.$question_set->id);
                }
                if(QuestionSetCandidate::where('question_set_id','=',$question_set->id)
                    ->where('profile_id','=',$userProfile->id)
                    ->exists()
                ){
                    $this->out->writeln('Attended status: 1>'.$question_set->id);
                    $question_set['attended']=1;
                }else{
                    $question_set['attended']=0;
                    $this->out->writeln('Attended status: 0>'.$question_set->id);
                }
                array_push($question_sets,$question_set);
            }
            Log::channel("ac_info")->info(__CLASS__."@".__FUNCTION__."# Successfully fetched student assessments.");
            return response()->json(['success'=>true,'question_sets'=>$question_sets],$this->successStatus);
        }catch (\Exception $e){
            Log::channel("ac_error")->info(__CLASS__."@".__FUNCTION__."# Unable to fetch student-assessment! error: ".$e->getMessage());
            return response()->json(['success'=>false, 'message'=>"Fetching Assessment Failed!", "error"=>$e->getMessage()], $this->failedStatus);
        }
    }
}
