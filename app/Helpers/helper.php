<?php

use App\Question;
use App\QuestionCatalog;
use App\UserProfile;
use Illuminate\Support\Facades\Auth;

if (!function_exists('get_catalog')) {

    function get_catalog()
    {
        $user = Auth::user();
        $userProfile = UserProfile::where('user_id', $user->id)->first();

        if ($userProfile->institute_id) {

            $question_catalogs = QuestionCatalog::with(['question_catalog_details'])
                ->where('institute_id', '=', $userProfile->institute_id)
                ->get();

            // return $question_catalogs;

            for ($i = 0; $i < count($question_catalogs); $i++) {
                $question_catalog_details = $question_catalogs[$i]->question_catalog_details;

                for ($j = 0; $j < count($question_catalog_details); $j++) {
                    $question_id = $question_catalogs[$i]->question_catalog_details[$j]->question_id;
                    $question = Question::with(['question_details', 'question_answer', 'question_tag'])

                        ->where('id', $question_id)
                        ->get();
                    $question_catalogs[$i]->question_catalog_details[$j]['question'] = $question;
                }
            }
        }

        return $question_catalogs;
    }
}
