<?php

namespace App\Http\Controllers\API\Filter;

use App\Http\Controllers\Controller;
use App\Question;
use App\QuestionCatalogDetail;
use Illuminate\Http\Request;

class QuestionGetByCatalog extends Controller
{
    public $successStatus = 200;
    public $failedStatus = 500;
    public $invalidStatus = 400;
    public function QuestionByCatalog(Request $request)
    {
        // Log::info($request);
        // exit();
        $question_id = QuestionCatalogDetail::select('question_id')->distinct()->whereIn('question_catalog_id', $request->catalog_id)->get();

        $questions = Question::with(['question_details', 'question_answer', 'question_tag', 'question_tag.category'])

            ->whereIn('id', $question_id)
            ->get();

        

        if ($questions) {
            return response()->json(['success' => true, 'questions' => $questions], $this->successStatus);
        } else {
            return response()->json(['success' => true, 'questions' => []], $this->successStatus);
        }
    }
}
