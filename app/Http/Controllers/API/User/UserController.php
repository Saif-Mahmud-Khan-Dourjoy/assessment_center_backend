<?php

namespace App\Http\Controllers\API\User;


use App\Contributor;
use App\RoleSetup;
use App\Student;
use App\User;
use App\UserAcademicHistory;
use App\UserEmploymentHistory;
use App\UserProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Mail\Mailable;
use Illuminate\Validation\Validator;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Str;
use App\Mail\UserCredentials;


class UserController extends Controller
{
    public $successStatus = 200;
    public $failedStatus = 500;
    public $invalidStatus = 400;
    public $out;                            //for printing any message into console

    public function __construct(){
        //$this->middleware(['api_role'])->only('index');
//        $this->middleware('api_permission:user-list|user-create|user-edit|user-delete', ['only' => ['index','show']]);
//        $this->middleware('api_permission:user-create', ['only' => ['store']]);
//        $this->middleware('api_permission:user-edit', ['only' => ['update']]);
//        $this->middleware('api_permission:user-delete', ['only' => ['destroy']]);
        $this->out = new \Symfony\Component\Console\Output\ConsoleOutput();                 // for printing message to console
    }


    /**
     * Display a listing of the resource.
     *
     * @return JsonResponse
     */

    public function index(Request $request){
        $user = auth()->user();
        $input = $request->all();
        $this->out->writeln('Get user-list!');
        if($user->can('super-admin')){
            if (!empty($_POST["for_students"]) || $input['for_students']){
                $role_id =RoleSetup::select('student_role_id')->first();
                $users = User::role($role_id['student_role_id'])->get();
                return response()->json(['success'=>true,'users'=>$users],$this->successStatus);
            }
            $users = User::with(['roles'])->where('id','!=',$user->id)->get();
            return response()->json(['success'=>true,'users'=>$users],$this->successStatus);
        }
        if (!empty($_POST["for_students"]) || $input['for_students']){
            $role_id =RoleSetup::select('student_role_id')->first();
            $users = User::role($role_id['student_role_id'])->where('id','!=',$user->id)->get();
            return response()->json(['success'=>true,'users'=>$users],$this->successStatus);
        }
        if($user->institute_id){
            $users = User::with(['roles'])->where('id','!=',$user->id)->role('Admin')->get();
            return response()->json(['success'=>true,'users'=>$users],$this->successStatus);
        }
        return response()->json(['success' => true, 'users' => []], $this->successStatus);
    }

    /**
     * Sen User his credential to his email
     * @param $username, $user_password, $user_email
     * @return True/False
     */

    public function emailCredential($username,$name,  $user_password, $user_email){
        $this->out->writeln('Emailing user credentials');
        try{
            // $email = env('TO_EMAIL');
            $this->out->writeln('Email: '.$user_email);
            Mail::to($user_email)
            ->send(new UserCredentials($username, $name, $user_password, $user_email));
            $this->out->writeln('Mail sent scuccessfully');
            return true;
        }catch(\Swift_TransportException $e){
            $this->out->writeln('Unable to email user credentials, for '.$e->getMessage());
            return false;
        }
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
            'username'=>'required|unique:users',
            'email' => 'required|email',
            'birth_date'=>'required',
            'phone'=>'required',
            'institute_id'=>'required'
        ]);
        $input = $request->all();
        $this->out->writeln('Input-img: '.$input['img']);
        $rand_pass = Str::random(8);
        $hashed_random_password = Hash::make($rand_pass);
        if( $input['role_id'] )
            $role_id = $input['role_id'];
        else{
            $role = RoleSetup::first();
            $role_id = $role->new_register_user_role_id;
        }
        $user_data = [
            'first_name'=>$input['first_name'],
            'last_name' =>$input['last_name'],
            'name' => $input['first_name'] .' '. $input['last_name'],
            'username'=>$input['username'],
            'email' => $input['email'],
            'status' => 1,
            'password' => $hashed_random_password,
            'phone'=>$input['phone'],
            'birth_date'=>$input['birth_date'],
            'skype' => (!empty($input["skype"])) ? $input['skype'] : 0,
            'profession' => (!empty($input["profession"])) ? $input['profession'] : 'n/a',
            'skill' => (!empty($input["skill"])) ? $input['skill'] : 'n/a',
            'about' => (!empty($input["about"])) ? $input['about'] : 'n/a',
            'img' => (!empty($input["img"])) ? $input['img'] : '',
            'address' => (!empty($input["address"])) ? $input['address'] : 'n/a',
            'institute_id'=>(!(empty($input['institute_id'] or is_null($input['institute_id'])))? $input['institute_id']:null),
            'zipcode' => $input['zipcode'],
            'country' => $input['country'],
            'completing_percentage' => 100,
            'total_complete_assessment' => 0,
            'approve_status' => 0,
            'active_status' => 0,
            'total_question' => 0,
            'average_rating' => 0,
            'guard_name' => 'web',
        ];
        DB::beginTransaction();
        try {
            $user = User::create($user_data);
            $user->assignRole($role_id);
            $user_data['user_id']=$user->id;
            $user_profile = UserProfile::create( $user_data );
            $user_data['profile_id']=$user_profile->id;
            $student = Student::create( $user_data );
            $contributor = Contributor::create( $user_data );
            if(!$this->emailCredential($user->username,$user->name, $rand_pass, $user->email))
                throw new \Exception('User email may incorrect!');
        }catch(\Exception $e){
            DB::rollback();
            return response()->json(['success'=>false, 'message'=>'User Creation unsuccessful!','error'=>$e->getMessage()],$this->failedStatus);
        }
        Db::commit();
        $user =Student::with(['user_profile'])->where('id', $student->id)->get();
        return response()->json(['success' => true, 'student' =>$user], $this->successStatus);
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
        $profile = User::with(['user_profile', 'roles'])->where('id', $id)->get();
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
//            'email' => 'unique:user_profiles,email,'.$input['profile_id'],
            //'phone' => 'unique:user_profiles,phone,'.$input['profile_id'],
        ]);
        $userProfile = $user->update($request->all());
        $input = request()->all();
        $input['name']=$input['first_name'].' '.$input['last_name'];
        $userUpdate = User::find($user->user_id);
        $userUpdate->update($input);
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

    /**
     * Show a newly created resource in storage.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getRoleWiseUsersList_old(Request $request)
    {
        $input = $request->all();
        $users = User::with(['user_profile', 'roles'])->get();
        if (!empty($_POST["role_name"]) && $input['role_name']){
            $users = User::with(['user_profile'])->role($input['role_name'])->get();
        }
        return response()->json(['success' => true, 'users' => $users], $this->successStatus);
    }

    public function getRoleWiseUsersList(Request $request)
    {
        $user = auth()->user();
        $userProfile = UserProfile::where('user_id','=',$user->id)->first();
        $input = $request->all();
        $this->out->writeln('Get Role Wise userlist!');
        if($user->can('super-admin')){
            $users = User::with(['user_profile', 'roles'])->where('id','!=',$user->id)->get();
            if (!empty($_POST["role_name"]) && $input['role_name']){
                $users = User::with(['user_profile'])->role($input['role_name'])->get();
            }
            return response()->json(['success'=>true,'users'=>$users],$this->successStatus);
        }
        $valid_users=[];
//        $this->out->writeln("Role Name: ".$input['role_name']);
        if (!empty($_POST["role_name"]) && $input['role_name']){
            $this->out->writeln('Role-wise user list fetching: '.$input['role_name']);
            $users = User::with(['user_profile'])->role($input['role_name'])->get();
            foreach ($users as $u) {
                $up = UserProfile::where('user_id','=',$u->id)->first();
                if($userProfile->institute_id==$up->institute_id){
                    array_push($valid_users, $u);
                }
            }
            return response()->json(['success' => true, 'users' => $valid_users], $this->successStatus);
        }

        if($userProfile->institute_id){
            $this->out->writeln('User fetching based on the institution.');
            $users = User::with(['user_profile', 'roles'])->where('id','!=',$user->id)->get();
            foreach ($users as $u) {
                $up = UserProfile::where('user_id','=',$u->id)->first();
                if($userProfile->institute_id==$up->institute_id){
                    array_push($valid_users, $u);
                }

            }
            return response()->json(['success'=>true,'users'=>$valid_users],$this->successStatus);
        }
        return response()->json(['success' => true, 'users' => $valid_users], $this->successStatus);
    }


    /**
     * Update resource in storage.
     *
     * @param $id
     * @return JsonResponse
     */
    public function updateStatus($id)
    {
        $profile = UserProfile::where('id', $id)->first();
        // Check profile
        if ( !$profile )
            return response()->json(['success' => false, 'message' => 'Profile not found'], $this->invalidStatus);
        $user = User::where('id', $profile->user_id)->first();
        if ( !$user )
            return response()->json(['success' => false, 'message' => 'Profile not found'], $this->invalidStatus);
        $data = [
            'status' => ($user->status == '0') ? '1' : '0',
        ];
        $user->update($data);
        return response()->json(['success' => true, 'message' => 'User updated'], $this->successStatus);
    }

}
