<?php

namespace App\Http\Controllers\API\Setup;

use App\Http\Controllers\Controller;
use App\RoleSetup;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

class RoleSetupController extends Controller
{
    public $successStatus = 200;
    public $failedStatus = 500;
    public $invalidStatus = 400;
    function __construct()
    {
        $this->middleware('api_permission:role-setup', ['only' => ['index', 'store']]);
    }


    /**
     * Display a listing of the resource.
     *
     * @return JsonResponse
     */
    public function index()
    {
        $role_setup = RoleSetup::all();
        return response()->json(['success' => true, 'role_setup' => $role_setup], $this-> successStatus);
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
            'contributor_role_id' => 'required',
            'new_register_user_role_id' => 'required',
        ]);
        $input = $request->all();

        if( !Role::find($input['contributor_role_id']) ){
            return response()->json(['success' => false, 'message' => 'Contributor Role not found'], $this->failedStatus);
        }
        if( !Role::find($input['new_register_user_role_id']) ){
            return response()->json(['success' => false, 'message' => 'Register User Role not found'], $this->failedStatus);
        }

        $role_setup = RoleSetup::first();
        $data = [
            'contributor_role_id' => $input['contributor_role_id'],
            'student_role_id' => $input['student_role_id'],
            'new_register_user_role_id' => $input['new_register_user_role_id'],
        ];
        if( $role_setup ){
            $role_setup->update($data);
        }
        else{
            $role_setup = RoleSetup::create($data);
        }

        if( $role_setup )
            return response()->json(['success' => true, 'role_setup' => $role_setup], $this->successStatus);
        else
            return response()->json(['success' => false, 'message' => 'Setup Role added fail'], $this->failedStatus);
    }
}
