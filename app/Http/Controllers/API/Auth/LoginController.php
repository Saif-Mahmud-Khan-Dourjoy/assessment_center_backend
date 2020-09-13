<?php

namespace App\Http\Controllers\API\Auth;


use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\User;
use Illuminate\Support\Facades\Auth;


class LoginController extends Controller
{
    public $successStatus = 200;
    /**
     * Login api
     *
     * @return JsonResponse
     */
    public function login(){
        if(Auth::attempt(['email' => request('email'), 'password' => request('password')])){
            $user = Auth::user();
            $token =  $user->createToken('NSLAssessmentCenter')-> accessToken;
            return response()->json(['success' => true, 'user' => $user['name'], 'token' => $token], $this-> successStatus);
        }
        else{
            return response()->json(['error'=>'Unauthenticated'], 401);
        }
    }
}
