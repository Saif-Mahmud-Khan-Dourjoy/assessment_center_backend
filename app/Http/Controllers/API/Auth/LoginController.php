<?php

namespace App\Http\Controllers\API\Auth;


use App\UserAccessToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\User;
use Illuminate\Support\Facades\Auth;


class LoginController extends Controller
{
    public $successStatus = 200;
    public $out;
    function __construct(){
        $this->out = new \Symfony\Component\Console\Output\ConsoleOutput();
    }

    /**
     * Login api
     *
     * @return JsonResponse
     */
    public function login(){
        $this->out->writeln('user login processing...');
        if(Auth::attempt(['username' => request('username'), 'password' => request('password')])){
            $user = Auth::user();
            $this->out->writeln('Looged in user: '.$user);
            if (UserAccessToken::where('user_id', $user->id)->exists()) {
                $this->out->writeln('User is already logges in other devices: '.$user->id);
                $deletedTokens = UserAccessToken::where('user_id', $user->id)->delete();
            }
            $token =  $user->createToken('NSLAssessmentCenter')-> accessToken;
            $user->roles;
//            $this->out->writeln('Access token: '.$user->token());
            return response()->json(['success' => true, 'user' => $user, 'token' => $token], $this-> successStatus);
        }
        else{
            return response()->json(['error'=>'Unauthenticated'], 401);
        }
    }
}
