<?php

namespace App\Http\Controllers\API\Question;

use App\Http\Controllers\Controller;
use App\Mail\WelcomeMail;
use App\Question;
use App\QuestionAnswer;
use App\QuestionDetail;
use App\QuestionSet;
use App\QuestionSetAnswer;
use App\QuestionSetAnswerDetail;
use App\QuestionSetDetail;
use App\Round;
use App\RoundCandidates;
use App\Student;
use App\UserProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use PDF;
use Illuminate\Support\Facades\Mail;

class QuestionSetAnswerController extends Controller
{
    public $successStatus = 200;
    public $failedStatus = 500;
    public $invalidStatus = 400;

    public $out;
    function __construct()
    {
        $this->middleware('api_permission:question-set-answer-list|question-set-answer-create|question-set-answer-edit|question-set-answer-delete', ['only' => ['index','show']]);
        $this->middleware('api_permission:question-set-answer-create', ['only' => ['store']]);
        $this->middleware('api_permission:question-set-answer-edit', ['only' => ['update']]);
        $this->middleware('api_permission:question-set-answer-delete', ['only' => ['destroy']]);
        $this->out = new \Symfony\Component\Console\Output\ConsoleOutput();
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
     * student ranking based on the mark and time-taken during exam
     * @param $question_answers
     * @return $question_answers
     */
    public function studentRank($question_answers){
        $position = 1;
        $previous_mark = 0;
        $previous_time = 0;
        for($i=0;$i<sizeof($question_answers)-1;$i++){
            for($j=$i+1;$j<sizeof($question_answers);$j++){
                if($question_answers[$i]->total_mark<$question_answers[$j]->total_mark){
                    $temp = $question_answers[$i];
                    $question_answers[$i]=$question_answers[$j];
                    $question_answers[$j]=$temp;
                }else if($question_answers[$i]->total_mark==$question_answers[$j]->total_mark  && $question_answers[$i]->time_taken>$question_answers[$j]->time_taken){
                    $this->out->writeln('swap by time');
                    $temp = $question_answers[$i];
                    $question_answers[$i]=$question_answers[$j];
                    $question_answers[$j]=$temp;
                }
            }
            $question_answers[$i]['rank']=$i+1;
            if($i>0 && $question_answers[$i-1]->total_mark==$question_answers[$i]->total_mark && $question_answers[$i-1]->time_taken==$question_answers[$i]->time_taken){
                $this->out->writeln('Position must be same!');
                $question_answers[$i]['position']=$position-1;
            }else{
                $question_answers[$i]['position']=$position++;
            }
        }
        $question_answers[$i]['rank']=$i+1;
        if($question_answers[$i-1]->total_mark==$question_answers[$i]->total_mark && $question_answers[$i-1]->time_taken==$question_answers[$i]->time_taken){
            $question_answers[$i]['position']=$position-1;
        }else{
            $question_answers[$i]['position']=$position++;
        }
        foreach ($question_answers as $qs){
            $this->out->writeln('question set ans id: '.$qs->id);
        }
        return $question_answers;
    }

    public function studentPromotion($question_set, $mark, $student){
        $questionSet = QuestionSet::find($question_set);
        $round = Round::find($questionSet->round_id);
        $this->out->writeln('Round: '.$round);
    }

    /**
     * Display the specified resource.
     *
     * @param $id
     * @return JsonResponse
     */
    public function getAllStudent($id)
    {
        $this->out->writeln('Fetching students attended!');
//        $question_answer = QuestionSetAnswer::with(['user_profile', 'question_set', 'question_set_answer_details'])
//            ->where('question_set_id', $id)
//            ->orderByDesc('total_mark')
//            ->get();
        $question_answer = QuestionSetAnswer::with(['user_profile','question_set_answer_details'])
            ->where('question_set_id', $id)
            ->orderByDesc('total_mark')
            ->get();
        $questionSet = QuestionSet::find($id);
        $round = Round::find($questionSet->round_id);
        $this->out->writeln($round);
        $i=0;
        $total_mark=$questionSet->total_mark;
        $question_answer = $this->studentRank($question_answer);
        foreach($question_answer as $question_ans){
            $mark_achieved = $question_ans->total_mark;
            $student = $question_ans->user_profile->id;
            $mark_percentage = ($mark_achieved/$total_mark)*100;
            $question_answer[$i]['percentage']=$mark_percentage;
            if($round->passing_criteria=='pass' && $mark_percentage>=$round->number){
                $this->out->writeln('Student is promoted, i: '.$i);
                $this->out->writeln('Student id: '.$student);
                $this->out->writeln('Total Mark: '.$mark_achieved);
                $question_answer[$i]['promoted']=1;
//                $question_answer[$i]['rank']=$i+1;
            }else if($round->passing_criteria=='sort' && $i<$round->number){
                $this->out->writeln('Student is promoted, i: '.$i);
                $this->out->writeln('Student id: '.$student);
                $this->out->writeln('Total Mark: '.$mark_achieved);
                $question_answer[$i]['promoted']=1;
//                $question_answer[$i]['rank']=$i+1;
            }else{
                $this->out->writeln('Student is not promoted, i: '.$i);
                $this->out->writeln('Student id: '.$student);
                $this->out->writeln('Total Mark: '.$mark_achieved);
                $question_answer[$i]['promoted']=0;
//                $question_answer[$i]['rank']=$i+1;
            }

            $i++;
        }
        if ( !$question_answer )
            return response()->json(['success' => false, 'message' => 'Question set answer not found'], $this->invalidStatus);
        else
            return response()->json(['success' => true, 'question_set'=>$questionSet , 'question_set_answer' => $question_answer], $this->successStatus);
    }
    /**
     * Generate PDF and Send email.
     *
     * @param Request $request
     * @return mixed
     */
    public function getCertificate(Request $request)
    {
        $profile = UserProfile::where('id', $request->profile_id);
        $data = [
            'name' => 'Test'
        ];
        $pdf = PDF::loadView('assessment.certificate', $data)->setPaper('a4', 'landscape');
        Storage::put('certificate/1.pdf', $pdf->output());

        mail::to('mohammad.hemayet@neural-semiconductor.com')
            ->cc('hemayet.nirjhoy@gmail.com')
            ->send(new WelcomeMail());

        if ( !$profile )
            return response()->json(['success' => false, 'message' => 'Profile not found'], $this->invalidStatus);
        else
            return response()->json(['success' => true, 'message' => "Certificate generated"], $this->successStatus);
    }

    /**
     * Show a specific Assessment Result to a specific student
     * @param $student-id, $assessment-id
     * @returns Specific assessment belongs to that student
     */
    public function eachStudentAssessment(Request $request){
        request()->validate([
            'profile_id'=>'required',
            'question_set_id'=>'required'
        ]);
        $input = $request->all();
        $this->out->writeln('Each Student Assessment.'.$input['profile_id'].' Assessment id: '.$input['question_set_id']);
        $question_set_answer = QuestionSetAnswer::where('question_set_id','=',$input['question_set_id'])
                                                    ->where('profile_id','=',$input['profile_id'])
                                                    ->first();
        if($question_set_answer){
            return response()->json(['success'=>true, 'question_set_answer'=>$question_set_answer],$this->successStatus);
        }
        return response()->json(['success'=>false, 'message'=>'Assessment not found for this profile'],$this->invalidStatus);
    }


}
