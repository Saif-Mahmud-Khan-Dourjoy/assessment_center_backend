<?php

namespace App\Http\Controllers\API\User;


use App\Contributor;
use App\User;
use App\UserAcademicHistory;
use App\UserEmploymentHistory;
use App\UserProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Validation\Validator;
use Spatie\Permission\Models\Role;


class UserController extends Controller
{
    public $successStatus = 200;
    public $failedStatus = 500;
    public $invalidStatus = 400;

    public function __construct(){
        //$this->middleware(['api_role'])->only('index');
        /*$this->middleware('api_permission:user-list|user-create|user-edit|user-delete', ['only' => ['index','show']]);
        $this->middleware('api_permission:user-create', ['only' => ['store']]);
        $this->middleware('api_permission:user-edit', ['only' => ['update']]);
        $this->middleware('api_permission:user-delete', ['only' => ['destroy']]);*/
    }


    /**
     * Display a listing of the resource.
     *
     * @return JsonResponse
     */
    public function index()
    {
        //$users = User::all();
        $users = User::with('user_profile')->get();
        return response()->json(['success' => true, 'users' => $users], $this-> successStatus);
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
            'name' => 'required',
            'email' => 'required|email|unique:users',
            'password' => 'required',
            'c_password' => 'required|same:password',
        ]);
        $input = $request->all();
        $input['password'] = bcrypt($input['password']);
        $user = User::create($input);
        if( $user ){

            // Assign Role
            $role_id = 2;
            if( $input['role_id'] ){
                $role_id = $input['role_id'];
            }
            if( !Role::find($role_id) ){
                $role_id = 2;
            }
            $user->assignRole([$role_id]);

            // Add User Profile
            UserProfile::create([
                'user_id' => $user['id'],
                'first_name' => $input['name'],
                'email' => $input['email']
            ]);

            $responseData['name'] =  $user->name;
            $responseData['token'] =  $user->createToken('NSLAssessmentCenter')-> accessToken;
            return response()->json(['success' => true, 'user' => $responseData], $this->successStatus);
        }
        else{
            return response()->json(['success' => false, 'message' => 'User added fail'], $this->failedStatus);
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
        $user = User::find($id);
        if ( !$user )
            return response()->json(['success' => false, 'message' => 'User not found'], $this->invalidStatus);
        else
            return response()->json(['success' => true, 'user' => $user], $this->successStatus);
    }


    /**
     * Display the specified resource.
     *
     * @param $id
     * @return JsonResponse
     */
    public function getUser($id)
    {
        $profile = User::with('user_profile')->where('id', $id)->get();
        if ( !$profile )
            return response()->json(['success' => false, 'message' => 'User not found'], $this->invalidStatus);
        else
            return response()->json(['success' => true, 'profile' => $profile], $this->successStatus);
    }


    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param $id
     * @return JsonResponse
     */
    public function updateProfile(Request $request)
    {
        $input = $request->all();
        $user = UserProfile::find($input['profile_id']);
        if( ! $user ){
            return response()->json(['success' => true, 'message' => 'Profile not found'], $this->successStatus);
        }
        request()->validate([
            'email' => 'unique:user_profiles,email,'.$input['profile_id'],
            //'phone' => 'unique:user_profiles,phone,'.$input['profile_id'],
        ]);
        $userProfile = $user->update($request->all());
        if( $userProfile )
            return response()->json(['success' => true, 'message' => 'Profile update successfully'], $this->successStatus);
        else
            return response()->json(['success' => false, 'message' => 'Profile update failed'], $this->failedStatus);
    }


    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function addAcademicHistory(Request $request)
    {
        request()->validate([
            //'user_id' => 'required'
        ]);
        $input = $request->all();

        // Delete previous data
        if( ! UserAcademicHistory::where(['profile_id' => $input['profile_id'], 'check_status' => $input['check_status']])->first() )
            UserAcademicHistory::where('profile_id', $input['profile_id'])->delete();


        // Add User Academic History
        $dataAcademic = [
            'profile_id' => $input['profile_id'],
            'exam_course_title' => $input['exam_course_title'],
            'major' => $input['major'],
            'institute' => $input['institute'],
            'result' => $input['result'],
            'start_year' => $input['start_year'],
            'end_year' => $input['end_year'],
            'currently_study' => $input['currently_study'],
            'duration' => $input['duration'],
            'description' => (!empty($_POST["description"])) ? $input['description'] : 'n/a',
            'check_status' => $input['check_status'],
        ];
        UserAcademicHistory::create($dataAcademic);

        return response()->json(['success' => true, 'message' => 'Academic history add successfully'], $this->successStatus);

    }


    /**
     * Show a newly created resource in storage.
     *
     * @param $id
     * @return JsonResponse
     */
    public function getAcademicHistory($id)
    {
        // Get User Academic History
        $dataAcademic = UserAcademicHistory::where('profile_id', '=', $id)->get();

        return response()->json(['success' => true, 'data' => $dataAcademic], $this->successStatus);

    }


    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function addEmploymentHistory(Request $request)
    {
        request()->validate([
            //'user_id' => 'required'
        ]);
        $input = $request->all();

        // Delete previous data
        if( ! UserEmploymentHistory::where(['profile_id' => $input['profile_id'], 'check_status' => $input['check_status']])->first() )
            UserEmploymentHistory::where('profile_id', $input['profile_id'])->delete();

        // Add User Employment History
        $dataEmployment = [
            'profile_id' => $input['profile_id'],
            'institute' => $input['institute'],
            'position' => $input['position'],
            'responsibility' => $input['responsibility'],
            'start_date' => $input['start_date'],
            'end_date' => $input['end_date'],
            'duration' => $input['duration'],
            'currently_work' => $input['currently_work'],
            'description' => (!empty($_POST["description"])) ? $input['description'] : 'n/a',
            'check_status' => $input['check_status'],
        ];
        UserEmploymentHistory::create($dataEmployment);

        return response()->json(['success' => true, 'message' => 'Employment history add successfully'], $this->successStatus);

    }

    /**
     * Show a newly created resource in storage.
     *
     * @param $id
     * @return JsonResponse
     */
    public function getEmploymentHistory($id)
    {
        // Get User Employment History
        $dataEmployment = UserEmploymentHistory::where('profile_id', '=', $id)->get();

        return response()->json(['success' => true, 'data' => $dataEmployment], $this->successStatus);

    }

    /**
     * Show a newly created resource in storage.
     *
     * @param $id
     * @return JsonResponse
     */
    public function getPermissionList()
    {
        // get logged-in user
        $user = auth()->user();
        $permissions = $user->getAllPermissions();

        //dd($permissions);
        return response()->json(['success' => true, 'permissions' => $permissions], $this->successStatus);

    }
}
