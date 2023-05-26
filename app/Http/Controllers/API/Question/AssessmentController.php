<?php

namespace App\Http\Controllers\Api\Question;

use App\User;
use App\Question;
use Carbon\Carbon;
use App\QuestionSet;
use App\QuestionCatalog;
use App\QuestionCategory;
use Illuminate\Http\Request;
use App\QuestionSetCandidate;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class AssessmentController extends Controller
{
    public function checkValidAssessment(Request $request)
    {
        $assessment_id = $request['assessment_id'];
        $profile_id = $request['profile_id'];
        // $current_time = date('Y-m-d H:i:s');
        $current_time = Carbon::now()->toDateTimeString();
        $isValid = QuestionSetCandidate::where('question_set_id', $assessment_id)->where('profile_id', $profile_id)->exists();
        if ($isValid) {
            $assessment = QuestionSet::where('id', $assessment_id)->first();
            $assessment_candidate = QuestionSetCandidate::where('question_set_id', $assessment_id)->where('profile_id', $profile_id)->first();

            if ($assessment_candidate->attended == 1) {
                return response()->json(["success" => false, "message" => "You Have Already Attended in This Assessment"]);
            }

            return response()->json(["success" => true, "current_time" => $current_time, "start_time" => $assessment->start_time, "end_time" => $assessment->end_time]);
        } else {
            return response()->json(["success" => false, "message" => "Not Valid Assessment"]);
        }
    }
    public function assessmentCandidates($id)
    {
        $assessment_candidates = QuestionSetCandidate::with('user_profile')->where('question_set_id', $id)->get();
        if (!$assessment_candidates) {
            return response()->json(['success' => true, 'assessment_candidates' => []]);
        }
        return response()->json(['success' => true, 'assessment_candidates' => $assessment_candidates]);
    }
   
    public function assessmentStats(Request $request){
        $user=Auth::user();
        $total=0;
        $finished=0;
        $ongoing=0;
        $upcoming=0;
        $now = Carbon::now()->format('Y-m-d H:i:s');
        if($request->type){
            if($request->type==1){
                $question_sets=QuestionSet::where('privacy',0)
                          ->orWhere('institute_id',$user->institute_id)
                          ->orWhere('created_by',$user->id)->get();

              }
              if($request->type==2){
                  $question_sets=QuestionSet::Where('institute_id',$user->institute_id)->orWhere('created_by',$user->id)->get();
                }else{
                  $question_sets=QuestionSet::Where('created_by',$user->id)->get();
                }
             foreach($question_sets as $single){
                 $start_time=$single->start_time;
                 $end_time=$single->end_time;
                 if($end_time <= $now ){
                    $finished++;
                 }elseif($end_time>$now && $start_time < $now){
                    $ongoing++;
                 }elseif($start_time > $now){
                   $upcoming++;
                 }

             }
             $total=count($question_sets);

              return response()->json(['success'=>true,'data'=>['total'=>$total,'finished'=>$finished,'ongoing'=>$ongoing,'upcoming'=>$upcoming]]);
        }

        else{
            return response()->json(['success'=>false,'data'=>[]]);
        }
       
    }

    public function questionQuestionSetStats(){
        $user=Auth::user();

        $openSourceQuestion=Question::Where('privacy',0)->get();
        $openSourceQuestionSet=QuestionCatalog::Where('privacy',0)->get();
        $organizationalQuestion=Question::Where('institute_id',$user->institute_id)->orWhere('created_by',$user->id)->get();
        $organizationalQuestionSet=QuestionCatalog::Where('institute_id',$user->institute_id)->orWhere('created_by',$user->id)->get();
        $ownQuestion=Question::Where('created_by',$user->id)->get();
        $ownQuestionSet=QuestionCatalog::Where('created_by',$user->id)->get();

        return response()->json(['success'=>true,'data'=>['publicQuestion'=>count($openSourceQuestion),'publicQuestionSet'=>count($openSourceQuestionSet),'OrganizationalQuestion'=>count($organizationalQuestion),'organizationalQuestionSet'=>count($organizationalQuestionSet),'OwnQuestion'=>count($ownQuestion),'OwnQuestionSet'=>count($ownQuestionSet)]]);
    }
    public function coRecruiters(){
        $user=Auth::user();
        
        $coRecruiter = User::with(['user_profile','roles'])->where('institute_id',$user->institute_id)->get();
       
        return response()->json(['success'=>true,'data'=>$coRecruiter]);
    }
    public function questionNumberByCategory($categoryName=""){
        if($categoryName==""){
            $questionCategory= QuestionCategory::with(['question_category_tag','question_category_tag.question','question_category_tag.question.question_details','question_category_tag.question.question_answer'])->get();
            foreach($questionCategory as $qc){
                $count=count($qc->question_category_tag);
                $qc['count']=$count;
            }
        }else{
            $questionCategory= QuestionCategory::with(['question_category_tag','question_category_tag.question','question_category_tag.question.question_details','question_category_tag.question.question_answer'])->where("name",'like',$categoryName.'%')->get();
            foreach($questionCategory as $qc){
                $count=count($qc->question_category_tag);
                $qc['count']=$count;
            }
        }
         
        return response()->json(['success'=>true,'data'=>$questionCategory]);
    }
}
