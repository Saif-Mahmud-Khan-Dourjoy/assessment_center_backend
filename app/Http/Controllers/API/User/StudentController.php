<?php

namespace App\Http\Controllers\API\User;

use App\Contributor;
use App\QuestionSetAnswer;
use App\Student;
use App\Http\Controllers\Controller;
use App\RoleSetup;
use App\User;
use App\UserProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class StudentController extends Controller
{
    public $successStatus = 200;
    public $failedStatus = 500;
    public $invalidStatus = 400;
    function __construct()
    {
        /*$this->middleware('api_permission:student-list|student-create|student-edit|student-delete', ['only' => ['index','show']]);
        $this->middleware('api_permission:student-create', ['only' => ['store']]);
        $this->middleware('api_permission:student-edit', ['only' => ['update']]);
        $this->middleware('api_permission:student-delete', ['only' => ['destroy']]);*/
    }


    /**
     * Display a listing of the resource.
     *
     * @return JsonResponse
     */
    public function index()
    {
        $students = Student::with(['user_profile'])->get();
        return response()->json(['success' => true, 'students' => $students], $this-> successStatus);
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
            //'phone' => 'required|unique:user_profiles',
        ]);
        $input = $request->all();
        $roleID = (!empty($_POST["role_id"])) ? $input['role_id'] : 0;
        if($roleID){
            $student_role_id = $input['role_id'];
        }else{
            $role = RoleSetup::first();
            if( !$role ){
                return response()->json(['success' => false, 'message' => 'Role not found for this user'], $this->failedStatus);
            }
            $student_role_id = $role->student_role_id;
        }

        // Add Login Info
        $login_data = [
            'name' => $input['first_name'] .' '. $input['last_name'],
            'email' => $input['email'],
            'status' => 1,
            'password' => Hash::make('123456789'),
        ];
        $user = User::create($login_data);

        // Add User Profile
        $data = [
            'user_id' => $user->id,
            'institute_id' => (!empty($_POST["institute_id"])) ? $input['institute_id'] : NULL,
            'first_name' => $input['first_name'],
            'last_name' => $input['last_name'],
            'email' => $input['email'],
            'phone' => $input['phone'],
            'skype' => (!empty($_POST["skype"])) ? $input['skype'] : 0,
            'profession' => (!empty($_POST["profession"])) ? $input['profession'] : 'n/a',
            'skill' => (!empty($_POST["skill"])) ? $input['skill'] : 'n/a',
            'about' => (!empty($_POST["about"])) ? $input['about'] : 'n/a',
            'image' => (!empty($_POST["image"])) ? $input['image'] : '',
            'address' => (!empty($_POST["address"])) ? $input['address'] : 'n/a',
            'zipcode' => $input['zipcode'],
            'country' => $input['country'],
            'guard_name' => 'web',
        ];

        // Assign Role
        //$role = RoleSetup::first();
        $user->assignRole($student_role_id);

        $user_profile = UserProfile::create( $data );
        if( $user_profile ){

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

            $user =Student::with(['user_profile'])->where('id', $student->id)->get();

            if( $student ){
                return response()->json(['success' => true, 'student' => $user], $this->successStatus);
            }
        }
        else{
            return response()->json(['success' => false, 'message' => 'Student added fail'], $this->failedStatus);
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
        $student = Student::with('user_profile')
            ->where('id', $id)
            ->get();
        if ( !$student )
            return response()->json(['success' => false, 'message' => 'Student not found'], $this->invalidStatus);
        else
            return response()->json(['success' => true, 'student' => $student], $this->successStatus);
    }


    /**
     * Display the specified resource.
     *
     * @param $id
     * @return JsonResponse
     */
    public function getStudent($id)
    {
        $student = Student::with(['user_profile'])->where('profile_id', $id)->get();
        if ( !$student )
            return response()->json(['success' => false, 'message' => 'Student not found'], $this->invalidStatus);
        else
            return response()->json(['success' => true, 'student' => $student], $this->successStatus);
    }

    /**
     * Display the specified resource.
     *
     * @param $id
     * @return JsonResponse
     */
    public function getAllAssessment($id)
    {
        $student = Student::where('profile_id', $id)->first();
        if ( $student ){
            $assessment = QuestionSetAnswer::with(['question_set_answer_details'])->where('profile_id', $id)->get();
            return response()->json(['success' => true, 'all_assessment' => $assessment], $this->successStatus);
        }
        else{
            return response()->json(['success' => false, 'message' => 'Student assessment not found'], $this->invalidStatus);
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
        $profile = Student::where('id', $id)->first();
        $student = UserProfile::find($profile->profile_id);
        request()->validate([
            //'email' => 'unique:user_profiles,email,'.$id,
            //'phone' => 'unique:user_profiles,phone,'.$id,
        ]);
        $student = $student->update($request->all());
        if( $student )
            return response()->json(['success' => true, 'message' => 'Student update successfully'], $this->successStatus);
        else
            return response()->json(['success' => false, 'message' => 'Student update failed'], $this->failedStatus);
    }


    /**
     * Remove the specified resource from storage.
     *
     * @param $id
     * @return JsonResponse
     */
    public function destroy($id)
    {
        $student = Student::find($id);
        if ( !$student )
            return response()->json(['success' => false, 'message' => 'Student not found'], $this->invalidStatus);

        if ( $student->delete() )
            return response()->json(['success' => true, 'message' => 'Student deleted'], $this->successStatus);
        else
            return response()->json(['success' => false, 'message' => 'Student can not be deleted'], $this->failedStatus);

    }
}
