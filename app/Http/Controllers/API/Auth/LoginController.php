<?php

namespace App\Http\Controllers\API\Auth;


use App\UserAccessToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;


class LoginController extends Controller
{
    public $successStatus = 200;
    public $failedStatus = 500;
    public $invalidStatus = 400;
    public $unAuthenticated = 401;
    public $out;
    function __construct()
    {
        $this->out = new \Symfony\Component\Console\Output\ConsoleOutput();
    }

    /**
     * Login api
     *
     * @return JsonResponse
     */
    public function login()
    {
        try {
            Log::channel("ac_info")->info(__CLASS__ . "@" . __FUNCTION__ . "# User Logging is processing.");
            if (!Auth::attempt(['username' => request('username'), 'password' => request('password')])) {
                Log::channel("ac_info")->info(__CLASS__ . "@" . __FUNCTION__ . "# User Login is unsuccessful!");
                return response()->json(['success' => false, "message" => "Username or Password may incorrect!"], $this->unAuthenticated);
            }
            $user = Auth::user();
            Log::channel("ac_info")->info(__CLASS__ . "@" . __FUNCTION__ . "# User Logging credential Authenticated: " . $user->username);
            if (UserAccessToken::where('user_id', $user->id)->exists()) {
                Log::channel("ac_info")->info(__CLASS__ . "@" . __FUNCTION__ . "#Logging out from other device is successful: " . $user->username);
                $deletedTokens = UserAccessToken::where('user_id', $user->id)->delete();
            }
            $token =  $user->createToken('NSLAssessmentCenter')->accessToken;
            $user->roles;
            Log::channel("ac_info")->info(__CLASS__ . "@" . __FUNCTION__ . "# User Login successful: " . $user->username);
            return response()->json(['success' => true, 'user' => $user, 'token' => $token], $this->successStatus);
        } catch (\Exception $e) {
            Log::channel("ac_error")->info(__CLASS__ . "@" . __FUNCTION__ . "# Login Unsuccessful! Error: " . $e->getMessage());
            return response()->json(['success' => false, "message" => "Login Unsuccessful!", "error" => $e->getMessage()], $this->failedStatus);
        }
    }
}
