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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use \Symfony\Component\Console\Output\ConsoleOutput;

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

    public $out;
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest');
        $this->out = new ConsoleOutput();
    }


    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function register(Request $request)
    {
        $this->out->writeln("Registering new user...");
        request()->validate([
            'username'=>'required|unique:users',
            'email' => 'required|email',
            'password' => 'required',
            'c_password' => 'required|same:password',
            'birth_date'=>'required',
        ]);
        try{
            $input = $request->all();
            $role = RoleSetup::first();
            if(!$role)
                throw new \Exception("Role not Found for this user!");
            if(isset($input['role_id']))
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
                'password' => $input['password'],
                'phone'=>$input['phone'],
                'birth_date'=>$input['birth_date'],
                'skype' => (isset($input["skype"])) ? $input['skype'] : 0,
                'profession' => (isset($input["profession"])) ? $input['profession'] : 'n/a',
                'skill' => (isset($input["skill"])) ? $input['skill'] : 'n/a',
                'about' => (isset($input["about"])) ? $input['about'] : 'n/a',
                'img' => (isset($input["img"])) ? $input['img'] : '',
                'address' => (isset($input["address"])) ? $input['address'] : 'n/a',
                'institute_id'=>(isset($input['institute_id'])? $input['institute_id']:'1'),
                'zipcode' => (isset($input["zipcode"])) ? $input['zipcode'] : 0,
                'country' => (isset($input["country"])) ? $input['country'] : 0,
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
            }catch(\Exception $e){
                DB::rollback();
                return response()->json(['success'=>false, 'message'=>'User Registration unsuccessful!','error'=>$e->getMessage()],$this->failedStatus);
            }
            Db::commit();
            return response()->json(['success' => true, 'message' =>"User Registration Successful"], $this->successStatus);
        }catch (\Exception $e){
            $this->out->writeln("User Registration is unsuccessful! error: ".$e->getMessage());
            return response()->json(['success'=>true, "message"=>"User Registration incomplete!", "error"=>$e->getMessage()], $this->failedStatus);
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
