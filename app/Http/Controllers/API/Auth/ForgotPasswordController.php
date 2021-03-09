<?php

namespace App\Http\Controllers\API\Auth;

use App\Http\Controllers\Controller;
use App\User;
use http\Env\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;
use App\Notifications\ResetPassword;
use mysql_xdevapi\Exception;

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
        try{
            Log::channel("ac_info")->info(__CLASS__."@".__FUNCTION__."# Password is resetting.");
            $validator = Validator::make($request->all(), [
                'token' => 'required',
                'username' => 'required',
                'email'=> 'required|email',
                'password' => 'required|confirmed|min:8',
            ]);
            if ($validator->fails()) {
                return response()->json($validator->errors());
            }
            $input = $request->all();
            $response = Password::reset($input, function ($user, $password) {
                $user->password = Hash::make($password);
                $user->save();
            });
            if($response!=Password::PASSWORD_RESET)
                throw new \Exception("Response isn't matching with reset status! Response: ".$response);
            Log::channel("ac_info")->info(__CLASS__."@".__FUNCTION__."# Password reset successful.");
            return response()->json(['success' => true, "message" => "Password Reset Successful."], $this->successStatus);
        }catch(\Exception $e){
            Log::channel("ac_error")->info(__CLASS__."@".__FUNCTION__."# Exception occurred! Error: ".$e->getMessage());
            return response()->json(["success"=>false, "message"=>"Password Reset is unsuccessful!", "error"=>$e->getMessage()], $this->failedStatus);
        }
    }
}
