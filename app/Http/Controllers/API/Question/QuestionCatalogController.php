<?php

namespace App\Http\Controllers\API\Question;

use App\Http\Controllers\Controller;
use App\Institute;
use App\QuestionCatalog;
use App\QuestionCatalogDetail;
use App\UserProfile;
// use Illuminate\Contracts\Cache\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Question;
// use Illuminate\Support\Facades\Cache;

class QuestionCatalogController extends Controller
{
    public $successStatus = 200;
    public $failedStatus = 500;
    public $invalidStatus = 400;
    function __construct()
    {
        //        $this->middleware('api_permission:question-list|question-create|question-edit|question-delete', ['only' => ['index','show']]);
        //        $this->middleware('api_permission:question-create', ['only' => ['store']]);
        //        $this->middleware('api_permission:question-edit', ['only' => ['update']]);
        //        $this->middleware('api_permission:question-delete', ['only' => ['destroy']]);
        $this->out = new \Symfony\Component\Console\Output\ConsoleOutput();
    }

    public function index()
    {

        $user = Auth::user();
        $userProfile = UserProfile::where('user_id', $user->id)->first();



        if ($userProfile->institute_id) {
            // $question_catalogs = QuestionCatalog::with(['question_catalog_details' => function ($question_catalog_details) {
            //     $question_catalog_details->with(['question'])->get();
            // }])
            //     ->where('institute_id', '=', $userProfile->institute_id)
            //     ->get();

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

            // $question_catalogs = DB::table('question_catalogs')
            //     ->leftjoin('question_catalog_details', 'question_catalogs.id', '=', 'question_catalog_details.question_catalog_id')
            //     ->leftjoin('questions', 'question_catalog_details.question_id', '=', 'questions.id')
            //     ->where('question_catalogs.institute_id', '=', $userProfile->institute_id)
            //     ->get();

            // $question_catalogs = QuestionCatalogDetail::with('question')->get();


            Log::channel("ac_info")->info(__CLASS__ . "@" . __FUNCTION__ . "# Returning all the catalog for inst-id: " . $user->institute_id);
            return response()->json(['success' => true, 'question_catalog' => $question_catalogs], $this->successStatus);
        }
        Log::channel("ac_info")->info(__CLASS__ . "@" . __FUNCTION__ . "# No catalog Found!");
        return response()->json(['success' => true, 'question_catalog' => []], $this->successStatus);
    }




    public function store(Request $request)
    {

        // return "Hello";
        // exit();


        request()->validate([
            'title' => 'required|unique:question_catalogs,title',
        ]);
        $input = $request->all();

        $user = Auth::user();
        $userProfile = UserProfile::where('user_id', $user->id)->first();

        $institute_id = NULL;
        $institute = NULL;
        if ($userProfile->institute_id) {
            $institute_id = $userProfile->institute_id;
        }
        if ($institute_id != NULL) {
            $institute_name = Institute::where('id', $institute_id)->first();
            $institute = $institute_name->name;
        }
        // return $institute_name;
        $questionCatalogData = [
            'title' => $input['title'],
            'type' => (!empty($_POST["type"])) ? $input['type'] : NULL,
            'institute' => $institute,
            'institute_id' => $institute_id,
            'total_question' => $input['total_question'],
            'privacy' => (!empty($_POST["privacy"])) ? $input['privacy'] : 0,
            'status' => (!empty($_POST["status"])) ? $input['status'] : 1,
            'approved_by' => $userProfile->id,
            'created_by' =>  $user->id,
            'updated_by' => $user->id,
        ];



        $question_catalog = QuestionCatalog::create($questionCatalogData);

        $question_id = explode(',', $input['question_id']);

        for ($i = 0; $i < count($question_id); $i++) {
            $questionCatalogOptionData = [
                'question_catalog_id' => $question_catalog->id,
                'question_id' => $question_id[$i],
            ];
            QuestionCatalogDetail::create($questionCatalogOptionData);
            Question::find($question_id[$i])->increment('no_of_used');
        }

        $catalog = QuestionCatalog::with(['question_catalog_details'])->where('id', $question_catalog->id)->get();
        Log::info($catalog);
        if (!$catalog)
            throw new \Exception("Question-calatog not found!");
        Log::channel("ac_info")->info(__CLASS__ . "@" . __FUNCTION__ . "# Storing Catalog successful.");
        return response()->json(['success' => true, 'question_set' => $catalog], $this->successStatus);
    }

    public function show($id)
    {
        try {
            Log::channel("ac_info")->info(__CLASS__ . "@" . __FUNCTION__ . "# Fetching Question-catalog.");
            $this->out->writeln('Fetching Question catalog with all questions, question-catalog id: ' . $id);
            $userProfile = UserProfile::where('user_id', '=', Auth::id())->first();
            $question_catalog = QuestionCatalog::inRandomOrder()->with(['question_catalog_details'])
                ->where('id', $id)
                ->get();

            if (sizeof($question_catalog) < 1)
                return response()->json(['success' => false, 'message' => 'Question catalog not found'], $this->invalidStatus);
            $i = 0;
            $question_catalog_details = json_decode($question_catalog[0]->question_catalog_details, true);

            shuffle($question_catalog_details);
            // return  $question_catalog_details;
            foreach ($question_catalog[0]->question_catalog_details as $question_catalog_detail) {
                $this->out->writeln('Question catalog details: ' . $question_catalog_detail);
                $this->out->writeln('Question ID: ' . $question_catalog_detail->question_id);
                $question = Question::with(['question_details', 'question_answer', 'question_tag'])
                    ->where('id', $question_catalog_detail->question_id)
                    ->get();
                $question_catalog[0]->question_catalog_details[$i++]['question'] = $question;
            }

            // return $question_catalog[0]->question_catalog_details[0]['question'];

            Log::channel("ac_info")->info(__CLASS__ . "@" . __FUNCTION__ . "# Fetching Question-catalog successful.");
            return response()->json(['success' => true, 'question_catalog' => $question_catalog], $this->successStatus);
        } catch (\Exception $e) {
            Log::channel("ac_error")->info(__CLASS__ . "@" . __FUNCTION__ . "# Unable to fetch Question-catalog! error: " . $e->getMessage());
            return response()->json(['success' => false, "message" => "Unable to show Catalog!", "error" => $e->getMessage()], $this->failedStatus);
        }
    }

    public function destroy($id)
    {
        try {

            $question_catalog = QuestionCatalog::find($id);
            if (!$question_catalog)
                throw new \Exception("Question-catalog not found!");
            if ($question_catalog->delete())
                Log::channel("ac_info")->info(__CLASS__ . "@" . __FUNCTION__ . "# Successfully deleted Question-catalog: $id");
            return response()->json(['success' => true, 'message' => 'Question catalog deleted'], $this->successStatus);
        } catch (\Exception $e) {
            Log::channel("ac_error")->info(__CLASS__ . "@" . __FUNCTION__ . "# Unable to delete question-catalog: $id! error: " . $e->getMessage());
            return response()->json(['success' => false, "message" => "Question-catalog Deletion unsuccessful!", "error" => $e->getMessage()], $this->failedStatus);
        }
    }

    public function update(Request $request, $id)
    {

        try {

            $request->validate([
                'title' => 'required|unique:question_catalogs,title,' . $id,
            ]);
            $input = $request->all();

            $user = Auth::user();
            $userProfile = UserProfile::where('user_id', $user->id)->first();

            $institute_id = NULL;
            $institute = NULL;
            if ($userProfile->institute_id) {
                $institute_id = $userProfile->institute_id;
            }
            if ($institute_id != NULL) {
                $institute_name = Institute::where('id', $institute_id)->first();
                $institute = $institute_name->name;
            }
            // return $institute_name;
            $questionCatalogData = [
                'title' => $input['title'],
                'type' => (!empty($_POST["type"])) ? $input['type'] : NULL,
                'institute' => $institute,
                'institute_id' => $institute_id,
                'total_question' => $input['total_question'],
                'privacy' => (!empty($_POST["privacy"])) ? $input['privacy'] : 0,
                'status' => (!empty($_POST["status"])) ? $input['status'] : 1,
                'approved_by' => $userProfile->id,
                'created_by' =>  $user->id,
                'updated_by' => $user->id,
            ];

            $question_id = explode(',', $input['question_id']);

            DB::beginTransaction();
            try {
                $questioncatalog = QuestionCatalog::find($id);
                // if( ! UserAcademicHistory::where(['profile_id' => $input['profile_id'], 'check_status' => $input['check_status']])->first() )
                // UserAcademicHistory::where('profile_id', $input['profile_id'])->delete();
                $questionCatalog_status =  $questioncatalog->update($questionCatalogData);
                if (!$questionCatalog_status)
                    throw new \Exception("Question-Catalog Update failed!");
                QuestionCatalogDetail::where(['question_catalog_id' => $id])->delete();                                           // Remove question set details
                for ($i = 0; $i < count($question_id); $i++) {                                                            // Add question set detail
                    $questionOptionData = [
                        'question_catalog_id' => $questioncatalog->id,
                        'question_id' => $question_id[$i],
                    ];
                    QuestionCatalogDetail::create($questionOptionData);
                    Question::find($question_id[$i])->increment('no_of_used');                                              // Increment question total no of use
                }
            } catch (\Exception $inner) {
                DB::rollback();
                $this->out->writeln("Question-catalog update failed! error: " . $inner->getMessage());
                Log::channel("ac_error")->info(__CLASS__ . "@" . __FUNCTION__ . "# Unable to upgrade Catalog! error: " . $inner->getMessage());
                return response()->json(['success' => false, "message" => "Question-catalog Update is unsuccessful!", "error" => "Sequence of any transaction is failed & rollback everything! coz: " . $inner->getMessage()], $this->failedStatus);
            }
            Db::commit();
            $question_catalogs = QuestionCatalog::with(['question_catalog_details'])->where('id', $questioncatalog->id)->get();
            // Cache::forget($this->cacheKey . $id);
            Log::channel("ac_info")->info(__CLASS__ . "@" . __FUNCTION__ . "# Catalog Upgrading successful.");
            return response()->json(['success' => true, 'question_catalog' => $question_catalogs], $this->successStatus);
        } catch (\Exception $e) {
            $this->out->writeln("Question-catalog update is unsuccessful! error: " . $e->getMessage());
            Log::channel("ac_error")->info(__CLASS__ . "@" . __FUNCTION__ . "# Unable to upgrade Catalog! error: " . $e->getMessage());
            return response()->json(['success' => false, "message" => "Question-catalog Update is unsuccessful!", "error" => $e->getMessage()], $this->failedStatus);
        }
    }
}
