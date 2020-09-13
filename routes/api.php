<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::group(['prefix' => 'v1'], function() {
    Route::post('register', 'API\Auth\RegisterController@register')->name('register');
    Route::post('login', 'API\Auth\LoginController@login');
    Route::post('forgot-password', 'API\Auth\ForgotPasswordController@sendResetLink')->name('forgot.password');

    Route::middleware('auth:api')->group( function () {
        Route::resource('permission-list', 'API\PermissionListController');
        Route::resource('users', 'API\UserController');
        Route::post('change-password', 'API\Auth\ChangePasswordController@updateAPIUserPassword')->name('change.password');
        Route::post('role-permissions/{role}', 'API\RoleAccessController@role_has_permissions');
        Route::post('assign-user-role/{role}', 'API\RoleAccessController@assign_user_to_role');
    });
    Route::fallback(function(){
        return response()->json(['message' => 'Not Found.'], 404);
    })->name('api.fallback.404');
});
