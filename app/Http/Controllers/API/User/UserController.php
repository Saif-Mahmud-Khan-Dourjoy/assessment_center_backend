<?php

namespace App\Http\Controllers\API\User;


use App\Contributor;
use App\RoleSetup;
use App\Student;
use App\User;
use App\UserAcademicHistory;
use App\UserEmploymentHistory;
use App\UserProfile;
use GuzzleHttp\Client;
use http\Env\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Institute;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Mail\Mailable;
use Illuminate\Validation\Validator;
use mysql_xdevapi\Exception;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Str;
use App\Mail\UserCredentials;
use \Symfony\Component\Console\Output\ConsoleOutput;
// use Illuminate\Support\Carbon;


class UserController extends Controller
{
    public $successStatus = 200;
    public $failedStatus = 500;
    public $invalidStatus = 400;
    public $out;                            //for printing any message into console

    public function __construct()
    {
        //$this->middleware(['api_role'])->only('index');
        //        $this->middleware('api_permission:user-list|user-create|user-edit|user-delete', ['only' => ['index','show']]);
        //        $this->middleware('api_permission:user-create', ['only' => ['store']]);
        //        $this->middleware('api_permission:user-edit', ['only' => ['update']]);
        //        $this->middleware('api_permission:user-delete', ['only' => ['destroy']]);
        $this->out = new ConsoleOutput();                 // for printing message to console
    }


    /**
     * Display a listing of the resource.
     *
     * @return JsonResponse
     */

    public function index(Request $request)
    {
        try {
            Log::channel("ac_info")->info(__CLASS__ . "@" . __FUNCTION__ . "# Fetching user-list.");
            $user = auth()->user();
            $input = $request->all();
            $this->out->writeln('Get user-list!');
            if ($user->can('super-admin')) {
                $this->out->writeln("Super Admin!");
                if (!empty($_POST["for_students"]) || $input['for_students']) {
                    $role_id = RoleSetup::select('student_role_id')->first();
                    $users = User::role($role_id['student_role_id'])->get();
                    /*** Uncomment this if caching is necessary
                    $users = Cache::remember('users', 60*60*24, function ($role_id) {
                    $this->out->writeln("Not found in cache!");
                    //                    return DB::table('users')->get();
                    return User::role($role_id['student_role_id'])->get();
                    });
                     * ***/
                    return response()->json(['success' => true, 'users' => $users], $this->successStatus);
                }
                $users = User::with(['roles'])->where('id', '!=', $user->id)->get();
                Log::channel("ac_info")->info(__CLASS__ . "@" . __FUNCTION__ . "# All user-list returned for super-admin!");
                return response()->json(['success' => true, 'users' => $users], $this->successStatus);
            }
            if (!empty($_POST["for_students"]) || $input['for_students']) {
                $role_id = RoleSetup::select('student_role_id')->first();
                $users = User::role($role_id['student_role_id'])->where('id', '!=', $user->id)->where('institute_id', $user->institute_id)->get();
                Log::channel("ac_info")->info(__CLASS__ . "@" . __FUNCTION__ . "# Institution wise student-list returned.");
                return response()->json(['success' => true, 'users' => $users], $this->successStatus);
            }
            if ($user->institute_id) {
                $users = User::with(['roles'])->where('id', '!=', $user->id)->where('institute_id', $user->institute_id)->get();
                Log::channel("ac_info")->info(__CLASS__ . "@" . __FUNCTION__ . "# Institution wise user-list returned of inst: " . $user->institute_id);
                return response()->json(['success' => true, 'users' => $users], $this->successStatus);
                /*** If caching necessary then uncomment next section ***
                $users = Cache::remember('users', 60*60*24, function () use ($user) {
                $this->out->writeln("User-list Not found in cache!");
                return User::with(['roles'])->where('id','!=',$user->id)->where('institute_id',$user->institute_id)->get();;
                });
                return response()->json(['success'=>true,'users'=>$users],$this->successStatus);
                 * */
            }
            Log::channel("ac_info")->info(__CLASS__ . "@" . __FUNCTION__ . "# Empty user-list found!");
            return response()->json(['success' => true, 'users' => []], $this->successStatus);
        } catch (\Exception $e) {
            Log::channel("ac_error")->info(__CLASS__ . "@" . __FUNCTION__ . "# Unable to fetch user-list! error: " . $e->getMessage());
            return response()->json(['success' => false, "message" => "Fetching failed!", "error" => $e->getMessage()], $this->failedStatus);
        }
    }
    public function getRecruiter(Request $request)
    {
        $recruiterRole = Role::where('name', '=', 'Recruiter')->first();
        $institute_id = $request->institute_id;



        $recruiter = User::whereHas("roles", function ($q) use ($recruiterRole) {
            $q->where("id", $recruiterRole->id);
        })->where('institute_id', $institute_id)->get();
        // $recruiter=User::role($recruiterRole->id)->where('institute_id', $institute_id)->get();

        return response()->json(['recruiters' => $recruiter]);
    }
    /**
     * Sen User his credential to his email
     * @param $username, $user_password, $user_email
     * @return True/False
     */

    public function emailCredential($username, $name,  $user_password, $user_email)
    {
        $this->out->writeln('Emailing user credentials');
        try {
            // $email = env('TO_EMAIL');
            $this->out->writeln('Email: ' . $user_email);
            Mail::to($user_email)
                ->send(new UserCredentials($username, $name, $user_password, $user_email));
            $this->out->writeln('Mail sent scuccessfully');
            return true;
        } catch (\Swift_TransportException $e) {
            $this->out->writeln('Unable to email user credentials, for ' . $e->getMessage());
            return false;
        }
    }

    public function singleUserCredential($email, $first_name, $last_name, $username, $password, $institute_name, $contact, $organization_email)
    {
        try {
            Log::channel("ac_info")->info(__CLASS__ . "@" . __FUNCTION__ . "# Sending single-user credential to email-server.");
            $delay = env("EMAIL_SERVER_JOB_DELAY");
            $url = env("EMAIL_SERVER_URL") . 'user-credential';
            $client = new Client();
            $body = [
                "email" => $email,
                "first_name" => $first_name,
                "last_name" => $last_name,
                "username" => $username,
                "password" => $password,
                "institute" => $institute_name,
                "contact" => $contact,
                "organization_email" => $organization_email,
                "delay" => $delay
            ];
            $response = $client->post($url, ["form_params" => $body, 'http_errors' => false]);
            if ($response->getStatusCode() != 200)
                throw new \Exception("Unable to send user-credential for user! Check your email.");
            Log::channel("ac_info")->info(__CLASS__ . "@" . __FUNCTION__ . "# Successfully Sent user-credential to email-server");
            return true;
        } catch (\Exception $e) {
            $this->out->writeln("Unable to send-credential to user! error: " . $e->getMessage());
            Log::channel("ac_error")->info(__CLASS__ . "@" . __FUNCTION__ . "# Unable to sent user-credential to email-server! error: " . $e->getMessage());
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

        Log::channel("ac_info")->info(__CLASS__ . "# Creating user: " . $request['username']);
        request()->validate([
            'username' => 'required|unique:users',
            'email' => 'required|email',
            // 'birth_date' => 'required',
            'phone' => 'required',
            'institute_id' => 'required'
        ]);

        try {
            $input = $request->all();
            $institute = Institute::where('id', '=', $input['institute_id'])->get();
            // return response()->json(['data' => $institute[0]['name']]);
            $rand_pass = Str::random(8);
            $hashed_random_password = Hash::make($rand_pass);
            if ($input['role_id'])
                $role_id = $input['role_id'];
            else {
                $role = RoleSetup::first();
                $role_id = $role->new_register_user_role_id;
            }
            // Log::info($role_id);
            // exit();
            $user_data = [
                'first_name' => $input['first_name'],
                'last_name' => $input['last_name'],
                'name' => $input['first_name'] . ' ' . $input['last_name'],
                'username' => $input['username'],
                'email' => $input['email'],
                'status' => 1,
                'password' => $hashed_random_password,
                'phone' => $input['phone'],
                // 'birth_date' => $input['birth_date'],
                'birth_date' => (!empty($input["birth_date"])) ? $input['birth_date'] : NULL,
                'skype' => (!empty($input["skype"])) ? $input['skype'] : NULL,
                'profession' => (!empty($input["profession"])) ? $input['profession'] : NULL,
                'skill' => (!empty($input["skill"])) ? $input['skill'] : NULL,
                'about' => (!empty($input["about"])) ? $input['about'] : NULL,
                'img' => (!empty($input["img"])) ? $input['img'] : '',
                'address' => (!empty($input["address"])) ? $input['address'] : NULL,
                'institute_id' => (!(empty($input['institute_id'] or is_null($input['institute_id']))) ? $input['institute_id'] : null),
                // 'zipcode' => $input['zipcode'],
                'zipcode' => (!empty($input["zipcode"])) ? $input['zipcode'] : NULL,
                // 'country' => $input['country'],
                'country' => (!empty($input["country"])) ? $input['country'] : NULL,
                'completing_percentage' => 100,
                'total_complete_assessment' => 0,
                'approve_status' => 0,
                'active_status' => 0,
                'total_question' => 0,
                'average_rating' => 0,
                'guard_name' => 'web',
                'email_verified_at' => date("Y-m-d H:i:s", strtotime('now'))
            ];
            DB::beginTransaction();
            try {
                $user = User::create($user_data);
                $user->assignRole($role_id);
                $user_data['user_id'] = $user->id;
                $user_profile = UserProfile::create($user_data);
                $user_data['profile_id'] = $user_profile->id;
                $student = Student::create($user_data);
                $contributor = Contributor::create($user_data);
                if (!$this->singleUserCredential($user_data['email'], $user_data['first_name'], $user_data['last_name'], $user_data['username'], $rand_pass, $institute[0]['name'], $institute[0]['contact_no'], $institute[0]['email']))
                    throw new \Exception('Email-server is unreachable!');
            } catch (\Exception $e) {
                DB::rollback();
                Log::channel("ac_error")->info(__CLASS__ . "@" . __FUNCTION__ . "# Unable to create user, rolling back db operation! error: " . $e->getMessage());
                return response()->json(['success' => false, 'message' => 'User Creation unsuccessful!', 'error' => $e->getMessage()], $this->failedStatus);
            }
            Db::commit();
            $user = Student::with(['user_profile'])->where('id', $student->id)->get();
            Log::channel("ac_info")->info(__CLASS__ . "# Successfully created user: " . $request['username']);
            return response()->json(['success' => true, 'student' => $user], $this->successStatus);
        } catch (\Exception $e) {
            Log::channel("ac_error")->info(__CLASS__ . "@" . __FUNCTION__ . "# Unable to create user! error: " . $e->getMessage());
            $this->out->writeln("Unable to create new-user! error: " . $e->getMessage());
            return response()->json(["success" => false, "message" => "User-Creation unsuccessful!", 'error' => $e->getMessage()], $this->failedStatus);
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
        try {
            Log::channel("ac_info")->info(__CLASS__ . "@" . __FUNCTION__ . "# Fetching user, id: " . $id);
            $user = User::find($id);
            if (!$user)
                throw new \Exception("User may not exist!");
            Log::channel("ac_info")->info(__CLASS__ . "@" . __FUNCTION__ . "# Successfully fetched user, id: " . $id);
            return response()->json(['success' => true, 'user' => $user], $this->successStatus);
        } catch (\Exception $e) {
            Log::channel("ac_error")->info(__CLASS__ . "@" . __FUNCTION__ . "# Unable to fetch user! error: " . $e->getMessage());
            return response()->json(['success' => false, "message" => "User fetching Unsuccessful!", "error" => $e->getMessage()], $this->failedStatus);
        }
    }


    /**
     * Display the specified resource.
     *
     * @param $id
     * @return JsonResponse
     */
    public function getUser($id)
    {
        try {
            Log::channel("ac_info")->info(__CLASS__ . "@" . __FUNCTION__ . "# Fetching user-profile, by profile-id: " . $id);
            $profile = User::with(['user_profile', 'roles'])->where('id', $id)->get();
            if (!$profile)
                throw new \Exception("User-profile may not exist!");
            Log::channel("ac_info")->info(__CLASS__ . "@" . __FUNCTION__ . "# Successfully fetched user-profile, by profile-id: " . $id);
            return response()->json(['success' => true, 'profile' => $profile], $this->successStatus);
        } catch (\Exception $e) {
            Log::channel("ac_error")->info(__CLASS__ . "@" . __FUNCTION__ . "# Unable to fetch user-profile, by profile-id: " . $id) . "! error: " . $e->getMessage();
            return response()->json(['success' => false, "message" => "Unable to Fetch User with Profile!", "error" => $e->getMessage()], $this->failedStatus);
        }
    }

    public function getProfileByPID($profileId)
    {
        try {
            Log::channel("ac_info")->info(__CLASS__ . "@" . __FUNCTION__ . "# Fetching user-profile by, user-id: " . $profileId);
            $userProfile = UserProfile::find($profileId);
            if (!$userProfile)
                throw new \Exception("User-profile may not exist!");
            //                return response()->json(['success'=>false, "message"=>"User Profile Not Found!"], $this->invalidStatus);
            Log::channel("ac_info")->info(__CLASS__ . "@" . __FUNCTION__ . "# redirecting for user-profile by, user-id: " . $profileId);
            return $this->getUser($userProfile->user_id);
        } catch (\Exception $e) {
            $this->out->writeln("Unable to fetch User-Profile by profile-id: $profileId! error: " . $e->getMessage());
            Log::channel("ac_error")->info(__CLASS__ . "@" . __FUNCTION__ . "# Unable to fetch user-profile by, user-id: " . $profileId . "! error: " . $e->getMessage());
            return response()->json(['success' => false, "message" => "Unable to fetch User-Profile by profile-id: $profileId!", 'error' => $e->getMessage()], $this->failedStatus);
        }
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
        try {
            Log::channel("ac_info")->info(__CLASS__ . "@" . __FUNCTION__ . "# Fetching user-profile by, profile-id: " . $request['profile_id']);
            $input = $request->all();
            $user = UserProfile::find($input['profile_id']);
            if (!$user) {
                return response()->json(['success' => true, 'message' => 'Profile not found'], $this->successStatus);
            }
            DB::beginTransaction();
            try {
                $userProfile = $user->update($request->all());
                $input['name'] = $input['first_name'] . ' ' . $input['last_name'];
                $userUpdate = User::find($user->user_id);
                $userUpdate->update($input);
                if (isset($input['role_id']) && !empty($input['role_id'])) {
                    DB::table('model_has_roles')->where('model_id', $userUpdate->id)->delete();
                    $userUpdate->assignRole($input['role_id']);
                }
            } catch (\Exception $e) {
                DB::rollback();
                Log::channel("ac_error")->info(__CLASS__ . "@" . __FUNCTION__ . "# Unable to update user-profile by, profile-id: " . $request['profile_id']);
                return response()->json(['success' => false, "message" => "Unable to update user profile!", "error" => $e->getMessage()], $this->failedStatus);
            }
            Db::commit();
            Log::channel("ac_info")->info(__CLASS__ . "@" . __FUNCTION__ . "# Successfully fetched user-profile by, profile-id: " . $request['profile_id']);
            return response()->json(['success' => true, 'message' => 'Profile updated successfully'], $this->successStatus);
        } catch (\Exception $e) {
            Log::channel("ac_error")->info(__CLASS__ . "@" . __FUNCTION__ . "# Unable to update user-profile by, profile-id: " . $request['profile_id'] . "! error: " . $e->getMessage());
            return response()->json(['success' => false, "message" => "Updating user-info failed!", "error" => $e->getMessage()], $this->failedStatus);
        }
    }


    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function addAcademicHistory(Request $request)
    {
        try {
            Log::channel("ac_info")->info(__CLASS__ . "@" . __FUNCTION__ . "# Updating user-academic history by, profile-id: " . $request['profile_id']);
            request()->validate([
                //'user_id' => 'required'
            ]);
            $input = $request->all();

            // Delete previous data
            if (!UserAcademicHistory::where(['profile_id' => $input['profile_id'], 'check_status' => $input['check_status']])->first())
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
            Log::channel("ac_info")->info(__CLASS__ . "@" . __FUNCTION__ . "# Successfully updated user-academic history by, profile-id: " . $request['profile_id']);
            return response()->json(['success' => true, 'message' => 'Academic history add successfully'], $this->successStatus);
        } catch (\Exception $e) {
            Log::channel("ac_error")->info(__CLASS__ . "@" . __FUNCTION__ . "# Unable to update user-academic history by, profile-id: " . $request['profile_id'] . "! error: " . $e->getMessage());
            return response()->json(['success' => false, "message" => "Updating user-academic history failed!", "error" => $e->getMessage()], $this->failedStatus);
        }
    }


    /**
     * Show a newly created resource in storage.
     *
     * @param $id
     * @return JsonResponse
     */
    public function getAcademicHistory($id)
    {
        try {
            Log::channel("ac_info")->info(__CLASS__ . "@" . __FUNCTION__ . "# Fetching user-academic history by, profile-id: " . $id);
            $dataAcademic = UserAcademicHistory::where('profile_id', '=', $id)->get();
            Log::channel("ac_info")->info(__CLASS__ . "@" . __FUNCTION__ . "# Successfully fetched user-academic history by, profile-id: " . $id);
            return response()->json(['success' => true, 'data' => $dataAcademic], $this->successStatus);
        } catch (\Exception $e) {
            Log::channel("ac_error")->info(__CLASS__ . "@" . __FUNCTION__ . "# Unable to fetch user-academic history by, profile-id: " . $id . "! error: " . $e->getMessage());
            return response()->json(['success' => false, "message" => "Unable to fetch use-academic history!", "error" => $e->getMessage()], $this->failedStatus);
        }
    }


    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function addEmploymentHistory(Request $request)
    {
        try {
            Log::channel("ac_info")->info(__CLASS__ . "@" . __FUNCTION__ . "# Updating user-employment history history by, profile-id: " . $request['profile_id']);
            request()->validate([
                'profile_id' => 'required'
            ]);
            $input = $request->all();
            // Delete previous data
            if (!UserEmploymentHistory::where(['profile_id' => $input['profile_id'], 'check_status' => $input['check_status']])->first())
                UserEmploymentHistory::where('profile_id', $input['profile_id'])->delete();
            // Add User Employment History
            $dataEmployment = [
                'profile_id' => $input['profile_id'],
                'institute' => $input['institute'],
                'position' => $input['position'],
                'responsibility' => $input['responsibility'],
                'start_date' => $input['start_date'],
                'end_date' => (!empty($input['end_date']) ? $input['end_date'] : ""),
                'duration' => $input['duration'],
                'currently_work' => $input['currently_work'],
                'description' => (!empty($_POST["description"])) ? $input['description'] : 'n/a',
                'check_status' => $input['check_status'],
            ];
            UserEmploymentHistory::create($dataEmployment);
            Log::channel("ac_info")->info(__CLASS__ . "@" . __FUNCTION__ . "# Successfully updated user-employment history history by, profile-id: " . $request['profile_id']);
            return response()->json(['success' => true, 'message' => 'Employment history add successfully'], $this->successStatus);
        } catch (\Exception $e) {
            Log::channel("ac_error")->info(__CLASS__ . "@" . __FUNCTION__ . "# Unable to update user-employment history history by, profile-id: " . $request['profile_id'] . "! error: " . $e->getMessage());
            return response()->json(['success' => false, "message" => "Employment History insertion unsuccessful!", "error" => $e->getMessage()], $this->failedStatus);
        }
    }

    /**
     * Show a newly created resource in storage.
     *
     * @param $id
     * @return JsonResponse
     */
    public function getEmploymentHistory($id)
    {
        try {
            Log::channel("ac_info")->info(__CLASS__ . "@" . __FUNCTION__ . "# Fetching user-employment history history by, profile-id: " . $id);
            $dataEmployment = UserEmploymentHistory::where('profile_id', '=', $id)->get();
            Log::channel("ac_info")->info(__CLASS__ . "@" . __FUNCTION__ . "# Successfully fetched user-employment history history by, profile-id: " . $id);
            return response()->json(['success' => true, 'data' => $dataEmployment], $this->successStatus);
        } catch (\Exception $e) {
            Log::channel("ac_error")->info(__CLASS__ . "@" . __FUNCTION__ . "# Unable to fetch user-employment history history by, profile-id: " . $id . "! error: " . $e->getMessage());
            return response()->json(['success' => false, "message" => "Fetching use-employment history is unsuccessful!", "error" => $e->getMessage()], $this->failedStatus);
        }
    }

    /**
     * Show a newly created resource in storage.
     *
     * @param $id
     * @return JsonResponse
     */
    public function getPermissionList()
    {
        try {
            Log::channel("ac_info")->info(__CLASS__ . "@" . __FUNCTION__ . "# Fetching user-permission list");
            // get logged-in user
            $user = auth()->user();
            $permissions = $user->getAllPermissions();
            Log::channel("ac_info")->info(__CLASS__ . "@" . __FUNCTION__ . "# Successfully Fetched user-permission list");
            return response()->json(['success' => true, 'permissions' => $permissions], $this->successStatus);
        } catch (\Exception $e) {
            Log::channel("ac_error")->info(__CLASS__ . "@" . __FUNCTION__ . "# Unable to fetch user-permission list! error: " . $e->getMessage());
            return response()->json(['success' => false, "message" => "Fetching user-permission list is unsuccessful!", "error" => $e->getMessage()], $this->failedStatus);
        }
    }

    /**
     * Update resource in storage.
     *
     * @param $id
     * @return JsonResponse
     */
    public function updateStatus($id)
    {
        try {
            Log::channel("ac_info")->info(__CLASS__ . "@" . __FUNCTION__ . "# Updating user status");
            $profile = UserProfile::where('user_id', $id)->first();
            // Check profile
            if (!$profile)
                throw new \Exception("Profile not found!");
            //                return response()->json(['success' => false, 'message' => 'Profile not found'], $this->invalidStatus);
            $user = User::where('id', $profile->user_id)->first();
            if (!$user)
                throw new \Exception("User not found!");
            //                return response()->json(['success' => false, 'message' => 'Profile not found'], $this->invalidStatus);
            $data = [
                'status' => ($user->status == '0') ? '1' : '0',
            ];
            $user->update($data);
            Log::channel("ac_info")->info(__CLASS__ . "@" . __FUNCTION__ . "# Successfully updated user-status");
            return response()->json(['success' => true, 'message' => 'User updated'], $this->successStatus);
        } catch (\Exception $e) {
            Log::channel("ac_error")->info(__CLASS__ . "@" . __FUNCTION__ . "# Unable to update user status! error: " . $e->getMessage());
            return response()->json(['success' => false, "message" => "User status change unsuccessful!", "error" => $e->getMessage()], $this->failedStatus);
        }
    }
}
