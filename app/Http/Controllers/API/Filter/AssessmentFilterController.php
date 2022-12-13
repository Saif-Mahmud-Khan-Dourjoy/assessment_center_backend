<?php

namespace App\Http\Controllers\API\Filter;

use App\Http\Controllers\Controller;
use App\QuestionSet;
use App\UserProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class AssessmentFilterController extends Controller
{
    public $successStatus = 200;
    public $failedStatus = 500;
    public $invalidStatus = 400;

    public function assessmentFilter(Request $request)
    {
        // return $request;
        $today = Carbon::now()->toDateTimeString();
        $user = Auth::user();
        $userProfile = UserProfile::where('user_id', $user->id)->first();
        $question_sets = QuestionSet::with(['question_set_details']);
        if (isset($request->start_time)) {
            $start_time = Carbon::parse($request->start_time)->toDateString();
            $end_time = Carbon::parse($request->end_time)->toDateString();

            $question_sets = $question_sets->where(function ($query) use ($start_time, $end_time) {
                $query->where(function ($q1) use ($start_time, $end_time) {
                    $q1->whereDate('start_time', '>=', $start_time)->whereDate('start_time', '<=', $end_time);
                });
                $query->orWhere(function ($q2) use ($start_time, $end_time) {
                    $q2->whereDate('end_time', '>=', $start_time)->whereDate('end_time', '<=', $end_time);
                });
            });
        }
        if (isset($request->search_value) && $request->search_value != NULL && Str::length($request->search_value) > 2) {
            $question_sets = $question_sets->where('title', 'like', '%' . $request->search_value . '%');
        }

        if (isset($request->institute_privacy_type)) {
            if ($request->institute_privacy_type == 0) {
                $question_sets = $question_sets->where('privacy', 0);
            }
            if ($request->institute_privacy_type == 1) {
                $question_sets = $question_sets->where(function ($query) use ($userProfile) {
                    $query->where('institute_id', $userProfile->institute_id)
                        ->orWhere('privacy', 0);
                });
            }
            if ($request->institute_privacy_type == 2) {
                $question_sets = $question_sets->where('institute_id', '=', $userProfile->institute_id);
            }
            if ($request->institute_privacy_type == 3) {
                $question_sets = $question_sets->where('institute_id', '=', $userProfile->institute_id)
                    ->where('created_by', $user->id);
            }
        }
        if (isset($request->assessment_type)) {
            if ($request->assessment_type == 1) {
                $question_sets = $question_sets->where(function ($query) use ($today) {
                    $query->where('start_time', '<=', $today)
                        ->where('end_time', '>=', $today);
                });
            }
            if ($request->assessment_type == 2) {
                $question_sets = $question_sets->where('start_time', '>', $today);
            }
            if ($request->assessment_type == 3) {
                $question_sets = $question_sets->where('end_time', '<', $today);
            }
        }

        $question_sets = $question_sets->get();

        if ($question_sets) {
            return response()->json(['success' => true, 'question_sets' => $question_sets], $this->successStatus);
        } else {
            return response()->json(['success' => true, 'question_sets' => []], $this->successStatus);
        }
    }
}
