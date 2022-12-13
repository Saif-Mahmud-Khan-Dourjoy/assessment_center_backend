<?php

namespace App\Http\Controllers\API\Filter;

use App\Http\Controllers\Controller;
use App\Question;
use App\QuestionCatalogDetail;
use App\QuestionCategoryTag;
use App\UserProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;



class QuestionFilterController extends Controller
{
    public $successStatus = 200;
    public $failedStatus = 500;
    public $invalidStatus = 400;
    // public function filterUsingTag(Request $request)
    // {


    //     if ($request->tag_id) {
    //         // $questions = QuestionCategoryTag::whereIn('category_id', $request->tag_id)->get()->unique('question_id');
    //         $questions = QuestionCategoryTag::select('question_id')->distinct()->whereIn('category_id', $request->tag_id)->get();

    //         // return $questions;
    //         $questions_id = [];
    //         for ($i = 0; $i < count($questions); $i++) {
    //             array_push($questions_id, $questions[$i]->question_id);
    //         }
    //         // return $questions_id;
    //         if ($request->institute_id != NULL) {
    //             $user = Auth::user();
    //             $userProfile = UserProfile::where('user_id', $user->id)->first();
    //             $questionAll = Question::with(['question_details', 'question_answer', 'question_tag','question_tag.category'])
    //                 ->where('institute_id', '=', $userProfile->institute_id)
    //                 ->whereIn('id', $questions_id)
    //                 ->get();
    //         } else {
    //             $questionAll = Question::with(['question_details', 'question_answer','question_tag', 'question_tag.category'])
    //                 ->whereIn('id', $questions_id)
    //                 ->get();
    //         }
    //     } elseif ($request->institute_id != NULL) {
    //         $user = Auth::user();
    //         $userProfile = UserProfile::where('user_id', $user->id)->first();
    //         $questionAll = Question::with(['question_details', 'question_answer','question_tag', 'question_tag.category'])
    //             ->where('institute_id', '=', $userProfile->institute_id)
    //             ->get();
    //     } else {
    //         $questionAll = Question::with(['question_details', 'question_answer','question_tag', 'question_tag.category'])
    //             ->get();
    //     }

    //     if ($questionAll) {
    //         return response()->json(['success' => true, 'questions' => $questionAll], $this->successStatus);
    //     } else {
    //         return response()->json(['success' => true, 'questions' => []], $this->successStatus);
    //     }
    // }
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
            if (!empty($request->institute_id)) {
                $user = Auth::user();
                $userProfile = UserProfile::where('user_id', $user->id)->first();
                $questionAll = Question::with(['question_details', 'question_answer', 'question_tag', 'question_tag.category'])->whereIn('id', $questions_id);
                if ($request->searchValue != NULL && Str::length($request->searchValue) > 2) {
                    $questionAll = $questionAll->where(function ($query) use ($request) {
                        $query->where('description', 'like', '%' . $request->searchValue . '%')
                            ->orWhere('question_type', 'like', '%' . $request->searchValue . '%')
                            ->orWhere('question_text', 'like', '%' . $request->searchValue . '%');
                    });
                }
                if ($request->institute_id == 1) {

                    $questionAll = $questionAll->where(function ($query) use ($userProfile) {
                        $query->where('institute_id', $userProfile->institute_id)
                            ->orWhere('privacy', 0);
                    })
                        ->get();
                } elseif ($request->institute_id == 2) {

                    $questionAll = $questionAll->where('institute_id', '=', $userProfile->institute_id)

                        ->get();
                } elseif ($request->institute_id == 3) {

                    $questionAll = $questionAll->where('institute_id', '=', $userProfile->institute_id)
                        ->where('profile_id', $userProfile->id)

                        ->get();
                }
                // else {
                //     $questionAll = Question::with(['question_details', 'question_answer', 'question_tag', 'question_tag.category'])
                //         ->where('privacy', 0)
                //         ->whereIn('id', $questions_id)
                //         ->get();
                // }
            } else {
                $questionAll = Question::with(['question_details', 'question_answer', 'question_tag', 'question_tag.category'])->whereIn('id', $questions_id);
                if ($request->searchValue != NULL && Str::length($request->searchValue) > 2) {
                    $questionAll = $questionAll->where(function ($query) use ($request) {
                        $query->where('description', 'like', '%' . $request->searchValue . '%')
                            ->orWhere('question_type', 'like', '%' . $request->searchValue . '%')
                            ->orWhere('question_text', 'like', '%' . $request->searchValue . '%');
                    });
                }
                $questionAll = $questionAll->where('privacy', 0)
                    ->whereIn('id', $questions_id)
                    ->get();
            }
        } elseif (!empty($request->institute_id)) {
            $user = Auth::user();
            $userProfile = UserProfile::where('user_id', $user->id)->first();

            $questionAll = Question::with(['question_details', 'question_answer', 'question_tag', 'question_tag.category']);
            if ($request->searchValue != NULL && Str::length($request->searchValue) > 2) {
                $questionAll = $questionAll->where(function ($query) use ($request) {
                    $query->where('description', 'like', '%' . $request->searchValue . '%')
                        ->orWhere('question_type', 'like', '%' . $request->searchValue . '%')
                        ->orWhere('question_text', 'like', '%' . $request->searchValue . '%');
                });
            }

            if ($request->institute_id == 1) {

                $questionAll = $questionAll->where(function ($query) use ($userProfile) {
                    $query->where('institute_id', '=', $userProfile->institute_id)
                        ->orWhere('privacy', 0);
                })->get();
            } elseif ($request->institute_id == 2) {

                $questionAll = $questionAll->where('institute_id', '=', $userProfile->institute_id)
                    ->get();
            } elseif ($request->institute_id == 3) {

                $questionAll = $questionAll->where('institute_id', '=', $userProfile->institute_id)
                    ->where('profile_id', $userProfile->id)
                    ->get();
            }
            // else {
            //     $questionAll = Question::with(['question_details', 'question_answer', 'question_tag', 'question_tag.category'])
            //         ->where('privacy', 0)
            //         ->get();
            // }
        } else {
            $questionAll = Question::with(['question_details', 'question_answer', 'question_tag', 'question_tag.category']);
            if ($request->searchValue != NULL && Str::length($request->searchValue) > 2) {
                $questionAll = $questionAll->where(function ($query) use ($request) {
                    $query->where('description', 'like', '%' . $request->searchValue . '%')
                        ->orWhere('question_type', 'like', '%' . $request->searchValue . '%')
                        ->orWhere('question_text', 'like', '%' . $request->searchValue . '%');
                });
            }

            $questionAll = $questionAll->where('privacy', 0)
                ->get();
        }

        if ($questionAll) {
            return response()->json(['success' => true, 'questions' => $questionAll], $this->successStatus);
        } else {
            return response()->json(['success' => true, 'questions' => []], $this->successStatus);
        }
    }
}
