<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public $successStatus = 200;
    public $failedStatus = 500;
    public $invalidStatus = 400;
    function __construct()
    {
        $this->middleware('api_permission:role-list|role-create|role-edit|role-delete', ['only' => ['index','show']]);
        $this->middleware('api_permission:role-create', ['only' => ['store']]);
        $this->middleware('api_permission:role-edit', ['only' => ['update']]);
        $this->middleware('api_permission:role-delete', ['only' => ['destroy']]);
    }
    /**
     * Display a listing of the resource.
     *
     * @return JsonResponse
     */
    public function index()
    {
        $roles = Role::all();
        return response()->json(['success' => true, 'roles' => $roles], $this-> successStatus);
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
            'name' => 'required|unique:roles,name',
            'permission' => 'required',
        ]);

        $role = Role::create(['name' => $request->input('name')]);
        $role->syncPermissions($request->input('permission'));
        if( $role )
            return response()->json(['success' => true, 'role' => $role], $this->successStatus);
        else
            return response()->json(['success' => false, 'message' => 'Role added fail'], $this->failedStatus);
    }


    /**
     * Display the specified resource.
     *
     * @param $id
     * @return JsonResponse
     */
    public function show($id)
    {
        $role = Role::find($id);
        if ( !$role )
            return response()->json(['success' => false, 'message' => 'Role not found'], $this->invalidStatus);
        else
            return response()->json(['success' => true, 'role' => $role], $this->successStatus);
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
        $role = Role::find($id);
        request()->validate([
            'name' => 'required|unique:roles,name,'.$id,
        ]);
        $role = $role->update($request->all());
        if( $role )
            return response()->json(['success' => true, 'message' => 'Role update successfully'], $this->successStatus);
        else
            return response()->json(['success' => false, 'message' => 'Role update failed'], $this->failedStatus);
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
        $role = Role::find($id);
        if ( !$role )
            return response()->json(['success' => false, 'message' => 'Role not found'], $this->invalidStatus);

        if ( $role->delete() )
            return response()->json(['success' => true, 'message' => 'Role deleted'], $this->successStatus);
        else
            return response()->json(['success' => false, 'message' => 'Role can not be deleted'], $this->failedStatus);

    }
}
