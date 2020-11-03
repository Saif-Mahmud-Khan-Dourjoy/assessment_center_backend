<?php

namespace App\Http\Controllers\API\Setup;

use App\PermissionList;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Validator;

class PermissionListController extends Controller
{
    public $successStatus = 200;
    public $failedStatus = 500;
    public $invalidStatus = 400;
    function __construct()
    {
        //$this->middleware('api_permission:permission-list|permission-create|permission-edit|permission-delete', ['only' => ['index','show']]);
        //$this->middleware('api_permission:permission-create', ['only' => ['store']]);
        //$this->middleware('api_permission:permission-edit', ['only' => ['update']]);
        //$this->middleware('api_permission:permission-delete', ['only' => ['destroy']]);
    }


    /**
     * Display a listing of the resource.
     *
     * @return JsonResponse
     */
    public function index()
    {
        $permissions = PermissionList::all();
        return response()->json(['success' => true, 'permissions' => $permissions], $this-> successStatus);
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
            'name' => 'required|unique:permissions',
        ]);
        $input = $request->all();
        $data = [
            'name' => $input['name'],
            'guard_name' => 'web',
        ];
        $permission = PermissionList::create($data);
        if( $permission )
            return response()->json(['success' => true, 'permission' => $permission], $this->successStatus);
        else
            return response()->json(['success' => false, 'message' => 'Permission added fail'], $this->failedStatus);
    }


    /**
     * Display the specified resource.
     *
     * @param $id
     * @return JsonResponse
     */
    public function show($id)
    {
        $permission = PermissionList::find($id);
        if ( !$permission )
            return response()->json(['success' => false, 'message' => 'Permission not found'], $this->invalidStatus);
        else
            return response()->json(['success' => true, 'permission' => $permission], $this->successStatus);
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
        $permission = PermissionList::find($id);
        request()->validate([
            'name' => 'required|unique:permissions,name,'.$id,
        ]);
        $permission = $permission->update($request->all());
        if( $permission )
            return response()->json(['success' => true, 'message' => 'Permission update successfully'], $this->successStatus);
        else
            return response()->json(['success' => false, 'message' => 'Permission update failed'], $this->failedStatus);
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
        $permission = PermissionList::find($id);
        if ( !$permission )
            return response()->json(['success' => false, 'message' => 'Permission not found'], $this->invalidStatus);

        if ( $permission->delete() )
            return response()->json(['success' => true, 'message' => 'Permission deleted'], $this->successStatus);
        else
            return response()->json(['success' => false, 'message' => 'Permission can not be deleted'], $this->failedStatus);

    }
}
