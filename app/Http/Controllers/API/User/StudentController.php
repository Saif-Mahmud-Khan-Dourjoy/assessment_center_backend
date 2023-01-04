<?php

namespace App\Http\Controllers\API\User;

use App\Contributor;
use App\Mail\UserCredentials;
use App\QuestionSet;
use App\QuestionSetAnswer;
use App\RoundCandidates;
use App\Student;
use App\Http\Controllers\Controller;
use App\Jobs\ExamInfoSend;
use App\Mail\ExamInfo;
use App\QuestionSetCandidate;
use App\RoleSetup;
use App\User;
use App\UserAcademicHistory;
use App\UserProfile;
use http\Env\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use \GuzzleHttp\Client;
use Illuminate\Support\Facades\URL;
use Lcobucci\JWT\Signer\Key\LocalFileReference;
use mysql_xdevapi\Exception;
use Symfony\Contracts\Service;
use Illuminate\Contracts\Encryption\DecryptException;

class StudentController extends Controller
{
    public $successStatus = 200;
    public $failedStatus = 500;
    public $invalidStatus = 400;

    public $out;
    function __construct()
    {
        //        $this->middleware('api_permission:student-list|student-create|student-edit|student-delete', ['only' => ['index','show']]);
        //        $this->middleware('api_permission:student-create', ['only' => ['store']]);
        //        $this->middleware('api_permission:student-edit', ['only' => ['update']]);
        //        $this->middleware('api_permission:student-delete', ['only' => ['destroy']]);
        $this->out = new \Symfony\Component\Console\Output\ConsoleOutput();                 // for printing message to console
    }


    /**
     * Display a listing of the resource.
     *
     * @return JsonResponse
     */
    public function index()
    {
        $user = auth()->user();
        $userProfile = UserProfile::find($user->id);
        $permissions = $user->getAllPermissions();
        if ($user->can('super-admin')) {
            $students = Student::with(['user_profile'])->where('profile_id', '!=', $userProfile->id)->get();
            return response()->json(['success' => true, 'students' => $students], $this->successStatus);
        }
        if ($userProfile->institute_id) {
            $students = UserProfile::with(['student'])->where('id', '!=', $userProfile->id)
                ->where('institute_id', '=', $userProfile->institute_id)
                ->get();
            return response()->json(['success' => true, 'students' => $students], $this->successStatus);
        }
        return response()->json(['success' => true, 'students' => []], $this->successStatus);
        //        $students = Student::with(['user_profile'])->get();
        //        return response()->json(['success' => true, 'students' => $students], $this-> successStatus);
    }

    /**
     * Sen User his credential to his email
     * @param $username, $user_password, $user_email
     * @return True/False
     */

    public function emailCredential($username, $name,  $user_password, $user_email)
    {
        try {
            Log::channel("ac_info")->info(__CLASS__ . "@" . __FUNCTION__ . "# Emailing credentials!");
            $this->out->writeln('Email: ' . $user_email);
            Mail::to($user_email)
                ->send(new UserCredentials($username, $name,  $user_password, $user_email));
            return true;
        } catch (\Throwable $e) {
            $this->out->writeln('Unable to email user credentials' . $e->getMessage());
            return false;
        }
    }


    public function ExamInfoEmail($candidate_email_data, $delay)
    {
        try {
            Log::channel("ac_info")->info(__CLASS__ . "@" . __FUNCTION__ . "# Emailing exam info.");
            $url = env("EMAIL_SERVER_URL") . 'exam-info';
            $client = new Client();
            $body = [
                "candidate_email_data" => $candidate_email_data,
                "delay" => $delay
            ];
            $response = $client->post($url, ["form_params" => $body, 'http_errors' => false]);
            if ($response->getStatusCode() != 200)
                throw new \Exception("Unable to send exam-info for user! Check your email.");
            Log::channel("ac_info")->info(__CLASS__ . "@" . __FUNCTION__ . "# Successfully emailed exam Info.");
            return true;
        } catch (\Exception $e) {
            Log::channel("ac_error")->info(__CLASS__ . "@" . __FUNCTION__ . "# Emailing exam Info Unsuccessful! error: " . $e->getMessage());
            return false;
        }
    }


    public function singleUserCredential($email, $first_name, $last_name, $username, $password, $delay)
    {
        try {
            Log::channel("ac_info")->info(__CLASS__ . "@" . __FUNCTION__ . "# Emailing credentials.");
            $url = env("EMAIL_SERVER_URL") . 'user-credential';
            $client = new Client();
            $body = [
                "email" => $email,
                "first_name" => $first_name,
                "last_name" => $last_name,
                "username" => $username,
                "password" => $password,
                "delay" => $delay
            ];
            $response = $client->post($url, ["form_params" => $body, 'http_errors' => false]);
            if ($response->getStatusCode() != 200)
                throw new \Exception("Unable to send user-credential for user! Check your email.");
            Log::channel("ac_info")->info(__CLASS__ . "@" . __FUNCTION__ . "# Successfully emailed credentials.");
            return true;
        } catch (\Exception $e) {
            Log::channel("ac_error")->info(__CLASS__ . "@" . __FUNCTION__ . "# Emailing credentials Unsuccessful! error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Storing a unique username for student
     *
     * @param $first_name, $lastname
     * @return $unique_username
     */

    public function uniqueUser($firstName, $lastName)
    {
        try {
            $username = $firstName[0] . $lastName;
            $i = 0;
            while (User::whereUsername($username)->exists()) {
                $i++;
                $username = $firstName[0] . $lastName . $i;
            }
            //        $this->attributes['username'] = $username;
            return $username;
        } catch (\Exception $e) {
            Log::channel("ac_error")->info(__CLASS__ . "@" . __FUNCTION__ . "# Creating unique use is Unsuccessful! error: " . $e->getMessage());
        }
    }

    public function decryptToken($token)
    {
        $dtoken = decrypt($token);
        if ($dtoken) {
            return response()->json(["success" => true, "token" => $dtoken]);
        } else {
            return response()->json(["success" => false, "token" => []]);
        }
    }


    public function getToken($assessment_id, $institute_id, $profile_id, $email, $username, $rand_pass)
    {
        // $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        // $randomString = '';

        // for ($i = 0; $i < 20; $i++) {
        //     $index = rand(0, strlen($characters) - 1);
        //     $randomString .= $characters[$index];
        // }

        // return $randomString;
        $token = array(
            "assessment_id" => $assessment_id,
            "institute_id" => $institute_id,
            "profile_id" => $profile_id,
            "email" => $email,
            "username" => $username,
            "rand_pass" => $rand_pass,
        );
        // $token = $assessment_id . $institute_id . $profile_id . $email . $username . $rand_pass;
        $generated_token = encrypt($token);
        return $generated_token;
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request)
    {
        try {
            Log::channel("ac_info")->info(__CLASS__ . "@" . __FUNCTION__ . "# Creating student.");
            request()->validate([
                'first_name' => 'required',
                'last_name' => 'required',
                'email' => 'required|email',
                // 'birth_date' => 'required',
                'phone' => 'required',
            ]);
            $input = $request->all();

            // Log::info($input);
            // exit();

            $roleID = (!empty($input["role_id"])) ? $input['role_id'] : 0;
            if ($roleID) {
                $student_role_id = $input['role_id'];
            } else {
                $role = RoleSetup::first();
                if (!$role) {
                    throw new \Exception("Role-not found for this user!");
                    //                    return response()->json(['success' => false, 'message' => 'Role not found for this user'], $this->failedStatus);
                }
                $student_role_id = $role->student_role_id;
            }



            $rand_pass = Str::random(8);
            $hashed_random_password = Hash::make($rand_pass);

            $isAvailable = UserProfile::where('email', '=', $input['email'])->exists();

            if ($isAvailable) {
                $previous_user = UserProfile::where('email', '=', $input['email'])->get();
                $previous_student = User::where("id", $previous_user[0]->user_id)->first();

                $previous_student->password = $hashed_random_password;
                $previous_student->update();

                // $token = $previous_user->createToken('Exam Instruction')->accessToken;
                $token = $this->getToken($input['assessment_id'], $input['institute_id'], $previous_user[0]->id, $input['email'], $previous_student->username, $rand_pass);
                $newCandidate = [
                    'question_set_id' => $input['assessment_id'],
                    'profile_id' => $previous_user[0]->id,
                    'attended' => 0,
                    'token' => $token
                ];
                QuestionSetCandidate::create($newCandidate);



                $url = env('FRONT_END_BASE') . '/#/exam-instruction/' . $input['assessment_id'] . '/' . $input['institute_id'] . '/' . $previous_user[0]->id . '/' . $token;

                $candidate_email_data = [
                    'url' => $url,
                    'assessment_start_time' => $input['assessment_start_time'],
                    'candidate_name' => $input['first_name'] . ' ' . $input['last_name'],
                    'email' => $input['email'],

                ];




                // Email Server Related Code
                $delay = 2; //seconds delay for email sending

                if (!($this->ExamInfoEmail(
                    $candidate_email_data,
                    $delay
                ))) {
                    throw new \Exception('Email-Server may be down!');
                }


                return response()->json(['success' => true, "message" => "Candidate Already Exist and Email Sent"], $this->successStatus);
            }


            $username = $this->uniqueUser($input['first_name'], $input['last_name']);

            $user_data = [
                'first_name' => $input['first_name'],
                'last_name' => $input['last_name'],
                'name' => $input['first_name'] . ' ' . $input['last_name'],
                'username' => $username,
                'email' => $input['email'],
                'status' => 1,
                'password' => $hashed_random_password,
                'phone' => $input['phone'],
                // 'birth_date' => $input['birth_date'],
                'skype' => (!empty($input["skype"])) ? $input['skype'] : 0,
                'profession' => (!empty($input["profession"])) ? $input['profession'] : 'n/a',
                'skill' => (!empty($input["skill"])) ? $input['skill'] : 'n/a',
                'about' => (!empty($input["about"])) ? $input['about'] : 'n/a',
                'img' => (!empty($input["img"])) ? $input['img'] : '',
                'address' => (!empty($input["address"])) ? $input['address'] : 'n/a',
                'institute_id' => (!(empty($input['institute_id'] or is_null($input['institute_id']))) ? $input['institute_id'] : null),
                // 'zipcode' => $input['zipcode'],
                'zipcode' => (!empty($input["zipcode"])) ? $input['zipcode'] : null,
                'country' => (!empty($input["country"])) ? $input['country'] : null,
                // 'country' => $input['country'],
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
                $user->assignRole($student_role_id);
                $user_data['user_id'] = $user->id;
                $user_profile = UserProfile::create($user_data);
                $user_data['profile_id'] = $user_profile->id;
                $student = Student::create($user_data);
                $contributor = Contributor::create($user_data);
                $token = $this->getToken($input['assessment_id'], $input['institute_id'], $user_profile->id, $input['email'], $username, $rand_pass);
                $candidate_data = [
                    'question_set_id' => $input['assessment_id'],
                    'profile_id' => $user_profile->id,
                    'attended' => 0,
                    'token' => $token
                ];


                $candidate = QuestionSetCandidate::create($candidate_data);

                // Email Sending Functionality

                // $token = $user_profile->createToken('Exam Instruction')->accessToken;

                $url = env('FRONT_END_BASE') . '/exam-instruction/' . $input['assessment_id'] . '/' . $input['institute_id'] . '/' . $user_profile->id . '/' . $token;

                $candidate_email_data = [
                    'url' => $url,
                    'assessment_start_time' => $input['assessment_start_time'],
                    'candidate_name' => $input['first_name'] . ' ' . $input['last_name'],
                    'email' => $input['email'],

                ];


                // return response()->json($candidate_email_data);

                // Mail::to('saifmahmudkhandourjoy@gmail.com')->send(new ExamInfo());
                // dispatch(new ExamInfoSend());

                // Email Server Related Code
                $delay = 2; //seconds delay for email sending

                if (!($this->ExamInfoEmail(
                    $candidate_email_data,
                    $delay
                ))) {
                    throw new \Exception('Email-Server may be down!');
                }


                // if (!($this->singleUserCredential($user_data['email'], $user_data['first_name'], $user_data['last_name'], $user_data['username'], $rand_pass, $delay)))
                //     throw new \Exception('Email-Server may be down!');
            } catch (\Exception $e) {
                DB::rollback();
                Log::channel("ac_error")->info(__CLASS__ . "@" . __FUNCTION__ . "# Creating unique use is Unsuccessful, rolling back db-operation! error: " . $e->getMessage());
                return response()->json(['success' => false, 'message' => 'Student Creation unsuccessful!', 'error' => $e->getMessage()], $this->failedStatus);
            }
            Db::commit();
            $student_user = Student::with(['user_profile'])->where('id', $student->id)->get();
            Log::channel("ac_info")->info(__CLASS__ . "@" . __FUNCTION__ . "# Creating studnet is successful.");
            return response()->json(['success' => true, 'student' => $student_user], $this->successStatus);
        } catch (\Exception $e) {
            Log::channel("ac_error")->info(__CLASS__ . "@" . __FUNCTION__ . "# Unable to create student! error: " . $e->getMessage());
            return response()->json(['success' => false, "message" => "Student creation unsuccessful!", "error" => $e->getMessage()], $this->failedStatus);
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
            Log::channel("ac_info")->info(__CLASS__ . "@" . __FUNCTION__ . "# Fetching student-profile by profile-id: " . $id);
            $student = Student::with('user_profile')
                ->where('id', $id)
                ->get();
            if (!$student)
                throw new \Exception("Student now found!");
            //                return response()->json(['success' => false, 'message' => 'Student not found'], $this->invalidStatus);
            Log::channel("ac_info")->info(__CLASS__ . "@" . __FUNCTION__ . "# Successfully fetched student-profile by profile-id: " . $id);
            return response()->json(['success' => true, 'student' => $student], $this->successStatus);
        } catch (\Exception $e) {
            Log::channel("ac_error")->info(__CLASS__ . "@" . __FUNCTION__ . "# Fetching student is Unsuccessful! error: " . $e->getMessage());
            return response()->json(['success' => false, "message" => "Unable to Fetch student id: $id", "error" => $e->getMessage()], $this->failedStatus);
        }
    }

    public function studentByUid($uid)
    {
        try {
            Log::channel("ac_info")->info(__CLASS__ . "@" . __FUNCTION__ . "# Fetching student-profile by user-id: " . $uid);
            $userProfile = UserProfile::with("student")->where('user_id', $uid)->first();
            if (!$userProfile)
                throw new \Exception("User profile not found!");
            //                return response()->json(['success'=>false, "message"=>"User Profile Not Found!"], $this->invalidStatus);
            Log::channel("ac_info")->info(__CLASS__ . "@" . __FUNCTION__ . "# Fetching student-profile by user-id: " . $uid);
            return $this->show($userProfile->student->id);
        } catch (\Exception $e) {
            Log::channel("ac_info")->info(__CLASS__ . "@" . __FUNCTION__ . "# Fetching student is Unsuccessful! error: " . $e->getMessage());
            return response()->json(['success' => false, "message" => "Unable to fetch student-user id: $uid", "error" => $e->getMessage()], $this->failedStatus);
        }
    }



    /**
     * Display the specified resource.
     *
     * @param $id
     * @return JsonResponse
     */
    public function getStudent($id)
    {
        try {
            Log::channel("ac_info")->info(__CLASS__ . "@" . __FUNCTION__ . "# Fetching student-profile by profile-id: " . $id);
            $student = Student::with(['user_profile'])->where('profile_id', $id)->get();
            if (!$student)
                throw new \Exception("Student not found");
            //                return response()->json(['success' => false, 'message' => 'Student not found'], $this->invalidStatus);
            Log::channel("ac_info")->info(__CLASS__ . "@" . __FUNCTION__ . "# Successfully fetched student-profile by profile-id: " . $id);
            return response()->json(['success' => true, 'student' => $student], $this->successStatus);
        } catch (\Exception $e) {
            Log::channel("ac_error")->info(__CLASS__ . "@" . __FUNCTION__ . "# Fetching student-profile by profile-id: " . $id . "! error: " . $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     *
     * @param $id
     * @return JsonResponse
     */
    public function getAllAssessment($id)
    {
        try {
            Log::channel("ac_info")->info(__CLASS__ . "@" . __FUNCTION__ . "# Fetching student-wise assessments by profile-id: " . $id);
            $student = Student::where('profile_id', $id)->first();
            if (!$student)
                throw new \Exception("Student Not found!");
            $assessment = QuestionSetAnswer::with(['question_set_answer_details'])->where('profile_id', $id)->get();
            Log::channel("ac_info")->info(__CLASS__ . "@" . __FUNCTION__ . "# Successfully fetched student wise assessment by profile-id: " . $id);
            return response()->json(['success' => true, 'all_assessment' => $assessment], $this->successStatus);
        } catch (\Exception $e) {
            Log::channel("ac_error")->info(__CLASS__ . "@" . __FUNCTION__ . "# Unable to fetch question sets by profile-id: " . $id . "! error: " . $e->getMessage());
            return response()->json(["success" => false, "message" => "Fetching Assessments fo student is Unsuccessful!", "error" => $e->getMessage()], $this->failedStatus);
        }
    }

    public function getAllAssessmentByUid($uid)
    {
        try {
            Log::channel("ac_info")->info(__CLASS__ . "@" . __FUNCTION__ . "# Fetching student-wise assessments by user-id: " . $uid);
            $userProfile = UserProfile::where('user_id', $uid)->first();
            if (!$userProfile)
                return response()->json(['success' => false, "message" => "User Profile Not Found!"], $this->invalidStatus);
            Log::channel("ac_info")->info(__CLASS__ . "@" . __FUNCTION__ . "# Fetching student-wise assessments by user-id: " . $uid);
            return $this->getAllAssessment($userProfile->id);
        } catch (\Exception $e) {
            Log::channel("ac_error")->info(__CLASS__ . "@" . __FUNCTION__ . "# Unable to fetch question sets by user-id: " . $uid . "! error: " . $e->getMessage());
            return response()->json(['success' => false, "message" => "Fetching All Assessments by User Id Unsuccessful!", "error" => $e->getMessage()], $this->failedStatus);
        }
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
        try {
            Log::channel("ac_info")->info(__CLASS__ . "@" . __FUNCTION__ . "# Updating student by profile-id: " . $id);
            $profile = Student::where('id', $id)->first();
            $student = UserProfile::find($profile->profile_id);
            request()->validate([
                //'email' => 'unique:user_profiles,email,'.$id,
                //'phone' => 'unique:user_profiles,phone,'.$id,
            ]);
            $studentUpdate = $student->update($request->all());
            $input = request()->all();
            $input['name'] = $input['first_name'] . ' ' . $input['last_name'];
            $userUpdate = User::find($student->user_id);
            $userUpdate->update($input);
            if (!$studentUpdate)
                throw new \Exception("Update failed!");
            Log::channel("ac_info")->info(__CLASS__ . "@" . __FUNCTION__ . "# Successfully updated student by profile-id: " . $id);
            return response()->json(['success' => true, 'message' => 'Student update successfully'], $this->successStatus);
        } catch (\Exception $e) {
            Log::channel("ac_error")->info(__CLASS__ . "@" . __FUNCTION__ . "# Unable to update students by profile-id: " . $id . "! error: " . $e->getMessage());
            return response()->json(['success' => false, "message" => "Updating student is un-successful!", "error" => $e->getMessage()], $this->failedStatus);
        }
    }


    /**
     * Remove the specified resource from storage.
     *
     * @param $id
     * @return JsonResponse
     */
    public function destroy($id)
    {
        try {
            Log::channel("ac_info")->info(__CLASS__ . "@" . __FUNCTION__ . "# Deleting student by profile-id: " . $id);
            $student = Student::find($id);
            if (!$student)
                throw new \Exception("Student not found!");
            //            return response()->json(['success' => false, 'message' => 'Student not found'], $this->invalidStatus);
            if (!$student->delete())
                throw new \Exception("Student deletion is unsuccessful!");
            Log::channel("ac_info")->info(__CLASS__ . "@" . __FUNCTION__ . "# Successfully Deleted student by profile-id: " . $id);
            return response()->json(['success' => true, 'message' => 'Student deleted'], $this->successStatus);
        } catch (\Exception $e) {
            Log::channel("ac_error")->info(__CLASS__ . "@" . __FUNCTION__ . "# Unable to Delete student by profile-id: " . $id . "! error: " . $e->getMessage());
            return response()->json(['success' => false, "message" => "Student deletion is unsuccessful!", "error" => $e->getMessage()], $this->failedStatus);
        }
    }

    public function validKey($keys)
    {
        try {
            Log::channel("ac_info")->info(__CLASS__ . "@" . __FUNCTION__ . "# Validating key for bulk entry.");
            $reform_keys = [];
            $first_name_keys_set = ['first name', 'firstname', 'first-name', 'first_name'];
            $last_name_keys_set = ['last name', 'last-name', 'last_name', 'lastname'];
            $inst_keys_set = array('institution', 'institution name', 'institute', 'institute-name', 'institution-name', 'institution_name', 'institute_name', 'school', 'college', 'school/college', 'university');
            $phone_keys_set = array('phone', 'phone no.', 'phone no', 'phone-no', 'mobile', 'mobile no.', 'mobile-no', 'mobile no', 'contact no', 'cell no');
            $zip_codes_set = array('zip-code', 'zipcode', 'zip code', 'zip', 'post', 'post-code');
            $birth_dates_set = array('birth_date', 'birth date', 'birth-date', 'birth');
            foreach ($keys as $key) {
                if (in_array(strtolower(trim($key)), $first_name_keys_set))
                    array_push($reform_keys, 'first_name');
                else if (in_array(strtolower(trim($key)), $last_name_keys_set))
                    array_push($reform_keys, 'last_name');
                else if (strtolower(trim($key)) == 'email')
                    array_push($reform_keys, 'email');
                else if (in_array(strtolower(trim($key)), $inst_keys_set))
                    array_push($reform_keys, 'institute_name');
                else if (in_array(strtolower(trim($key)), $phone_keys_set))
                    array_push($reform_keys, 'phone');
                else if (strtolower(trim($key)) == 'class')
                    array_push($reform_keys, 'class');
                else if (strtolower(trim($key)) == 'skype')
                    array_push($reform_keys, 'skype');
                else if (strtolower(trim($key)) == 'profession')
                    array_push($reform_keys, 'profession');
                else if (strtolower(trim($key)) == 'about')
                    array_push($reform_keys, 'about');
                else if (strtolower(trim($key)) == 'address')
                    array_push($reform_keys, 'address');
                else if (in_array(strtolower(trim($key)), $zip_codes_set))
                    array_push($reform_keys, 'zipcode');
                else if (strtolower(trim($key)) == 'country')
                    array_push($reform_keys, 'country');
                else if (in_array(strtolower(trim($key)), $birth_dates_set))
                    array_push($reform_keys, 'birth_date');
            }
            Log::channel("ac_info")->info(__CLASS__ . "@" . __FUNCTION__ . "# Successfully validate key for bulk entry.");
            return $reform_keys;
        } catch (\Exception $e) {
            Log::channel("ac_error")->info(__CLASS__ . "@" . __FUNCTION__ . "# Unable to validate key for bulk-entry! error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * For converting csv to json
     * @param $fname
     * @return false|string
     */

    function csvToJson($fname)
    {
        try {
            Log::channel("ac_info")->info(__CLASS__ . "@" . __FUNCTION__ . "# Converting csv to json.");
            if (!($fp = fopen($fname, 'r'))) {
                die("Can't open file...");
            }
            $key = fgetcsv($fp, "1024", ",");
            $valid_key = $this->validKey($key);
            $this->out->writeln('key: ' . sizeof($valid_key));
            $json = array();
            while ($row = fgetcsv($fp, "1024", ",")) {
                if (sizeof($row) != sizeof($valid_key)) {
                    $this->out->writeln("CSV Keys and items aren't matching!");
                    return false;
                }
                $json[] = array_combine($valid_key, $row);
            }
            $this->out->writeln('CSV to json convertion succesful');
            fclose($fp);
            Log::channel("ac_info")->info(__CLASS__ . "@" . __FUNCTION__ . "# Successfully converted csv to json.");
            return $json;
        } catch (\Exception $e) {
            Log::channel("ac_error")->info(__CLASS__ . "@" . __FUNCTION__ . "# Unable to convert csv to json for bulk-entry! error: " . $e->getMessage());
            return null;
        }
    }

    public function checkSimilarity($student)
    {
        if (UserProfile::where('first_name', '=', $student['first_name'])
            ->where('last_name', '=', $student['last_name'])
            ->where('email', '=', $student['email'])
            ->where('phone', '=', $student['phone'])
            ->exists()
        ) {
            return true;
        }
        return false;
    }

    public function bulkEntry(Request $request)
    {
        try {
            Log::channel("ac_info")->info(__CLASS__ . "@" . __FUNCTION__ . "# Student bulk-entry...");
            set_time_limit(8000000);
            $start_time = time();
            $default_status = 1;
            request()->validate([
                'institute_id' => 'required',
                'user_role' => 'required',
            ]);
            $input = $request->all();
            $this->out->writeln('Student Mass entry processing...');
            $user = UserProfile::where('user_id', '=', Auth::id())->first();
            $timestamp = date('y_m_d_h_m_s', time());
            $file_name = $timestamp . "_" . $user->user_id . ".csv";
            $path = $request->file('students')->storeAs('students', $file_name);
            $students_path = Storage::path($path);
            if (!($fp = fopen($students_path, 'r'))) {
                Log::channel("ac_error")->info(__CLASS__ . "@" . __FUNCTION__ . "# Can't open file!");
                return response()->json(['success' => false, 'message' => "Can't Open file!"], $this->failedStatus);
            }
            $keys = fgetcsv($fp);
            $success_content = $this->fileContent('', $keys);
            $failed_content = $this->fileContent('', $keys);
            $students = $this->csvToJson($students_path);
            if (!$students) {
                Log::channel("ac_error")->info(__CLASS__ . "@" . __FUNCTION__ . "# File contains invalid column name!");
                return response()->json(['success' => false, 'message' => "File has invalid column name!", 'columns' => $success_content], $this->invalidStatus);
            }
            $student_success = [];
            $student_rol_id = RoleSetup::select('student_role_id')->first()['student_role_id'];
            $students_credential = array();  // For sending students credential to email-server
            foreach ($students as $student) {
                $row = fgetcsv($fp);
                $this->out->writeln($student);
                if (empty($student['first_name']) || empty($student['last_name']) || empty($student['email']) || empty($student['phone'])) {
                    continue;
                }
                // Auto generate password
                $rand_pass = Str::random(8);
                $rand_pass = "123456789";
                $hashed_random_password = Hash::make($rand_pass);
                $student_data = [
                    'first_name' => trim($student['first_name']),
                    'last_name' => trim($student['last_name']),
                    'name' => $student['first_name'] . ' ' . $student['last_name'],
                    'username' => $this->uniqueUser($student['first_name'], $student['last_name']),
                    'status' => $default_status,
                    'password' => $hashed_random_password,
                    'institute_id' => (!empty($input['institute_id']) ? $input['institute_id'] : $user->institute_id),
                    'email' => trim($student['email']),
                    'phone' => trim($student['phone']),
                    'birth_date' => date('d-m-y', strtotime($student['birth_date'])),
                    'skype' => (!empty($student['skype']) ? $student['email'] : ''),
                    'profession' => (!empty($student['profession']) ? $student['profession'] : ''),
                    'skill' => (!empty($student['skill']) ? $student['skill'] : ''),
                    'about' => (!empty($student['about']) ? $student['about'] : ''),
                    'img' => (!empty($student['img']) ? $student['img'] : ''),
                    'address' => (!empty($student['address']) ? $student['address'] : ''),
                    'zipcode' => (!empty($student['zipcode']) ? $student['zipcode'] : ''),
                    'country' => (!empty($student['country']) ? $student['country'] : ''),
                    //academic info
                    'exam_course_title' => (!empty($student['class']) ? $student['class'] : ''),
                    'institute' => (!empty($student['institute_name']) ? $student['institute_name'] : ''),
                    // contributor info
                    'completing_percentage' => 100,
                    'total_question' => 0,
                    'average_rating' => 0,
                    'approve_status' => 0,
                    'active_status' => 0,
                    //student info
                    'total_complete_assessment' => 0,
                    'guard_name' => 'web',
                ];
                DB::beginTransaction();
                try {
                    $user = User::create($student_data);
                    $user->assignRole($student_rol_id);
                    $student_data['user_id'] = $user->id;
                    $student_profile = UserProfile::create($student_data);
                    $student_data['profile_id'] = $student_profile->id;
                    $student = Student::create($student_data);
                    $contributor = Contributor::create($student_data);
                    $student_academic_info = UserAcademicHistory::create($student_data);
                    //                if(!$this->emailCredential($user->username,$user->name, $rand_pass, $user->email))
                    //                    throw new \Exception('User email may incorrect!');
                } catch (\Exception $e) {
                    Log::channel("ac_error")->info(__CLASS__ . "@" . __FUNCTION__ . "# Exception in student bulk-entry, rolling back specific entry! error: " . $e->getMessage());
                    DB::rollback();
                    $failed_content = $this->fileContent($failed_content, $row);
                    continue;
                }
                Db::commit();
                array_push($students_credential, [
                    'email' => $student_data['email'],
                    'first_name' => $student_data['first_name'],
                    'last_name' => $student_data['last_name'],
                    'name' => $student_data['name'],
                    'username' => $student_data['username'],
                    'password' => $rand_pass,
                ]);
                Log::channel("ac_info")->info(__CLASS__ . "@" . __FUNCTION__ . "# Successfully entered one student! username: " . $student_data['username']);
                $success_content = $this->fileContent($success_content, $row);
                array_push($student_success, $student_profile);
            }
            $student_temp = Storage::put("students/$timestamp" . "_$user->user_id" . "_success.csv", $success_content);
            $student_temp = Storage::put("students/$timestamp" . "_$user->user_id" . "_failed.csv", $failed_content);
            if (!$this->bulkEmailCredential($students_credential))
                return response()->json(['success' => true, 'success_students' => $success_content, "failed_students" => $failed_content, "warning" => "Email server may be down!"], $this->successStatus);
            $time_taken = time() - $start_time;
            $this->out->writeln("Time Taken: $time_taken");
            return response()->json(['success' => true, 'time_taken' => $time_taken, 'success_students' => $success_content, "failed_students" => $failed_content], $this->successStatus);
        } catch (\Exception $e) {
            Log::channel("ac_error")->info(__CLASS__ . "@" . __FUNCTION__ . "# Failed student bulk-entry! error: " . $e->getMessage());
            return response()->json(['success' => false, "message" => "Bulk Entry Failed", "error" => $e->getMessage()], $this->failedStatus);
        }
    }

    public function bulkEmailCredential($students)
    {
        try {
            Log::channel("ac_info")->info(__CLASS__ . "@" . __FUNCTION__ . "# Sending credential for Bulk-entry.");
            $body = [
                "students" => $students,
                "delay" => env("BULK_EMAIL_DELAY"),
            ];
            $this->out->writeln("Sending credential for Bulk-entry.");
            $url = env("EMAIL_SERVER_URL") . 'bulk-entry-credential';
            $client = new Client();
            $response = $client->post($url, ["form_params" => $body, 'http_errors' => false]);
            if ($response->getStatusCode() != 200)
                throw new \Exception("Unable to send Bulk-credential to students! Check your email.");
            Log::channel("ac_info")->info(__CLASS__ . "@" . __FUNCTION__ . "# Successfully sent credential for Bulk-entry.");
            return true;
        } catch (\Exception $e) {
            $this->out->writeln("Unable to send Bulk-credential to students! error: " . $e->getMessage());
            Log::channel("ac_error")->info(__CLASS__ . "@" . __FUNCTION__ . "# Unable to sent information for bulk-entry! error: " . $e->getMessage());
            return false;
        }
    }

    public function fileContent($content, $items)
    {
        for ($i = 0; $i < sizeof($items); $i++) {
            $content = $content . $items[$i] . ",";
        }
        $content[strlen($content) - 1] = "\r\n";
        return $content;
    }

    public function studentCredential($profileId)
    {
        try {
            $userProfile = UserProfile::find($profileId);
            $rand_pass = Str::random(8);
            $rand_pass = "123456789";
            $hashed_random_password = Hash::make($rand_pass);
            User::where('id', $userProfile->user_id)->update(['password' => $hashed_random_password]);
        } catch (\Exception $e) {
            $this->out->writeln("Unable to Email Student Credential! error: " . $e->getMessage());
            return response()->json(['success' => false, "message" => "Unable to Email Student Credential!", "error" => $e->getMessage()], $this->failedStatus);
        }
    }
}
