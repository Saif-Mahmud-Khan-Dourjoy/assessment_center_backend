<?php

namespace App\Http\Controllers\API\Question;

use App\Http\Controllers\Controller;
use App\QuestionCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QuestionCategoryController extends Controller
{
    public $successStatus = 200;
    public $failedStatus = 500;
    public $invalidStatus = 400;
    function __construct()
    {
        //$this->middleware('api_permission:question-category-list|question-category-create|question-category-edit|question-category-delete', ['only' => ['index','show']]);
        $this->middleware('api_permission:question-category-create', ['only' => ['store']]);
        $this->middleware('api_permission:question-category-edit', ['only' => ['update']]);
        $this->middleware('api_permission:question-category-delete', ['only' => ['destroy']]);
    }
    /**
     * Display a listing of the resource.
     *
     * @return JsonResponse
     */
    public function index()
    {
        $question_categories = QuestionCategory::all();
        return response()->json(['success' => true, 'question_categories' => $question_categories], $this-> successStatus);
    }


    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request)
    {
        request()->validate([
            'name' => 'required|unique:question_categories',
        ]);
        $input = $request->all();
        $data = [
            'name' => $input['name'],
            'parents_id' => 0,
            'layer' => 0,
            'description' => $input['description'],
        ];
        $question_category = QuestionCategory::create($data);
        if( $question_category )
            return response()->json(['success' => true, 'question_category' => $question_category], $this->successStatus);
        else
            return response()->json(['success' => false, 'message' => 'Question category added fail'], $this->failedStatus);
    }


    /**
     * Display the specified resource.
     *
     * @param $id
     * @return JsonResponse
     */
    public function show($id)
    {
        $question_category = QuestionCategory::find($id);
        if ( !$question_category )
            return response()->json(['success' => false, 'message' => 'Question category not found'], $this->invalidStatus);
        else
            return response()->json(['success' => true, 'question_category' => $question_category], $this->successStatus);
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
        $question_category = QuestionCategory::find($id);
        request()->validate([
            'name' => 'required|unique:question_categories,name,'.$id,
        ]);
        $input = $request->all();
        $data = [
            'name' => $input['name'],
            'parents_id' => 0,
            'layer' => 0,
            'description' => $input['description'],
        ];
        $question_category = $question_category->update($data);
        if( $question_category )
            return response()->json(['success' => true, 'message' => 'Question category update successfully'], $this->successStatus);
        else
            return response()->json(['success' => false, 'message' => 'Question category update failed'], $this->failedStatus);
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
        $question_category = QuestionCategory::find($id);
        if ( !$question_category )
            return response()->json(['success' => false, 'message' => 'Question category not found'], $this->invalidStatus);

        if ( $question_category->delete() )
            return response()->json(['success' => true, 'message' => 'Question category deleted'], $this->successStatus);
        else
            return response()->json(['success' => false, 'message' => 'Question category can not be deleted'], $this->failedStatus);

    }
}
