<?php

namespace App\Http\Middleware;


use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\User;


class APIPermissionMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @param $permission
     * @return mixed
     */
    public function handle($request, Closure $next, $permission)
    {
        //dd(app('auth')->user()->getAllPermissions());
        /*$param = $request->route()->parameters();
        $loggedin_user = Auth::user();
        if(!$loggedin_user->hasRole('Admin') && $loggedin_user->id != $param['post']['user_id']){
            return response()->json(['error'=>'Unauthorised'], 401);
        }*/

        $permissions = is_array($permission)
            ? $permission
            : explode('|', $permission);

        foreach ($permissions as $permission) {
            if (app('auth')->user()->can($permission)) {
                return $next($request);
            }
        }
        return response()->json(['error'=>'Unauthorised'], 401);
    }
}
