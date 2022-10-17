<?php

namespace App\Http\Controllers\API\Filter;

use App\Http\Controllers\Controller;
use App\Question;
use App\QuestionCatalogDetail;
use App\QuestionCategoryTag;
use App\UserProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;



class QuestionFilterController extends Controller
{
    public $successStatus = 200;
    public $failedStatus = 500;
    public $invalidStatus = 400;
    public function filterUsingTag(Request $request)
    {


        if ($request->tag_id) {
            // $questions = QuestionCategoryTag::whereIn('category_id', $request->tag_id)->get()->unique('question_id');
            $questions = QuestionCategoryTag::select('question_id')->distinct()->whereIn('category_id', $request->tag_id)->get();

            // return $questions;
            $questions_id = [];
            for ($i = 0; $i < count($questions); $i++) {
                array_push($questions_id, $questions[$i]->question_id);
            }
            // return $questions_id;
            if ($request->institute_id != NULL) {
                $user = Auth::user();
                $userProfile = UserProfile::where('user_id', $user->id)->first();
                $questionAll = Question::with(['question_details', 'question_answer', 'question_tag','question_tag.category'])
                    ->where('institute_id', '=', $userProfile->institute_id)
                    ->whereIn('id', $questions_id)
                    ->get();
            } else {
                $questionAll = Question::with(['question_details', 'question_answer','question_tag', 'question_tag.category'])
                    ->whereIn('id', $questions_id)
                    ->get();
            }
        } elseif ($request->institute_id != NULL) {
            $user = Auth::user();
            $userProfile = UserProfile::where('user_id', $user->id)->first();
            $questionAll = Question::with(['question_details', 'question_answer','question_tag', 'question_tag.category'])
                ->where('institute_id', '=', $userProfile->institute_id)
                ->get();
        } else {
            $questionAll = Question::with(['question_details', 'question_answer','question_tag', 'question_tag.category'])
                ->get();
        }

        if ($questionAll) {
            return response()->json(['success' => true, 'questions' => $questionAll], $this->successStatus);
        } else {
            return response()->json(['success' => true, 'questions' => []], $this->successStatus);
        }
    }
}
