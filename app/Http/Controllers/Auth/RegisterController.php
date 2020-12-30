<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\UserCredentials;
use App\Providers\RouteServiceProvider;
use App\User;
use http\Env\Response;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class RegisterController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Register Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles the registration of new users as well as their
    | validation and creation. By default this controller uses a trait to
    | provide this functionality without requiring any additional code.
    |
    */

    use RegistersUsers;

    /**
     * Where to redirect users after registration.
     *
     * @var string
     */
    protected $redirectTo = RouteServiceProvider::HOME;

    public  $out;           // for writing message into console

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest');
        $this->out = new \Symfony\Component\Console\Output\ConsoleOutput();
    }

    /**
     * Get a validator for an incoming registration request.
     *
     * @param  array  $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data)
    {
        return Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);
    }

    /**
     * Sen User his credential to his email
     * @param $username, $user_password, $user_email
     * @return True/False
     */

    public function emailCredential($username, $user_password, $user_email){
        $this->out->writeln('Emailing user credentials');
        try{
             $email = env('TO_EMAIL');
            $this->out->writeln('Email: '.$user_email);
            Mail::to($user_email)
                ->send(new UserCredentials($username, $user_password, $user_email));
            return true;
        }catch(Throwable $e){
            $this->out->writeln('Unable to email user credentials, for '.$e);
            return false;
        }
    }


    /**
     * Create a new user instance after a valid registration.
     *
     * @param  array  $data
     * @return User
     */
    protected function create(array $data)
    {
        $rand_pass = Str::random(8);
        $hashed_random_password = Hash::make($rand_pass);
        $reg_info = [
            'name'=> $data['first_name']." ".$data['last_name'],
            'username'=>$data['username'],
            'email'=> $data['email'],
            'password'=>$hashed_random_password,
        ];
        $user = User::create($reg_info);
        if($user) {
            // send email for confirmation
            $this->emailCredential($user->username, $rand_pass, $user->email);
            return response()->json(['success'=>true, 'message'=>'User successfully Registered', 'user'=>$user], $this->successStatus);
        }
        return response()->json(['success'=>false, 'message'=> 'User registration unsuccessful'], $this->failedStatus);
    }
}
