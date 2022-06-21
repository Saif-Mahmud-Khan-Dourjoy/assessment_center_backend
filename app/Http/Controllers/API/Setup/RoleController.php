<?php

namespace App\Http\Controllers\API\Setup;

use App\Http\Controllers\Controller;
use App\UserProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public $successStatus = 200;
    public $failedStatus = 500;
    public $invalidStatus = 400;
    public $out;
    function __construct()
    {
//        $this->middleware('api_permission:role-list|role-create|role-edit|role-delete', ['only' => ['index','show']]);
//        $this->middleware('api_permission:role-create', ['only' => ['store']]);
//        $this->middleware('api_permission:role-edit', ['only' => ['update']]);
//        $this->middleware('api_permission:role-delete', ['only' => ['destroy']]);
        $this->out = new \Symfony\Component\Console\Output\ConsoleOutput();                 // for printing message to console
    }


    /**
     * Display a listing of the resource.
     *
     * @return JsonResponse
     */
    public function index()
    {
        $user = Auth::user();
        $userProfile = UserProfile::where('user_id','=',$user->id)->first();
        if($user->can('super-admin')){
            $roles = Role::all();
            return response()->json(['success' => true, 'roles' => $roles], $this-> successStatus);
        }
        $roles = Role::all();
        $valid_roles = [];
        foreach ($roles as $role){
            if($role->hasPermissionTo('super-admin')){
                continue;
            }
            array_push($valid_roles, $role);
        }

        $rolePermissions = Permission::join("role_has_permissions","role_has_permissions.permission_id","=","permissions.id")
            ->get();
        return response()->json(['success' => true, 'roles' => $valid_roles], $this-> successStatus);
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

        $role = Role::create([
            'name' => $request->input('name'),
            'guard_name' => 'web'
        ]);
        $permission = explode(',', $request->input('permission'));
        $role->syncPermissions($permission);
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
        $rolePermissions = Permission::join("role_has_permissions","role_has_permissions.permission_id","=","permissions.id")
            ->where("role_has_permissions.role_id",$id)
            ->get();
        $role['permissions'] = $rolePermissions;
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
     * @throws ValidationException
     */
    public function update(Request $request, $id)
    {
        $this->validate($request, [
            'name' => 'required',
            'permission' => 'required',
        ]);
        $role = Role::find($id);
        $role->name = $request->input('name');
        $role->guard_name = 'web';
        $role->save();

        $permission = explode(',', $request->input('permission'));
        $role->syncPermissions($permission);
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
