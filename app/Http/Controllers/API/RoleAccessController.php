<?php

namespace App\Http\Controllers\API;


use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\User;
use Validator;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;


class RoleAccessController extends Controller {
    public $successStatus = 200;
    public $failedStatus = 500;
    public $invalidStatus = 400;
    public function __construct(){
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @param Role $role
     * @return JsonResponse
     */
    public function role_has_permissions(Request $request, Role $role){
        $validator = Validator::make($request->all(), [
            'permission_id' => 'required|exists:permissions,id'
        ]);
        if($validator->fails()){
            return response()->json(['error' => $validator->errors()], 401);
        }
        $permission = Permission:: find($request['permission_id'])->firstOrFail();
        if($role->givePermissionTo($permission)){
            return response()->json(['success' => $role], $this-> successStatus);
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @param Role $role
     * @return JsonResponse
     */
    public function assign_user_to_role(Request $request, Role $role){
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id'
        ]);
        if($validator->fails()){
            return response()->json(['error' => $validator->errors()], 401);
        }
        $user = User:: find($request['user_id'])->firstOrFail();
        if($user->assignRole($role)){
            return response()->json(['success' => $user], $this-> successStatus);
        }
    }
}

