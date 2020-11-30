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
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class QuestionSetController extends Controller
{
    public $successStatus = 200;
    public $failedStatus = 500;
    public $invalidStatus = 400;
    function __construct()
    {
        /*$this->middleware('api_permission:question-set-list|question-set-create|question-set-edit|question-set-delete', ['only' => ['index','show']]);
        $this->middleware('api_permission:question-set-create', ['only' => ['store']]);
        $this->middleware('api_permission:question-set-edit', ['only' => ['update']]);
        $this->middleware('api_permission:question-set-delete', ['only' => ['destroy']]);*/
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
        if($userProfile->institute_id){
            $question_sets = QuestionSet::with(['question_set_details'])
                ->where('privacy', '=', 0)
                ->orWhere('privacy', '=', 1)
                ->where('institute_id', '=', $userProfile->institute_id)
                ->orWhere('privacy', '=', 2)
                ->where('created_by', '=', $userProfile->id)
                ->orWhere('created_by', '=', $userProfile->id)
                ->get();
        }else{
            $question_sets = QuestionSet::with(['question_set_details'])
                ->where('privacy', '=', 0)
                ->orWhere('privacy', '=', 2)
                ->where('created_by', '=', $userProfile->id)
                ->orWhere('created_by', '=', $userProfile->id)
                ->get();
        }
        return response()->json(['success' => true, 'question_set' => $question_sets], $this-> successStatus);
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

        // Add question set
        $questionData = [
            'title' => $input['title'],
            'type' => $input['type'],
            'institute' => (!empty($_POST["institute"])) ? $input['institute'] : '',
            'institute_id' => $institute_id,
            'assessment_time' => $input['assessment_time'],
            'total_question' => $input['total_question'],
            'total_mark' => $input['total_mark'],
            'status' => $input['status'],
            'privacy' => $privacy,
            'created_by' => $userProfile->id,//Profile ID
            'approved_by' => $userProfile->id,//Profile ID
        ];
        $question = QuestionSet::create($questionData);

        // Add question detail
        $questionOptionData = [];
        $question_id = explode( ',', $input['question_id']);
        $mark = explode( ',', $input['mark']);
        $partial_marking_status = explode( ',', $input['partial_marking_status']);
        for($i = 0; $i < count($question_id); $i++){
            $questionOptionData = [
                'question_set_id' => $question->id,
                'question_id' => $question_id[$i],
                'mark' => $mark[$i],
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
     * Display the specified resource.
     *
     * @param $id
     * @return JsonResponse
     */
    public function show($id)
    {
        $question = QuestionSet::with(['question_set_details'])
            ->where('id', $id)
            ->get();
        //->with(['question_details', 'question_answer', 'question_tag']);

        if ( !$question )
            return response()->json(['success' => false, 'message' => 'Question set not found'], $this->invalidStatus);
        else
            return response()->json(['success' => true, 'question_set' => $question], $this->successStatus);
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
        $question = Question::find($id);
        request()->validate([
            'name' => 'required|unique:questions,name,'.$id,
        ]);
        $question = $question->update($request->all());
        if( $question )
            return response()->json(['success' => true, 'message' => 'Question update successfully'], $this->successStatus);
        else
            return response()->json(['success' => false, 'message' => 'Question update failed'], $this->failedStatus);
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
}
