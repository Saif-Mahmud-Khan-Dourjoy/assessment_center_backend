<?php

namespace App\Http\Controllers\API\Question;

use App\Contributor;
use App\Http\Controllers\Controller;
use App\Question;
use App\QuestionDetail;
use App\QuestionSet;
use App\QuestionSetDetail;
use App\QuestionSetAnswer;
use App\QuestionSetAnswerDetail;
use App\UserProfile;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class QuestionSetController extends Controller
{
    public $successStatus = 200;
    public $failedStatus = 500;
    public $invalidStatus = 400;

    public $out;
    function __construct()
    {
        /*$this->middleware('api_permission:question-set-list|question-set-create|question-set-edit|question-set-delete', ['only' => ['index','show']]);
        $this->middleware('api_permission:question-set-create', ['only' => ['store']]);
        $this->middleware('api_permission:question-set-edit', ['only' => ['update']]);
        $this->middleware('api_permission:question-set-delete', ['only' => ['destroy']]);*/
        $this->out = new \Symfony\Component\Console\Output\ConsoleOutput();
    }


    /**
     * Display a listing of the resource.
     *
     * @return JsonResponse
     */
    public function index()
    {
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


    public function assessmentTimeValidator($start_time, $end_time, $duration){
        //calculate start and end time in terms of duration
        $this->out->writeln('Validating time....');
        $startTime = Carbon::parse($start_time);
        $endTime = Carbon::parse($end_time);
        $this->out->writeln('Start time: '.$startTime." ****** End time: ".$endTime);
        if ($startTime->addMinutes($duration)>$endTime){
            $this->out->writeln('Invalid time: ');
            return false;
        }
        return true;

    }


    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request)
    {
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
            'created_by' => $userProfile->id,//Profile ID
            'approved_by' => $userProfile->id,//Profile ID
            'round_id'=>$input['round_id'],
        ];
        $question = QuestionSet::create($questionData);

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
        if( $question )
            return response()->json(['success' => true, 'question_set' => $question_sets], $this->successStatus);
        else
            return response()->json(['success' => false, 'message' => 'Question set added fail'], $this->failedStatus);
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
        $user = Auth::user();
        $userProfile = UserProfile::where('user_id', $user->id)->first();
        request()->validate([
            'title' => 'required',
        ]);
        $input = $request->all();
        //dd($input);
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
            'each_question_time' => (!empty('each_question_time') || !is_null($input['each_question_time'])? $input['each_question_time']: 0),
            'total_question' => $input['total_question'],
            'total_mark' => $input['total_mark'],
            'status' => $input['status'],
            'privacy' => $privacy,
            'created_by' => $userProfile->id,//Profile ID
            'approved_by' => $userProfile->id,//Profile ID
            'round_id'=>$input['round_id'],
        ];
        $questionset = QuestionSet::find($id);
        // if( ! UserAcademicHistory::where(['profile_id' => $input['profile_id'], 'check_status' => $input['check_status']])->first() )
        // UserAcademicHistory::where('profile_id', $input['profile_id'])->delete();
        $questionset_status =  $questionset->update($questionsetData);
        if($questionset_status){
        //remove question set details
        $questionsetdetail = QuestionSetDetail::where(['question_set_id'=>$id])->delete();

        // Add question set detail
        $questionOptionData = [];
        $question_id = explode( ',', $input['question_id']);
        $mark = explode( ',', $input['mark']);
        $partial_marking_status = explode( ',', $input['partial_marking_status']);
        if(!(is_null($input['question_time']) && $questionsetData['each_question_time']==0)){
            $question_time = explode(',',$input['question_time']);
        }
        for($i = 0; $i < count($question_id); $i++){
            $questionOptionData = [
                'question_set_id' => $questionset->id,
                'question_id' => $question_id[$i],
                'mark' => $mark[$i],
                'question_time'=>($questionsetData['each_question_time']==0?0:$question_time[$i]),
                'partial_marking_status' => $partial_marking_status[$i],
            ];
            QuestionSetDetail::create($questionOptionData);
            // Increment question total no of use
            Question::find($question_id[$i])->increment('no_of_used');
        }
        $question_sets = QuestionSet::with(['question_set_details'])->where('id', $questionset->id)->get();
        if( $questionset )
            return response()->json(['success' => true, 'question_set' => $question_sets], $this->successStatus);
        else
            return response()->json(['success' => false, 'message' => 'Question set update fail'], $this->failedStatus);
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
        $this->out->writeln('Fetching Question set with all questions, question-set id: '.$id);
        $question_set = QuestionSet::with(['question_set_details'])
            ->where('id', $id)
            ->get();
        //->with(['question_details', 'question_answer', 'question_tag']);
        $i = 0;
        foreach ($question_set[0]->question_set_details as $question_detail){
            $this->out-> writeln('Question set details: '.$question_detail);
            $this->out->writeln('Question ID: '.$question_detail->question_id);
            $question = Question::with(['question_details', 'question_answer', 'question_tag'])
                ->where('id', $question_detail->question_id)
                ->get();
            $question_set[0]->question_set_details[$i++]['question']=$question;
//            $this->out->writeln('Question: '.$question);

        }
//        [0]->question_set_details[0]->question_id
//        return response()->json(['success' => true, 'question_set' => $question_set], $this->successStatus);
        if ( !$question )
            return response()->json(['success' => false, 'message' => 'Question set not found'], $this->invalidStatus);
        else
            return response()->json(['success' => true, 'question_set' => $question_set], $this->successStatus);
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
        $question_set = QuestionSet::find($id);
        if ( !$question_set )
            return response()->json(['success' => false, 'message' => 'Question set not found'], $this->invalidStatus);

        if ( $question_set->delete() )
            return response()->json(['success' => true, 'message' => 'Question set deleted'], $this->successStatus);
        else
            return response()->json(['success' => false, 'message' => 'Question set can not be deleted'], $this->failedStatus);

    }

    public function status($id){
        $this->out->write('Publish or Un-Publish status of Assessments!');
        $question_st = QuestionSet::find($id);
        if(!$question_st){
            return response()->json(['success'=>false, 'message'=>'Question Set Not Found, id: '.$id],$this->invalidStatus);
        }
        $question_st->status = ($question_st['status']==0 ? 1:0);
        if($question_st->save()){
            return response()->json(['success'=>true, 'question_set'=>$question_st],$this->successStatus);
        }
        return response()->json(['success'=>false, 'message'=>'Failed to change question set publish status'],$this->failedStatus);

    }
}
