<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public $successStatus = 200;
    public $failedStatus = 500;
    public $invalidStatus = 400;

    public function __construct(){
        //
    }


    /**
     * Display a listing of the resource.
     *
     * @return JsonResponse
     */
    public function index()
    {
        $total_institute = DB::table('institutes')->count();
        $total_user = DB::table('users')->count();
        $total_assessment = DB::table('question_sets')->count();
        $total_attendant = DB::table('question_set_answers')->count();
        $data = [
            'total_institute' => $total_institute,
            'total_user' => $total_user,
            'total_assessment' => $total_assessment,
            'total_attendant' => $total_attendant
        ];
        return response()->json(['success' => true, 'data' => $data], $this-> successStatus);
    }
}
