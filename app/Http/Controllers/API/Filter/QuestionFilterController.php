<?php

namespace App\Http\Controllers\API\Filter;

use App\Http\Controllers\Controller;
use App\Question;
use App\QuestionCatalogDetail;
use App\QuestionCategoryTag;
use Illuminate\Http\Request;

class QuestionFilterController extends Controller
{
    public $successStatus = 200;
    public $failedStatus = 500;
    public $invalidStatus = 400;
    public function filterUsingTag(Request $request)
    {

        $questions = QuestionCategoryTag::whereIn('category_id', $request->tag_id)->get();
        // return $questions;

        $all_questions = [];

        $i = 0;
        foreach ($questions as $question) {
            $question = Question::with(['question_details', 'question_answer', 'question_tag'])

                ->where('id', $question->question_id)
                ->get();
            $all_questions[$i] = $question;
            $i++;
        }




        if ($all_questions) {
            return response()->json(['success' => true, 'questions' => $all_questions], $this->successStatus);
        } else {
            return response()->json(['success' => true, 'questions' => []], $this->successStatus);
        }
    }
}
