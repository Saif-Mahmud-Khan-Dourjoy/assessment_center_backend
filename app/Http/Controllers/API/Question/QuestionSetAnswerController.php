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
use http\Env\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use mysql_xdevapi\Exception;
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
//        $this->middleware('api_permission:question-set-answer-list|question-set-answer-create|question-set-answer-edit|question-set-answer-delete', ['only' => ['index','show']]);
//        $this->middleware('api_permission:question-set-answer-create', ['only' => ['store']]);
//        $this->middleware('api_permission:question-set-answer-edit', ['only' => ['update']]);
//        $this->middleware('api_permission:question-set-answer-delete', ['only' => ['destroy']]);
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
    public function store_old(Request $request)
    {
        $this->out->writeln('questions et answer');
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

     /***
     * Store the question set answer
     * @param Request $request
     * @return JsonResponse
     */

    public function store(Request $request)
    {
        $this->out->writeln('Storing questions Set answer');
        Log::channel("ac_info")->info(__CLASS__."@".__FUNCTION__."# Request accepted for question-set storing (assessment-submission).");
        try{
            request()->validate([
                'question_set_id' => 'required',
                'profile_id' => 'required',
                'time_taken'=>'required',
            ]);
            $input = $request->all();
            $student = Student::where('profile_id', $input['profile_id'])->first();
            if( ! $student )
                return response()->json(['success' => true, 'message' => 'Student not found'], $this->successStatus);
            $question_set_answer = QuestionSetAnswer::create([
                'question_set_id'=>$input['question_set_id'],
                'profile_id'=>$input['profile_id'],
                'time_taken'=>$input['time_taken'],
                'total_mark'=>(!empty($_POST["total_mark"])) ? $input['total_mark'] : 0,
            ]);
            $question_id = explode( '|', $input['question_id']);
            $answer = explode( '|', $input['answer']);
            $mark_given = (!empty($_POST["mark"])) ? explode( '|', $input['mark']) : '';
            $total_mark= 0;
            for($i = 0; $i < count($question_id); $i++){
                $get_answer = QuestionAnswer::where('question_id', $question_id[$i])->first();
                $get_mark = QuestionSetDetail::where(['question_id' => $question_id[$i], 'question_set_id' => $input['question_set_id']])->first();
//                $answer_data = explode(',', $get_answer->answer);
                $this->out->writeln("Answer data: ".$get_answer->answer);
                $this->out->writeln("Given Answer data: ".$answer[$i]);
//                $given_answer = explode(',', $answer[$i]);
//                if( sizeof(array_diff($answer_data, $given_answer)) == 0 )
//                    $mark = $get_mark->mark;
//                else
//                    $mark = 0;
                $mark  = $this->haveMark($get_answer->answer, $answer[$i], $get_mark->mark);
                $questionAnswerDetailsData = [
                    'question_id'=>$question_id[$i],
                    'question_set_answer_id'=>$question_set_answer->id,
                    'answer' => $answer[$i],
                    'mark' => $mark,
                ];
                // $this->out->writeln("***************************************");
                QuestionSetAnswerDetail::create($questionAnswerDetailsData);
                $total_mark = $total_mark+$mark;
                // $this->out->writeln("***************************************");
            }
            // $this->out->writeln("***************************************");
            $questionAnswerData=[
                'total_mark'=>$total_mark,
            ];
            $this->out->writeln("***************************************");
            $question_set_answer->update($questionAnswerData);
            $question_set_answer_detail = QuestionSetAnswerDetail::where('question_set_answer_id',$question_set_answer->id)->get();
            $question_set_answer['question_set_answer_details']=$question_set_answer_detail;
            if(!$question_set_answer )
                throw new \Exception("Assessment Submission unsuccessful!");
            Log::channel("ac_info")->info(__CLASS__."@".__FUNCTION__."# Successfully stored question-set (assessment-submitted) with question answers.");
            return response()->json(['success' => true, 'question_set_answer' => $question_set_answer], $this->successStatus);
        }catch(\Exception $e){
            $this->out->writeln("Storing Question set answer is unsuccessful! error: ".$e->getMessage());
            Log::channel("ac_error")->info(__CLASS__."@".__FUNCTION__."# Storing question-set (assessment-submission) unsuccessful! error: ".$e->getMessage());
            return response()->json(['success'=>false, "message"=>"Submitting Assessment Answer is unsuccessful!", "error"=>$e->getMessage()], $this->failedStatus);
        }
    }

    public function haveMark($actual_answer, $given_answer, $mark){
        try {
            $actual_answers = explode(',',$actual_answer);
            $given_answers = explode(',', $given_answer);
            if(sizeof($actual_answers)!=sizeof($given_answers))
                return 0;
            sort($given_answers);
            for($i=0;$i<sizeof($actual_answers);$i++){
                if($actual_answers[$i]!=$given_answers[$i])
                    return 0;
            }
            return $mark;
        }catch (\Exception $e){
            Log::channel("ac_error")->info(__CLASS__."@".__FUNCTION__."# There is a problem calculating Mark! error: ".$e->getMessage());
            return 0;
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
            Log::channel("ac_info")->info(__CLASS__."@".__FUNCTION__."# Request for showing question-set.");
            $question_answer = QuestionSetAnswer::with(['question_set_answer_details'])->where('id', $id)->get();
            if(!$question_answer)
                return response()->json(['success' => false, 'message' => 'Question set answer not found'], $this->invalidStatus);
            Log::channel("ac_info")->info(__CLASS__."@".__FUNCTION__."# Request served successfully for showing question-set.");
            return response()->json(['success' => true, 'question_set_answer' => $question_answer], $this->successStatus);
        }catch(\Exception $e){
            $this->out->writeln("Fetching Question Set Answer is Unsuccessful! error: ".$e->getMessage());
            Log::channel("ac_error")->info(__CLASS__."@".__FUNCTION__."# Showing question-set unsuccessful! error: ".$e->getMessage());
            return response()->json(['success'=>false, "message"=>"Fetching Question Set Answer is unsuccessful!", 'error'=>$e->getMessage()], $this->failedStatus);
        }
    }

    /**
     * Purpose: Get All the student based on a specific Assessment (Question-set)     *
     * @param $id
     * @return JsonResponse
     */
    public function getAllStudent($id)
    {
        $this->out->writeln('Fetching students attended!');
        try{
            Log::channel("ac_info")->info(__CLASS__."@".__FUNCTION__."# Assessment-wise all student fetching.");
            $questionSet = QuestionSet::find($id);
            if(!$questionSet)
                throw new \Exception("Assessment Not Found!");
            $question_answer = QuestionSetAnswer::with(['user_profile','question_set_answer_details'])
                            ->where('question_set_id', $id)
                            ->orderByDesc('total_mark')
                            ->get();
            if(!$question_answer)
                throw new \Exception("No Student Attended for this Assessment!");
            $round = Round::find($questionSet->round_id);
            $i=0;
            $total_mark=$questionSet->total_mark;
            if(sizeof($question_answer)==0){
                return response()->json(['success' => true, 'question_set'=>$questionSet , 'question_set_answer' => $question_answer], $this->successStatus);
            }
            if(sizeof($question_answer)==1){
                $mark_achieved = $question_answer[0]->total_mark;
                $total_mark = $questionSet->total_mark;
                $mark_percentage = ($mark_achieved/$total_mark)*100;
                if($round->passing_criteria=='pass' && $mark_percentage>=$round->number){
                    $this->out->writeln('Total Mark: '.$mark_achieved);
                    $question_answer[$i]['promoted']=1;
                }else if($round->passing_criteria=='sort' && 0<$round->number){
                    $this->out->writeln('Total Mark: '.$mark_achieved);
                    $question_answer[$i]['promoted']=1;
                }else{
                    $this->out->writeln('Total Mark: '.$mark_achieved);
                    $question_answer[$i]['promoted']=0;
                }
                $question_answer[0]['rank']=1;
                $question_answer[0]['position']=1;
                $question_answer[0]['percentage']=$mark_percentage;
                return response()->json(['success' => true, 'question_set'=>$questionSet , 'question_set_answer' => $question_answer], $this->successStatus);
            }
            $question_answer =json_decode($question_answer, true);
            usort($question_answer, function($student1, $student2){
                return ($student1['total_mark'] < $student2['total_mark']) || ($student1['total_mark'] == $student2['total_mark'] && $student1['time_taken'] > $student2['time_taken']);
            });
            $total_student = sizeof($question_answer);
            for($rank=0,$position=0;$rank<$total_student;$rank++){
                $mark_achieved = $question_answer[$rank]['total_mark'];
                $student = $question_answer[$rank]['user_profile']['id'];
                $mark_percentage = ($mark_achieved/$total_mark)*100;
                $question_answer[$rank]['percentage']=$mark_percentage;
                if($round->passing_criteria=='pass' && $mark_percentage>=$round->number){
                    $question_answer[$rank]['promoted']=1;
                }else if($round->passing_criteria=='sort' && $rank<$round->number){
                    $question_answer[$rank]['promoted']=1;
                }else{
                    $question_answer[$rank]['promoted']=0;
                }
                $question_answer[$rank]['rank']=$rank+1;
                $question_answer[$rank]['position']=$position+1;
                if($rank+1<$total_student && $question_answer[$rank]['total_mark'] ==$question_answer[$rank+1]['total_mark'] && $question_answer[$rank]['time_taken']==$question_answer[$rank+1]['time_taken'])
                    continue;
                $position++;
            }
            Log::channel("ac_info")->info(__CLASS__."@".__FUNCTION__."# Assessment-wise all student fetching Successful.");
            return response()->json(['success' => true, 'question_set'=>$questionSet , 'question_set_answer' => $question_answer], $this->successStatus);
        }catch(\Exception $e){
            Log::channel("ac_error")->info(__CLASS__."@".__FUNCTION__."# Assessment-wise all student fetching is unsuccessful! error: ".$e->getMessage());
            return response()->json(['success'=>false, 'message'=>"All Students standing fetching un-successful!", "error"=>$e->getMessage()], $this->failedStatus);
        }
    }

    /***
     * Purpose: Serving Certificate to the student into his dashboard with the rank he/she obtained
     * @param Request $request
     * @return JsonResponse containing rank with certificate
     */

    public function rankCertificate(Request $request){
        request()->validate([
            'question_set_id'=>'required',
            'profile_id'=>'required',
        ]);
        try{
            Log::channel("ac_info")->info(__CLASS__."@".__FUNCTION__."# Ranking student for certificate.");
            $input = $request->all();
            $question_set = QuestionSet::find($input['question_set_id']);
            if(!$question_set || empty($question_set))
                throw new \Exception('No Assessment Found!');
            $question_set_answers = QuestionSetAnswer::with(['user_profile','question_set_answer_details'])
                ->where('question_set_id', $input['question_set_id'])
                ->orderByDesc('total_mark')
                ->get();
            if(sizeof($question_set_answers)<1)
                throw new \Exception("No Question Set Answer found!");
            if(sizeof($question_set_answers)==1){
                if($question_set_answers[0]->profile_id!=$input['profile_id'])
                    throw new \Exception("User may not attended to this assessment!");
                $total_mark = $question_set->total_mark;
                $mark_percentage = ($question_set_answers[0]->total_mark/$total_mark)*100;
                $question_set_answers[0]['rank']=1;
                $question_set_answers[0]['position']=1;
                $question_set_answers[0]['percentage']=$mark_percentage;
                return response()->json(['success' => true, 'question_set'=>$question_set , 'question_set_answer' => $question_set_answers], $this->successStatus);
            }
            $question_set_answers =json_decode($question_set_answers, true);
            usort($question_set_answers, function($student1, $student2){
                return ($student1['total_mark'] < $student2['total_mark']) || ($student1['total_mark'] == $student2['total_mark'] && $student1['time_taken'] > $student2['time_taken']);
            });
            $total_student=sizeof($question_set_answers);
            for($rank=0, $position=0; $rank<$total_student;$rank++){
                if($question_set_answers[$rank]['profile_id']==$input['profile_id']){
                    $question_set_answers[$rank]['position']=$position+1;
                    $question_set_answers[$rank]['rank']=$rank+1;
                    Log::channel("ac_info")->info(__CLASS__."@".__FUNCTION__."# Ranking student with certificate is Successful.");
                    return response()->json(['success'=>true, 'question_set_answer'=>$question_set_answers[$rank]],$this->successStatus);
                }
                if($rank+1<$total_student && $question_set_answers[$rank]['total_mark'] ==$question_set_answers[$rank+1]['total_mark'] && $question_set_answers[$rank]['time_taken']==$question_set_answers[$rank+1]['time_taken'])
                    continue;
                $position++;
            }
            throw new \Exception("User may not attended");
        }catch(\Exception $e){
            $this->out->writeln("Fetching Certificate with rank is unsuccessful!, error: ".$e->getMessage());
            Log::channel("ac_error")->info(__CLASS__."@".__FUNCTION__."# Unable to rank student with certificate! error: ".$e->getMessage());
            return response()->json(['success'=>false, 'message'=>'Fetching Certificate with rank is unsuccessful!', 'error'=>$e->getMessage()],$this->failedStatus);
        }
    }


    /**
     * Generate PDF and Send email.
     * Purpose: For testing, whether the certificate is generating successfully or not
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
     * @param $request
     * @returns Specific assessment belongs to that student
     */
    public function eachStudentAssessment(Request $request): JsonResponse
    {
        try{
            Log::channel("ac_info")->info(__CLASS__."@".__FUNCTION__."# Student-wise all assessment(question-set) fetching.");
            request()->validate([
                'profile_id'=>'required',
                'question_set_id'=>'required'
            ]);
            $input = $request->all();
            $question_set_answer = QuestionSetAnswer::where('question_set_id','=',$input['question_set_id'])
                ->where('profile_id','=',$input['profile_id'])
                ->first();
            if(!$question_set_answer)
                return response()->json(['success'=>false, 'message'=>'Assessment not found for this profile'],$this->invalidStatus);
            Log::channel("ac_info")->info(__CLASS__."@".__FUNCTION__."# Student-wise all assessment fetching Successful.");
            return response()->json(['success'=>true, 'question_set_answer'=>$question_set_answer],$this->successStatus);
        }catch (\Exception $e){
            Log::channel("ac_error")->info(__CLASS__."@".__FUNCTION__."# Unable to fetch all assessment for student! error: ".$e->getMessage());
            return response()->json(['success'=>false, "message"=>"Unable to fetch Each Student Assessments!", "error"=>$e->getMessage()], $this->failedStatus);
        }
    }
}
