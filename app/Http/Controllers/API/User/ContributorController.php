<?php

namespace App\Http\Controllers\API\User;

use App\RoleSetup;
use App\User;
use App\UserProfile;
use App\Http\Controllers\Controller;
use App\Contributor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ContributorController extends Controller
{
    public $successStatus = 200;
    public $failedStatus = 500;
    public $invalidStatus = 400;
    function __construct()
    {
        $this->middleware('api_permission:contributor-list|contributor-create|contributor-edit|contributor-delete', ['only' => ['index','show']]);
        $this->middleware('api_permission:contributor-create', ['only' => ['store']]);
        $this->middleware('api_permission:contributor-edit', ['only' => ['update']]);
        $this->middleware('api_permission:contributor-delete', ['only' => ['destroy']]);
    }


    /**
     * Display a listing of the resource.
     *
     * @return JsonResponse
     */
    public function index()
    {
        $contributors = Contributor::with(['user_profile'])->get();
        return response()->json(['success' => true, 'contributors' => $contributors], $this-> successStatus);
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
            'first_name' => 'required',
            'last_name' => 'required',
            'email' => 'required|unique:user_profiles',
            'phone' => 'required|unique:user_profiles',
        ]);
        $input = $request->all();
        if($input['role_id']){
            $contributor_role_id = $input['role_id'];
        }else{
            $role = RoleSetup::first();
            if( !$role ){
                return response()->json(['success' => false, 'message' => 'Role not found for this user'], $this->failedStatus);
            }
            $contributor_role_id = $role->contributor_role_id;
        }

        // Add Login Info
        $login_data = [
            'name' => $input['first_name'] .' '. $input['last_name'],
            'email' => $input['email'],
            'password' => Hash::make('123456789'),
        ];
        $user = User::create($login_data);

        // Add User Profile
        $data = [
            'user_id' => $user->id,
            'first_name' => $input['first_name'],
            'last_name' => $input['last_name'],
            'email' => $input['email'],
            'phone' => $input['phone'],
            'skype' => $input['skype'],
            'profession' => $input['profession'],
            'about' => $input['about'],
            'image' => $input['image'],
            'address' => $input['address'],
            'zipcode' => $input['zipcode'],
            'country' => $input['country'],
            'guard_name' => 'web',
        ];

        // Assign Role
        //$role = RoleSetup::first();
        $user->assignRole($contributor_role_id);

        $user_profile = UserProfile::create( $data );
        if( $user_profile ){

            // Add Contributor Info
            $contributor_data = [
                'profile_id' => $user_profile['id'],
                'completing_percentage' => 100,
                'total_question' => 0,
                'average_rating' => 0,
                'approve_status' => 0,
                'active_status' => 0,
                'guard_name' => 'web',
            ];
            $contributor = Contributor::create( $contributor_data );

            if( $contributor ){
                return response()->json(['success' => true, 'contributor' => $user_profile], $this->successStatus);
            }
        }
        else{
            return response()->json(['success' => false, 'message' => 'Contributor added fail'], $this->failedStatus);
        }
    }


    /**
     * Display the specified resource.
     *
     * @param $id
     * @return JsonResponse
     */
    public function show($id)
    {
        //$contributor = UserProfile::find($id);
        $contributor = Contributor::with('user_profile')
                                    ->where('id', $id)
                                    ->get();
        if ( !$contributor )
            return response()->json(['success' => false, 'message' => 'Contributor not found'], $this->invalidStatus);
        else
            return response()->json(['success' => true, 'contributor' => $contributor], $this->successStatus);
    }


    /**
     * Display the specified resource.
     *
     * @param $id
     * @return JsonResponse
     */
    public function getContributor($id)
    {
        $profile = UserProfile::where('user_id', $id)->first();
        $contributor = Contributor::where('profile_id', $profile->id)->get();
        if ( !$profile )
            return response()->json(['success' => false, 'message' => 'User not found'], $this->invalidStatus);
        else
            return response()->json(['success' => true, 'contributor' => $contributor], $this->successStatus);
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
        $profile_id = Contributor::find($id);
        $contributor = UserProfile::find($profile_id->profile_id);
        request()->validate([
            'email' => 'unique:user_profiles,email,'.$profile_id->profile_id,
            'phone' => 'unique:user_profiles,phone,'.$profile_id->profile_id,
        ]);
        $contributor = $contributor->update($request->all());
        if( $contributor )
            return response()->json(['success' => true, 'message' => 'Contributor update successfully'], $this->successStatus);
        else
            return response()->json(['success' => false, 'message' => 'Contributor update failed'], $this->failedStatus);
    }


    /**
     * Remove the specified resource from storage.
     *
     * @param $id
     * @return JsonResponse
     */
    public function destroy($id)
    {
        $contributor = Contributor::find($id);
        if ( !$contributor )
            return response()->json(['success' => false, 'message' => 'Contributor not found'], $this->invalidStatus);

        if ( $contributor->delete() )
            return response()->json(['success' => true, 'message' => 'Contributor deleted'], $this->successStatus);
        else
            return response()->json(['success' => false, 'message' => 'Contributor can not be deleted'], $this->failedStatus);

    }
}
