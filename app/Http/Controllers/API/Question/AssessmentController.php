<?php

namespace App\Http\Controllers\Api\Question;

use App\Http\Controllers\Controller;
use App\QuestionSet;
use App\QuestionSetCandidate;
use Illuminate\Http\Request;
use Carbon\Carbon;

class AssessmentController extends Controller
{
    public function checkValidAssessment(Request $request)
    {
        $assessment_id = $request['assessment_id'];
        $profile_id = $request['profile_id'];
        // $current_time = date('Y-m-d H:i:s');
        $current_time = Carbon::now()->toDateTimeString();

        $assessment = QuestionSet::where('id', $assessment_id)->first();
        $assessment_candidate = QuestionSetCandidate::where('question_set_id', $assessment_id)->where('profile_id', $profile_id)->first();
        if ($assessment_candidate->attended == 1) {
            return response()->json(["success" => false, "message" => "You Have Already Attended in This Assessment"]);
        }

        return response()->json(["success" => true, "current_time" => $current_time, "start_time" => $assessment->start_time, "end_time" => $assessment->end_time]);
    }
}
