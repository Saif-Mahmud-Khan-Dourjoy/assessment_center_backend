<?php

namespace App\Http\Controllers\API\Filter;

use App\Http\Controllers\Controller;
use App\Question;
use App\QuestionCatalog;
use App\QuestionCatalogDetail;
use App\QuestionCategoryTag;
use App\UserProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class QuestionCatalogFilterController extends Controller
{
    public $successStatus = 200;
    public $failedStatus = 500;
    public $invalidStatus = 400;
    public function filterUsingTag(Request $request)
    {
        // Log::info($request);
        // exit();
        if ($request->tag_id) {
            $question_id = QuestionCategoryTag::select('question_id')->distinct()->whereIn('category_id', $request->tag_id)->get();

            //  $catalog_id = QuestionCatalogDetail::select('question_catalog_id', DB::raw('count(*) as total'))->groupBy('question_catalog_id')->get();
            $catalog_id = QuestionCatalogDetail::select('question_catalog_id')->whereIn('question_id', $question_id)->groupBy('question_catalog_id')->get();

            if ($request->institute_id != NULL) {

                $user = Auth::user();
                $userProfile = UserProfile::where('user_id', $user->id)->first();
                $question_catalogs = QuestionCatalog::with(['question_catalog_details'])
                    ->where('institute_id', '=', $userProfile->institute_id)
                    ->whereIn('id', $catalog_id)->get();
            } else {

                $question_catalogs = QuestionCatalog::with(['question_catalog_details'])
                    ->whereIn('id', $catalog_id)->get();
            }

            for ($i = 0; $i < count($question_catalogs); $i++) {
                $question_catalog_details = $question_catalogs[$i]->question_catalog_details;

                for ($j = 0; $j < count($question_catalog_details); $j++) {
                    $question_id = $question_catalogs[$i]->question_catalog_details[$j]->question_id;
                    $question = Question::with(['question_details', 'question_answer','question_tag', 'question_tag.category'])

                        ->where('id', $question_id)
                        ->get();
                    $question_catalogs[$i]->question_catalog_details[$j]['question'] = $question;
                }
            }
        } elseif ($request->institute_id != NULL) {
            $user = Auth::user();
            $userProfile = UserProfile::where('user_id', $user->id)->first();
            $question_catalogs = QuestionCatalog::with(['question_catalog_details'])
                ->where('institute_id', '=', $userProfile->institute_id)
                ->get();

            for ($i = 0; $i < count($question_catalogs); $i++) {
                $question_catalog_details = $question_catalogs[$i]->question_catalog_details;

                for ($j = 0; $j < count($question_catalog_details); $j++) {
                    $question_id = $question_catalogs[$i]->question_catalog_details[$j]->question_id;
                    $question = Question::with(['question_details', 'question_answer','question_tag', 'question_tag.category'])

                        ->where('id', $question_id)
                        ->get();
                    $question_catalogs[$i]->question_catalog_details[$j]['question'] = $question;
                }
            }
        } else {
            $question_catalogs = QuestionCatalog::with(['question_catalog_details'])->get();
            for ($i = 0; $i < count($question_catalogs); $i++) {
                $question_catalog_details = $question_catalogs[$i]->question_catalog_details;

                for ($j = 0; $j < count($question_catalog_details); $j++) {
                    $question_id = $question_catalogs[$i]->question_catalog_details[$j]->question_id;
                    $question = Question::with(['question_details', 'question_answer', 'question_tag', 'question_tag.category'])

                        ->where('id', $question_id)
                        ->get();
                    $question_catalogs[$i]->question_catalog_details[$j]['question'] = $question;
                }
            }
        }


        if ($question_catalogs) {
            return response()->json(['success' => true, 'question_catalogs' => $question_catalogs], $this->successStatus);
        } else {
            return response()->json(['success' => true, 'question_catalogs' => []], $this->successStatus);
        }
    }
}
