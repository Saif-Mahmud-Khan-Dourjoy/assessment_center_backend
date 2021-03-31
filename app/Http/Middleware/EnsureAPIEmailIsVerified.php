<?php

namespace App\Http\Middleware;

use App\User;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class EnsureAPIEmailIsVerified
{
    public $successStatus = 200;
    public $failedStatus = 500;
    public $invalidStatus = 400;
    public $unAuthenticated = 401;

    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @param string|null $redirectToRoute
     * @return JsonResponse|mixed
     */
    public function handle(Request $request, Closure $next, $redirectToRoute = null): JsonResponse
    {
        $user = Auth::user();
        if($user){
            if($user->email_verified_at){
                return $next($request);
            }
            Log::channel("ac_info")->info(__CLASS__."@".__FUNCTION__."# User email address not verified.");
            return response()->json(['success'=>false, 'message'=> 'Your email address is not verified!'],$this->successStatus);
        }
        return response()->json(['success'=>false, 'message'=> 'Username or Password may incorrect!'], $this->successStatus);
    }
}
