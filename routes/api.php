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
    Route::post('password/forgot-password', 'API\Auth\ForgotPasswordController@forgotPassword');
    Route::post('password/reset', 'API\Auth\ForgotPasswordController@passwordReset'); 

    Route::post('check-email', 'API\Auth\RegisterController@checkEmail')->name('check-email');

    Route::middleware('auth:api')->group( function () {

        Route::get('dashboard/{id}', 'API\DashboardController@index')->name('dashboard');

        Route::post('notice','API\Setup\InstituteNoticeController@store')->name('notice');
        Route::get('notice', 'API\Setup\InstituteNoticeController@index')->name('all-notice');
        Route::get('get-notice/{id}', 'API\Setup\InstituteNoticeController@getNotice')->name('get-notice');
        Route::get('institute-notice/{id}','API\Setup\InstituteNoticeController@instituteNotice')->name('institute-notice');
        Route::post('update-notice','API\Setup\InstituteNoticeController@update')->name('update-notice');
        Route::delete('delete-notice/{id}','API\Setup\InstituteNoticeController@delete')->name('delete-notice');
        Route::get('status-notice/{id}','API\Setup\InstituteNoticeController@status')->name('status-notice');

        Route::resource('permission-list', 'API\Setup\PermissionListController');
        Route::resource('roles', 'API\Setup\RoleController');
        Route::resource('role-setup', 'API\Setup\RoleSetupController');
        Route::resource('users', 'API\User\UserController');
        Route::post('get-role-wise-user', 'API\User\UserController@getRoleWiseUsersList')->name('get-role-wise-user');

        Route::get('get-profile/{id}', 'API\User\UserController@getUser')->name('get-profile');
        Route::post('update-profile', 'API\User\UserController@updateProfile')->name('update-profile');
        Route::get('update-status/{id}', 'API\User\UserController@updateStatus')->name('update-status');

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
        Route::get('get-assessment-all-student/{id}', 'API\Question\QuestionSetAnswerController@getAllStudent')->name('get-assessment-all-student');

        Route::resource('question-categories','API\Question\QuestionCategoryController');
        Route::resource('questions','API\Question\QuestionController');
        Route::resource('question-sets','API\Question\QuestionSetController');
        Route::resource('question-set-answer','API\Question\QuestionSetAnswerController');

    });
    Route::fallback(function(){
        return response()->json(['message' => 'Not Found.'], 404);
    })->name('api.fallback.404');
});
