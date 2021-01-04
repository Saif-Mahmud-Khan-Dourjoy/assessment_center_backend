<?php

namespace App\Http\Controllers\API\Auth;

use App\Contributor;
use App\Http\Controllers\Controller;
use App\RoleSetup;
use App\Student;
use App\User;
use App\UserProfile;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class RegisterController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | API Register Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles the registration of new users as well as their
    | validation and creation. By default this controller uses a trait to
    | provide this functionality without requiring any additional code.
    |
    */

    use RegistersUsers;
    public $successStatus = 200;
    public $failedStatus = 500;
    public $invalidStatus = 400;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest');
    }


    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function register(Request $request)
    {
        request()->validate([
            'username'=>'required|unique:users',
            'email' => 'required|email',
            'password' => 'required',
            'c_password' => 'required|same:password',
        ]);
        $input = $request->all();

//        $check_email = User::where('email', $input['email'])->first();
//        if($check_email){
//            return response()->json(['success' => false, 'message' => 'Email already exist'], $this->failedStatus);
//        }

        $role = RoleSetup::first();
        if( !$role ){
            return response()->json(['success' => false, 'message' => 'Role not found for this user'], $this->failedStatus);
        }

        $login_data = [
            'name' => $input['first_name'] .' '. $input['last_name'],
            'username'=>$input['username'],
            'email' => $input['email'],
            'password' => bcrypt($input['password']),
        ];
        $user = User::create($login_data);
        if( $user ){

            // Assign Role
            $user->assignRole([$role->new_register_user_role_id]);

            // Add User Profile
            $user_profile = UserProfile::create([
                'user_id' => $user['id'],
                'institute_id' => (!empty($_POST["institute_id"])) ? $input['institute_id'] : NULL,
                'first_name' => $input['first_name'],
                'last_name' => $input['last_name'],
                'email' => $input['email'],
                'phone' => $input['phone'],
            ]);

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

            // Add Student Info
            $student_data = [
                'profile_id' => $user_profile['id'],
                'completing_percentage' => 100,
                'total_complete_assessment' => 0,
                'approve_status' => 0,
                'active_status' => 0,
                'guard_name' => 'web',
            ];
            $student = Student::create( $student_data );


            $responseData['name'] =  $user->name;
            $responseData['token'] =  $user->createToken('NSLAssessmentCenter')-> accessToken;
            return response()->json(['success' => true, 'user' => $responseData], $this->successStatus);
        }
        else{
            return response()->json(['success' => false, 'message' => 'User added fail'], $this->failedStatus);
        }
    }

    function checkEmail(Request $request){
        $input = $request->all();
        $check_email = User::where('email', $input['email'])->first();
        if($check_email){
            return response()->json(['success' => true, 'message' => 'Email already exist'], $this->successStatus);
        }
        return response()->json(['success' => false], $this->failedStatus);
    }

}
