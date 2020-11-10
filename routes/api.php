<?php

use Illuminate\Support\Facades\Route;

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
        Route::resource('permission-list', 'API\Setup\PermissionListController');
        Route::resource('roles', 'API\Setup\RoleController');
        Route::resource('role-setup', 'API\Setup\RoleSetupController');
        Route::resource('users', 'API\User\UserController');

        Route::get('get-profile/{id}', 'API\User\UserController@getUser')->name('get-profile');
        Route::post('update-profile', 'API\User\UserController@updateProfile')->name('update-profile');

        Route::get('get-permission', 'API\User\UserController@getPermissionList')->name('get-permission');

        Route::post('add-academic-history', 'API\User\UserController@addAcademicHistory')->name('add-academic-history');
        Route::post('add-employment-history', 'API\User\UserController@addEmploymentHistory')->name('add-employment-history');
        Route::get('get-academic-history/{id}', 'API\User\UserController@getAcademicHistory')->name('get-academic-history');
        Route::get('get-employment-history/{id}', 'API\User\UserController@getEmploymentHistory')->name('get-employment-history');

        Route::post('change-password', 'API\Auth\ChangePasswordController@updateAPIUserPassword')->name('change.password');

        Route::resource('institutes','API\Setup\InstituteController');

        Route::resource('contributors','API\User\ContributorController');
        Route::get('get-contributor/{id}', 'API\User\ContributorController@getContributor')->name('get-contributor');

        Route::resource('students','API\User\StudentController');
        Route::get('get-student/{id}', 'API\User\StudentController@getStudent')->name('get-student');
        Route::get('get-student-all-assessment/{id}', 'API\User\StudentController@getAllAssessment')->name('get-student-all-assessment');

        Route::resource('question-categories','API\Question\QuestionCategoryController');
        Route::resource('questions','API\Question\QuestionController');
        Route::resource('question-sets','API\Question\QuestionSetController');
        Route::resource('question-set-answer','API\Question\QuestionSetAnswerController');

    });
    Route::fallback(function(){
        return response()->json(['message' => 'Not Found.'], 404);
    })->name('api.fallback.404');
});
