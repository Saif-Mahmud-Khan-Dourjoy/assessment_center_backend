<?php

namespace App\Http\Controllers\API\Question;

use App\Http\Controllers\Controller;
use App\Question;
use App\QuestionAnswer;
use App\QuestionDetail;
use App\QuestionSet;
use App\QuestionSetAnswer;
use App\QuestionSetAnswerDetail;
use App\QuestionSetDetail;
use App\RoundCandidates;
use App\Student;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QuestionSetAnswerController extends Controller
{
    public $successStatus = 200;
    public $failedStatus = 500;
    public $invalidStatus = 400;
    function __construct()
    {
        /*$this->middleware('api_permission:question-set-answer-list|question-set-answer-create|question-set-answer-edit|question-set-answer-delete', ['only' => ['index','show']]);
        $this->middleware('api_permission:question-set-answer-create', ['only' => ['store']]);
        $this->middleware('api_permission:question-set-answer-edit', ['only' => ['update']]);
        $this->middleware('api_permission:question-set-answer-delete', ['only' => ['destroy']]);*/
    }


    /**
     * Display a listing of the resource.
     *
     * @return JsonResponse
     */
    public function index()
    {
        $question_set_answers = QuestionSetAnswer::with(['question_set_answer_details'])->get();
        return response()->json(['success' => true, 'question_set_answer' => $question_set_answers], $this-> successStatus);
    }


    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request)
    {
        request()->validate([
            'question_set_id' => 'required',
            'profile_id' => 'required',
        ]);
        $input = $request->all();

        // Add question set
        $student = Student::where('profile_id', $input['profile_id'])->first();
        if( ! $student ){
            return response()->json(['success' => true, 'message' => 'Student not found'], $this->successStatus);
        }
        $questionAnswerData = [
            'question_set_id' => $input['question_set_id'],
            'profile_id' => $input['profile_id'],
            'time_taken' => $input['time_taken'],
            'total_mark' => (!empty($_POST["total_mark"])) ? $input['total_mark'] : 0,
        ];
        $question_answer = QuestionSetAnswer::create($questionAnswerData);
        // Increment total assessment attend
        Student::where('id', $student->id)->increment('total_complete_assessment');

        // Add answer detail
        $questionAnswerDetailsData = [];
        $question_id = explode( '|', $input['question_id']);
        $answer = explode( '|', $input['answer']);
        $mark_given = (!empty($_POST["mark"])) ? explode( '|', $input['mark']) : '';
        $t_mark = 0;
        for($i = 0; $i < count($question_id); $i++){
            $get_answer = QuestionAnswer::where('question_id', $question_id[$i])->first();
            $get_mark = QuestionSetDetail::where(['question_id' => $question_id[$i], 'question_set_id' => $input['question_set_id']])->first();
            $answer_data = explode(',', $get_answer->answer);
            $given_answer = explode(',', $answer[$i]);
            if( sizeof(array_diff($answer_data, $given_answer)) == 0 ){
                $mark = $get_mark->mark;
            }
            else{
                $mark = 0;
            }
            $questionAnswerDetailsData = [
                'question_set_answer_id' => $question_answer->id,
                'question_id' => $question_id[$i],
                'answer' => $answer[$i],
                'mark' => $mark,
            ];
            QuestionSetAnswerDetail::create($questionAnswerDetailsData);
            $t_mark = $t_mark+$mark;
        }
        $question_answer_data = QuestionSetAnswer::find($question_answer->id);
        $question_answer_data->update(['total_mark' => $t_mark]);

        $question_sets_answer = QuestionSetAnswer::with(['question_set_answer_details'])->where('id', $question_answer->id)->get();
        if( $question_answer )
            return response()->json(['success' => true, 'question_set_answer' => $question_sets_answer], $this->successStatus);
        else
            return response()->json(['success' => false, 'message' => 'Question set answer added fail'], $this->failedStatus);
    }


    /**
     * Display the specified resource.
     *
     * @param $id
     * @return JsonResponse
     */
    public function show($id)
    {
        $question_answer = QuestionSetAnswer::with(['question_set_answer_details'])
            ->where('id', $id)
            ->get();

        if ( !$question_answer )
            return response()->json(['success' => false, 'message' => 'Question set answer not found'], $this->invalidStatus);
        else
            return response()->json(['success' => true, 'question_set_answer' => $question_answer], $this->successStatus);
    }


    /**
     * Display the specified resource.
     *
     * @param $id
     * @return JsonResponse
     */
    public function getAllStudent($id)
    {
        $question_answer = QuestionSetAnswer::with(['user_profile', 'question_set', 'question_set_answer_details'])
            ->where('question_set_id', $id)
            ->get();

        if ( !$question_answer )
            return response()->json(['success' => false, 'message' => 'Question set answer not found'], $this->invalidStatus);
        else
            return response()->json(['success' => true, 'question_set_answer' => $question_answer], $this->successStatus);
    }



}
