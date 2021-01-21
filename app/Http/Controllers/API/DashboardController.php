<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\QuestionSet;
use App\QuestionSetAnswer;
use App\RoleSetup;
use App\User;
use App\UserProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class DashboardController extends Controller
{
    public $successStatus = 200;
    public $failedStatus = 500;
    public $invalidStatus = 400;

    public $out;

    public function __construct(){
        $this->out = new \Symfony\Component\Console\Output\ConsoleOutput();
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
//        $this->out->writeln("User profile: $userProfile");
        if($user->can('super-admin')){
            $this->out->writeln('Super Admin Dashboard: '.$user->id);
            $total_institute = DB::table('institutes')->count();
            $total_user = DB::table('users')->count();
            $total_assessment = DB::table('question_sets')->count();
            $total_attendant = DB::table('question_set_answers')->count();
            $data = [
                'total_institute' => $total_institute,
                'total_user' => $total_user,
                'total_assessment' => $total_assessment,
                'total_attendant' => $total_attendant
            ];
            return response()->json(['success' => true, 'data' => $data], $this-> successStatus);
        }
        if($userProfile->institute_id){
            $student_role_setup = RoleSetup::first();
            $student_role = Role::where('id',$student_role_setup->student_role_id)->first();
            $students = User::with(['user_profile'])->role($student_role->name)->get();
//            return $students;
            $total_student=0;
            for($i=0;$i<sizeof($students);$i++){
//                $this->out->writeln('Student: '.$students[$i]);
                if($students[$i]->user_profile->institute_id==$userProfile->institute_id){
                    $this->out->writeln('total student: '.$total_student);
                    $total_student++;
                    continue;
                }
            }
            $total_user = UserProfile::where('institute_id','=',$userProfile->institute_id)->count();
            $total_assessment = QuestionSet::where('institute_id','=',$userProfile->institute_id)->count();
            $total_attendant = UserProfile::with('question_set_answer')->where('institute_id','=',$userProfile->institute_id)->count();
            $total_attendant = QuestionSetAnswer::whereIn('profile_id',UserProfile::where('institute_id','=',$userProfile->institute_id)->get('id'))->count();
            $data = [
                'total_institute' => null,
                'total_user' => $total_user,
                'total_assessment' => $total_assessment,
                'total_attendant' => $total_attendant,
                'total_student'=>$total_student,
            ];
            return response()->json(['success' => true, 'data' => $data], $this-> successStatus);
        }


        $total_institute = DB::table('institutes')->count();
        $total_user = DB::table('users')->count();
        $total_assessment = DB::table('question_sets')->count();
        $total_attendant = DB::table('question_set_answers')->count();
        $data = [
            'total_institute' => $total_institute,
            'total_user' => $total_user,
            'total_assessment' => $total_assessment,
            'total_attendant' => $total_attendant
        ];
        return response()->json(['success' => true, 'data' => $data], $this-> successStatus);
    }
}
