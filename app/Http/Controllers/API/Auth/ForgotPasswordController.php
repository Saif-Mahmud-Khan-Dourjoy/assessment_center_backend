<?php

namespace App\Http\Controllers\API\Auth;

use App\Http\Controllers\Controller;
use App\User;
use http\Env\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;
use App\Notifications\ResetPassword;

class ForgotPasswordController extends Controller
{
    public $successStatus = 200;
    public $failedStatus = 500;
    public $invalidStatus = 400;


    public $out;
    function __construct()
    {
        $this->out = new \Symfony\Component\Console\Output\ConsoleOutput();                 // for printing message to console
    }

    /**
     * Forgot password
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function forgotPassword(Request $request): JsonResponse
    {
//        die('Reseting password');
        $input = $request->only('username');
        $validator = Validator::make($input, [
            'username' => "required"
        ]);
        if ($validator->fails()) {
            return response()->json($validator->errors());
        }
        $this->out->writeln('Username: '.$input['username']);
        $user_email = User::where('username',$input['username'])->first();
        $this->out->writeln('user email: '.$user_email);
        if($user_email){
            $response = Password::sendResetLink($input);
            $message = $response == Password::RESET_LINK_SENT ? 'Mail send successfully' : 'Failed';
            return response()->json(['success' => true, 'message' => $message], $this->successStatus);
        }
        return response()->json(['success'=> false, 'message'=>'Username not found'], '401');
    }


    /**
     * Forgot password
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function passwordReset(Request $request): JsonResponse
    {
        $input = $request->only('username','email','token', 'password', 'password_confirmation');
        $validator = Validator::make($input, [
            'token' => 'required',
            'username' => 'required',
            'email'=>'required|email',
            'password' => 'required|confirmed|min:8',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors());
        }
        $this->out->writeln('Password Reset for: '.$input['username'].'|'.$input['password'].'|'.$input['email']);
        $response = Password::reset($input, function ($user, $password) {
            $user->password = Hash::make($password);
            $user->save();
        });
        $message = $response == Password::PASSWORD_RESET ? 'Password reset successfully' : 'Failed';

        return response()->json(['success' => true, "message" => $message], $this->successStatus);
    }
}
