<?php

namespace App\Http\Middleware;


use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\User;


class APIRoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Request  $request
     * @param  Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $roles = Auth::user()->getRoleNames();
        for ($i = 0; $i < count($roles); $i++)
            if(Auth::user()->hasRole($roles[$i])){
                return $next($request);
            }
        return response()->json(['error'=>'Unauthorised'], 401);

    }
}
