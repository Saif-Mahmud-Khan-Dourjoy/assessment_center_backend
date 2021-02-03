<?php

namespace App\Http\Controllers\API\User;

use App\Mail\UserCredentials;
use App\Question;
use App\RoleSetup;
use App\Student;
use App\User;
use App\UserProfile;
use App\Http\Controllers\Controller;
use App\Contributor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class ContributorController extends Controller
{
    public $successStatus = 200;
    public $failedStatus = 500;
    public $invalidStatus = 400;

    public $out;
    function __construct()
    {
//        $this->middleware('api_permission:contributor-list|contributor-create|contributor-edit|contributor-delete', ['only' => ['index','show']]);
//        $this->middleware('api_permission:contributor-create', ['only' => ['store']]);
//        $this->middleware('api_permission:contributor-edit', ['only' => ['update']]);
//        $this->middleware('api_permission:contributor-delete', ['only' => ['destroy']]);
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
        $userProfile=UserProfile::find($user->id);
        $userProfile =$userProfile::where('user_id','=',$user->id)->first();
        $permissions = $user->getAllPermissions();
        if($user->can('super-admin')){
            $contributors = Contributor::with(['user_profile'])->where('profile_id','!=',$userProfile->id)->get();
            return response()->json(['success'=>true,'contributors'=>$contributors],$this->successStatus);
        }
        if($userProfile->institute_id){
            $contributors = UserProfile::with(['contributor'])->where('id','!=',$userProfile->id)
                ->where('institute_id','=',$userProfile->institute_id)
                ->get();
            return response()->json(['success'=>true,'contributors'=>$contributors],$this->successStatus);
        }
        return response()->json(['success'=>true,'contributors'=>[]],$this->successStatus);
    }


    /**
     * Sen User his credential to his email
     * @param $username, $user_password, $user_email
     * @return True/False
     */

    public function emailCredential($username, $name,  $user_password, $user_email){
        $this->out->writeln('Emailing user credentials');
        try{
            // $email = env('TO_EMAIL');
            $this->out->writeln('Email: '.$user_email);
            Mail::to($user_email)
                ->send(new UserCredentials($username, $name, $user_password, $user_email));
            return true;
        }catch(Throwable $e){
            $this->out->writeln('Unable to email user credentials, for '.$e);
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
            'first_name' => 'required',
            'last_name' => 'required',
            'username'=> 'required|unique:users',
            'email' => 'required|email',
            'birth_date'=>'required',
            //'phone' => 'required|unique:user_profiles',
        ]);
        $input = $request->all();
        $roleID = (!empty($_POST["role_id"])) ? $input['role_id'] : 0;
        if($roleID){
            $contributor_role_id = $roleID;
        }else{
            $role = RoleSetup::first();
            if( !$role ){
                return response()->json(['success' => false, 'message' => 'Role not found for this user'], $this->failedStatus);
            }
            $contributor_role_id = $role->contributor_role_id;
        }

        // Auto generate password
        $rand_pass = Str::random(8);
        $hashed_random_password = Hash::make($rand_pass);

        // Add Login Info
        $login_data = [
            'name' => $input['first_name'] .' '. $input['last_name'],
            'username'=> $input['username'],
            'email' => $input['email'],
            'status' => 1,
            'password' => $hashed_random_password,
        ];
        $user = User::create($login_data);
        if(!$user){
            return response()->json(['success'=>false, 'message'=>'Unable to register student'],$this->failedStatus);
        }

        //Send Email
        if(!($this->emailCredential($user->username, $login_data['name'],  $rand_pass, $user->email))){
            $user->delete();
            $this->out->writeln('User deleted successfully due to unsend email');
            return response()->json(['success'=>false, 'message'=>'Unable to send email'],$this->successStatus);
        }

        // Add User Profile
        $data = [
            'user_id' => $user->id,
            'institute_id' => (!empty($_POST["institute_id"])) ? $input['institute_id'] : NULL,
            'first_name' => $input['first_name'],
            'last_name' => $input['last_name'],
            'email' => $input['email'],
            'phone' => $input['phone'],
            'birth_date'=>$input['birth_date'],
            'skype' => (!empty($_POST["skype"])) ? $input['skype'] : 0,
            'profession' => (!empty($_POST["profession"])) ? $input['profession'] : 'n/a',
            'skill' => (!empty($_POST["skill"])) ? $input['skill'] : 'n/a',
            'about' => $input['about'],
            'img' => $input['img'],
            'address' => (!empty($_POST["address"])) ? $input['address'] : 'n/a',
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

            $user =Contributor::with(['user_profile'])->where('id', $contributor->id)->get();

            if( $contributor ){
                return response()->json(['success' => true, 'contributor' => $user], $this->successStatus);
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
        $contributor = Contributor::with(['user_profile'])->where('profile_id', $id)->get();
        if ( !$contributor )
            return response()->json(['success' => false, 'message' => 'Contributor not found'], $this->invalidStatus);
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
        $profile = Contributor::where('id', $id)->first();
        $contributorProfile = UserProfile::find($profile->profile_id);
        request()->validate([
            //'email' => 'unique:user_profiles,email,'.$id,
            //'phone' => 'unique:user_profiles,phone,'.$id,
        ]);
        $contributor = $contributorProfile->update($request->all());
        $input = request()->all();
        $input['name']=$input['first_name'].' '.$input['last_name'];
        $userUpdate = User::find($contributorProfile->user_id);
        $userUpdate->update($input);
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
