<?php

namespace App\Http\Controllers\API\Question;

use App\Contributor;
use App\PermissionList;
use App\Question;
use App\QuestionAnswer;
use App\QuestionCategory;
use App\QuestionCategoryTag;
use App\QuestionDetail;
use App\User;
use App\UserProfile;
use http\Env\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\TestModel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Intervention\Image\Facades\Image;

class QuestionController extends Controller
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


    /**
     * Display a listing of the resource.
     *
     * @return JsonResponse
     */
    public function index()
    {
        $user = Auth::user();
        $userProfile = UserProfile::where('user_id', $user->id)->first();
        $user = auth()->user();
        $permissions = $user->getAllPermissions();
        if ($user->can('super-admin')) {
            $questions = Question::with(['question_details', 'question_answer', 'question_tag', 'question_tag.category'])->get();
            return response()->json(['success' => true, 'questions' => $questions], $this->successStatus);
        }
        if ($userProfile->institute_id) {
            $questions = Question::with(['question_details', 'question_answer', 'question_tag', 'question_tag.category'])
                ->where('institute_id', '=', $userProfile->institute_id)
                ->get();
            return response()->json(['success' => true, 'questions' => $questions], $this->successStatus);
        }
        return response()->json(['success' => true, 'questions' => []], $this->successStatus);
        //        if($userProfile->institute_id){
        //            $questions = Question::with(['question_details', 'question_answer', 'question_tag'])
        //                ->where('privacy', '=', 0)
        //                ->orWhere('privacy', '=', 1)
        //                ->where('institute_id', '=', $userProfile->institute_id)
        //                ->orWhere('privacy', '=', 2)
        //                ->where('profile_id', '=', $userProfile->id)
        //                ->orWhere('profile_id', '=', $userProfile->id)
        //                ->get();
        //        }else{
        //            $questions = Question::with(['question_details', 'question_answer', 'question_tag'])
        //                ->where('privacy', '=', 0)
        //                ->orWhere('privacy', '=', 2)
        //                ->where('profile_id', '=', $userProfile->id)
        //                ->orWhere('profile_id', '=', $userProfile->id)
        //                ->get();
        //        }
        //        return response()->json(['success' => true, 'questions' => $questions], $this-> successStatus);
    }

    /**
     * Store a newly created resource in storage.
     * Developed specially for vue
     *
     * @param Request $request
     * @return JsonResponse
     */

    public function store(Request $request)
    {

        // Log::info($request);

        // exit();



        request()->validate([
            'question_text' => 'required',
        ]);
        $input = $request->all();

        // $this->out->writeln('data: ' . $input);

        $user = Auth::user();
        $userProfile = UserProfile::where('user_id', $user->id)->first();


        $contributor = Contributor::where('profile_id', $userProfile->id)->first();

        // Check contributor
        if (!$contributor)
            return response()->json(['success' => false, 'message' => 'Contributor not found'], $this->invalidStatus);

        // Check question category
        // $questionCategory = QuestionCategory::find($input['category_id']);
        // if (!$questionCategory)
        //     return response()->json(['success' => false, 'message' => 'Question Category not found'], $this->invalidStatus);

        // Check answer
        if (!$input['answer'])
            return response()->json(['success' => false, 'message' => 'No answer selected'], $this->invalidStatus);

        // $user = Auth::user();
        // $userProfile = UserProfile::where('user_id', $user->id)->first();
        // Log::info($userProfile);
        // exit();
        $institute_id = NULL;
        if ($userProfile->institute_id) {
            $institute_id = $userProfile->institute_id;
        }
        // Log::info($institute_id);
        // exit();
        // Add question
        $questionData = [
            'institute_id' => $institute_id,
            //'profile_id' => $input['profile_id'],
            'profile_id' => $userProfile->id,
            'category_id' => $input['category_id'],
            'privacy' => (!empty($_POST["privacy"])) ? $input['privacy'] : 0,
            'publish_status' => $input['publish_status'],
            'question_type' => $input['question_type'],
            'question_text' => $input['question_text'],
            'description' => $input['description'],
            'option_type' => $input['option_type'],
            'no_of_option' => $input['no_of_option'],
            'no_of_answer' => $input['no_of_answer'],
            'no_of_used' => $input['no_of_used'],
            'no_of_comments' => $input['no_of_comments'],
            'average_rating' => $input['average_rating'],
            'img' => $input['img'],
            'active' => $input['active'],
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ];

        // $this->out->writeln($questionData);
        // dd($questionData);
        // Log::info($questionData);
        // exit();
        $question = Question::create($questionData);

        // Add question options
        $questionOptionData = [];
        $serial_no_data = explode('|', $input['serial_no']);
        // $option_data = explode('|', $input['option']);
        $option_data = $input['option'];
        // $description_data = explode('|', $input['description']);
        $description_data = $input['description'];

        $image_data = explode('|', $input['img']);
        for ($i = 0; $i < $input['no_of_option']; $i++) {
            $questionOptionData = [
                'question_id' => $question->id,
                'serial_no' => $serial_no_data[$i],
                'option' => $option_data[$i],
                //'description' => $description_data[$i],
                //'img' => $image_data[$i],
            ];
            QuestionDetail::create($questionOptionData);
        }

        // Add question answer
        $no_of_ans = $request['no_of_answer'];
        // for ($i = 0; $i < $no_of_ans; $i++) {
        $questionAnswerData = [
            'question_id' => $question->id,
            'answer' => $input['answer'],
            'reference' => $input['reference'],
        ];
        // $this->writeln(questionAnswerData);
        QuestionAnswer::create($questionAnswerData);
        // }


        // Add question tags
        $tag_data = explode(',',  $input['tags']);
        if ($tag_data) {
            for ($i = 0; $i < count($tag_data); $i++) {
                $questionTagData = [
                    'question_id' => $question->id,
                    'category_id' => $tag_data[$i],
                ];
                QuestionCategoryTag::create($questionTagData);
            }

            Log::info($questionTagData);
        }

        // Increment contributor total no of question
        Contributor::find($contributor->id)->increment('total_question');

        if ($question)
            return response()->json(['success' => true, 'question' => $question], $this->successStatus);
        else
            return response()->json(['success' => false, 'message' => 'Question added fail'], $this->failedStatus);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store_before_vue_integrations(Request $request)
    {

        Log::info($request['description']);
        // exit();

        request()->validate([
            'question_text' => 'required',
        ]);
        $input = $request->all();

        // $this->out->writeln('data: ' . $input);


        $contributor = Contributor::where('profile_id', $input['profile_id'])->first();

        // Check contributor
        if (!$contributor)
            return response()->json(['success' => false, 'message' => 'Contributor not found'], $this->invalidStatus);

        // Check question category
        $questionCategory = QuestionCategory::find($input['category_id']);
        if (!$questionCategory)
            return response()->json(['success' => false, 'message' => 'Question Category not found'], $this->invalidStatus);

        // Check answer
        if (!$input['answer'])
            return response()->json(['success' => false, 'message' => 'No answer selected'], $this->invalidStatus);

        $user = Auth::user();
        $userProfile = UserProfile::where('user_id', $user->id)->first();

        $institute_id = NULL;
        if ($userProfile->institute_id) {
            $institute_id = $userProfile->institute_id;
        }

        // Add question
        $questionData = [
            'institute_id' => $institute_id,
            //'profile_id' => $input['profile_id'],
            'profile_id' => $userProfile->id,
            'category_id' => $input['category_id'],
            'privacy' => (!empty($input['privacy'])) ? $input['privacy'] : 0,
            'publish_status' => $input['publish_status'],
            'question_type' => $input['question_type'],
            'question_text' => $input['question_text'],
            'description' => $input['description'],
            'option_type' => $input['option_type'],
            'no_of_option' => $input['no_of_option'],
            'no_of_answer' => $input['no_of_answer'],
            'no_of_used' => $input['no_of_used'],
            'no_of_comments' => $input['no_of_comments'],
            'average_rating' => $input['average_rating'],
            'img' => $input['img'],
            'active' => $input['active'],
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ];

        // $this->out->writeln($questionData);
        // dd($questionData);
        $question = Question::create($questionData);

        // Add question options
        $questionOptionData = [];
        $serial_no_data = explode('|', $input['serial_no']);
        // $option_data = explode('|', $input['option']);
        $option_data = $input['option'];
        // $description_data = explode('|', $input['description']);
        $description_data = $input['description'];

        $image_data = explode('|', $input['img']);
        for ($i = 0; $i < $input['no_of_option']; $i++) {
            $questionOptionData = [
                'question_id' => $question->id,
                'serial_no' => $serial_no_data[$i],
                'option' => $option_data[$i],
                //'description' => $description_data[$i],
                //'img' => $image_data[$i],
            ];
            QuestionDetail::create($questionOptionData);
        }

        // Add question answer
        // $no_of_ans = $request['no_of_answer'];
        // for ($i = 0; $i < $no_of_ans; $i++) {
        $questionAnswerData = [
            'question_id' => $question->id,
            'answer' => $input['answer'],
            'reference' => $input['reference'],
        ];
        // $this->writeln(questionAnswerData);
        QuestionAnswer::create($questionAnswerData);
        // }


        // Add question tags
        $tag_data = (!empty($_POST["tags"])) ? explode(',',  $input['tags']) : '';
        if ($tag_data) {
            for ($i = 0; $i < count($tag_data); $i++) {
                $questionTagData = [
                    'question_id' => $question->id,
                    'category_id' => $tag_data[$i],
                ];
                QuestionCategoryTag::create($questionTagData);
            }
        }

        // Increment contributor total no of question
        Contributor::find($contributor->id)->increment('total_question');

        if ($question)
            return response()->json(['success' => true, 'question' => $question], $this->successStatus);
        else
            return response()->json(['success' => false, 'message' => 'Question added fail'], $this->failedStatus);
    }


    /**
     * Display the specified resource.
     *
     * @param $id
     * @return JsonResponse
     */
    public function show($id)
    {
        $user = Auth::user();
        $userProfile = UserProfile::where('user_id', $user->id)->first();
        $question = Question::with(['question_details', 'question_answer', 'question_tag', 'question_tag.category'])
            ->where('id', $id)
            ->get();

        if (!$question)
            return response()->json(['success' => false, 'message' => 'Question not found'], $this->invalidStatus);
        else
            return response()->json(['success' => true, 'question' => $question], $this->successStatus);
    }


    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param $id
     * @return JsonResponse
     */
    public function update(Request $request, $id)
    {
        $question = Question::find($id);



        $input = $request->all();
        $input['updated_by'] = Auth::id();
        $question = $question->update($input);


        // $contributor = Contributor::where('profile_id', $input['profile_id'])->first();

        // // Check contributor
        // if (!$contributor)
        //     return response()->json(['success' => false, 'message' => 'Contributor not found'], $this->invalidStatus);

        $questionDetails = QuestionDetail::where('question_id', '=', $id)->delete();



        $questionOptionData = [];
        $serial_no_data = explode('|', $input['serial_no']);
        // $option_data = explode('|', $input['option']);
        $option_data = $input['option'];
        // $description_data = explode('|', $input['description']);
        $description_data = $input['description'];

        $image_data = explode('|', $input['img']);
        for ($i = 0; $i < $input['no_of_option']; $i++) {
            $questionOptionData = [
                'question_id' => $id,
                'serial_no' => $serial_no_data[$i],
                'option' => $option_data[$i],
                //'description' => $description_data[$i],
                //'img' => $image_data[$i],
            ];
            QuestionDetail::create($questionOptionData);
        }

        $questionAns = QuestionAnswer::where('question_id', '=', $id)->delete();
        // Add question answer
        $no_of_ans = $request['no_of_answer'];
        // for ($i = 0; $i < $no_of_ans; $i++) {
        $questionAnswerData = [
            'question_id' => $id,
            'answer' => $input['answer'],
            'reference' => $input['reference'],
        ];
        // $this->writeln(questionAnswerData);
        QuestionAnswer::create($questionAnswerData);
        // }

        $questionAns = QuestionCategoryTag::where('question_id', '=', $id)->delete();
        // Add question tags
        $tag_data = explode(',',  $input['tags']);
        if ($tag_data) {
            for ($i = 0; $i < count($tag_data); $i++) {
                $questionTagData = [
                    'question_id' => $id,
                    'category_id' => $tag_data[$i],
                ];
                QuestionCategoryTag::create($questionTagData);
            }

            Log::info($questionTagData);
        }

        // Increment contributor total no of question
        // Contributor::find($contributor->id)->increment('total_question');

        if ($question)
            return response()->json(['success' => true, 'question' => $question], $this->successStatus);
        else
            return response()->json(['success' => false, 'message' => 'Question update fail'], $this->failedStatus);






        // if ($question)
        //     return response()->json(['success' => true, 'message' => 'Question update successfully'], $this->successStatus);
        // else
        //     return response()->json(['success' => false, 'message' => 'Question update failed'], $this->failedStatus);
    }


    /**
     * Remove the specified resource from storage.
     *
     * @param $id
     * @return JsonResponse
     * @throws Exception
     */
    public function destroy($id)
    {
        $question = Question::find($id);
        if (!$question)
            return response()->json(['success' => false, 'message' => 'Question not found'], $this->invalidStatus);

        if ($question->delete())
            return response()->json(['success' => true, 'message' => 'Question deleted'], $this->successStatus);
        else
            return response()->json(['success' => false, 'message' => 'Question can not be deleted'], $this->failedStatus);
    }

    public function test(Request $request)
    {
        log::info("Started");

        $array = array(
            'description' => $request['description'],
        );
        $test = TestModel::insert($array);
        if ($test) {
            log::info("Successfull");
        }
    }

    // public resize_img($file){
    //     $max_width = 852;
    //     $max_height = 480;

    //     if($file->extension()=="jpg"){
    //         dd($file);
    //     }

    //     return 0;
    // }

    public function imageUploadPost(Request $request)
    {
        $this->out->writeln("Uplaoding image.....");
        // return response()->json(['success' => 'You have successfully upload image']);
        // header('Access-Control-Allow-Origin', '*');
        // header('Access-Control-Allow-Methods', 'GET,POST,PUT,PATCH,DELETE,OPTIONS');
        // header('Access-Control-Allow-Headers', 'Content-Type,Authorization');
        Log::info($request);
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);


        // image conversion
        $max_height = 480;
        $max_width = 852;
        list($width, $height) = getimagesize($request->image);
        $imageName = time() . '.' . $request->image->extension();
        $image = $request->file('image');

        if ($width > $max_width || $height > $max_height) {
            $destinationPath = public_path('images');
            $img = Image::make($image->path());
            $img->resize($max_width, $max_height, function ($constraint) {
                $constraint->aspectRatio();
            })->save($destinationPath . '/' . $imageName);
        } else {

            $request->image->move(public_path('images'), $imageName);
        }


        $app_url = env('APP_URL');

        return response()->json(['success' => 'You have successfully upload image', 'img_url' => 'images/' . $imageName, 'base_url' => $app_url]);
    }

    // public function imageUploadPost(Request $request)
    // {

    //     $request->validate([
    //         'image' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
    //     ]);

    //     $imageName = time() . '.' . $request->image->extension();

    //     $request->image->move(public_path('images'), $imageName);

    //     $app_url = env('APP_URL');

    //     return response()->json(['success' => 'You have successfully upload image', 'img_url' => 'images/' . $imageName, 'base_url' => $app_url]);
    // }


    public function get_question()
    {
        $user = Auth::user();
        $userProfile = UserProfile::where('user_id', $user->id)->first();
        $user = auth()->user();
        $permissions = $user->getAllPermissions();

        $question = Question::select('questions.id', 'questions.question_type', 'questions.description', 'question_categories.name as category_name')
            ->leftJoin('question_categories', 'questions.category_id', '=', 'question_categories.id')
            ->get();
        return response()->json(['success' => true, 'questions' => $question], $this->successStatus);
    }
}
