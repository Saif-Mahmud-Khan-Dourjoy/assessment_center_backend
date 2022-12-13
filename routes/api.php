<?php

use App\Http\Controllers\API\Question\QuestionController;
use Illuminate\Http\Request;
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

Route::group(['prefix' => 'v1'], function () {

    Route::post('register', 'API\Auth\RegisterController@register')->name('register');
    Route::post('login', 'API\Auth\LoginController@login');
    Route::post('password/forgot-password', 'API\Auth\ForgotPasswordController@forgotPassword');
    Route::post('password/reset', 'API\Auth\ForgotPasswordController@passwordReset');
    Route::post('valid-assessment', 'API\Question\AssessmentController@checkValidAssessment');
    Route::get('decrypt-token/{token}', 'API\User\StudentController@decryptToken');
    // Route::get('attend-question-set/{id}', 'API\Question\QuestionSetController@attendQuestionSet')->name('attend-question-set');
    Route::get('attend-question-set', 'API\Question\QuestionSetController@attendQuestionSet')->name('attend-question-set');
    Route::resource('question-set-answer', 'API\Question\QuestionSetAnswerController');
    Route::get('validate-token', function () {
        return response()->json(['success' => true, 'message' => 'Token is valid'], 200);
    })->middleware('auth:api');


    Route::post('check-email', 'API\Auth\RegisterController@checkEmail')->name('check-email');
    Route::post('check-username', 'API\Auth\RegisterController@checkUsername')->name('check-username');

    Route::middleware(['auth:api'])->group(function () {
        Route::get('email/verify/{id}/{hash}', 'API\Auth\VerificationApiController@verify')->name('verification.verify');
        Route::get('email/resend', 'API\Auth\VerificationApiController@resend')->name('verification.resend');
        Route::post('question-catalogs-store', 'API\Question\QuestionCatalogController@store');
        Route::get('question-catalogs', 'API\Question\QuestionCatalogController@index');
        Route::delete('delete-catalog/{id}', 'API\Question\QuestionCatalogController@destroy');
        Route::get('single-catalog/{id}', 'API\Question\QuestionCatalogController@show');
        Route::put('update-question-catalog/{id}', 'API\Question\QuestionCatalogController@update');
        Route::post('question-filter', 'API\Filter\QuestionFilterController@filterUsingTag');
        Route::post('question-catalog-filter', 'API\Filter\QuestionCatalogFilterController@filterUsingTag');
        Route::post('question-by-catalog', 'API\Filter\QuestionGetByCatalog@QuestionByCatalog');
        Route::get('question-by-catalog', 'API\Filter\QuestionGetByCatalog@QuestionByCatalog');
        Route::get('recruiters', 'API\User\UserController@getRecruiter');
        Route::get('assessment-filter', 'API\Filter\AssessmentFilterController@assessmentFilter');
        // Route::get('valid-assessment', 'API\Question\AssessmentController@checkValidAssessment');
    });

    Route::middleware(['auth:api', 'api_email_verified'])->group(function () {

        Route::get('dashboard/{id}', 'API\DashboardController@index')->name('dashboard');

        Route::post('notice', 'API\Setup\InstituteNoticeController@store')->name('notice');
        Route::get('notice', 'API\Setup\InstituteNoticeController@index')->name('all-notice');
        Route::get('get-notice/{id}', 'API\Setup\InstituteNoticeController@getNotice')->name('get-notice');
        Route::get('institute-notice/{id}', 'API\Setup\InstituteNoticeController@instituteNotice')->name('institute-notice');
        Route::post('update-notice', 'API\Setup\InstituteNoticeController@update')->name('update-notice');
        Route::delete('delete-notice/{id}', 'API\Setup\InstituteNoticeController@delete')->name('delete-notice');
        Route::get('status-notice/{id}', 'API\Setup\InstituteNoticeController@status')->name('status-notice');

        Route::resource('permission-list', 'API\Setup\PermissionListController');
        Route::resource('roles', 'API\Setup\RoleController');
        Route::resource('role-setup', 'API\Setup\RoleSetupController');
        Route::resource('users', 'API\User\UserController');
        Route::post('get-role-wise-user', 'API\User\UserController@getRoleWiseUsersList')->name('get-role-wise-user');

        Route::get('get-profile/{id}', 'API\User\UserController@getUser')->name('get-profile');
        Route::get('get-profile-pid/{pid}', 'API\User\UserController@getProfileByPID')->name('get-profile-pid');
        Route::post('update-profile', 'API\User\UserController@updateProfile')->name('update-profile');
        Route::get('update-status/{id}', 'API\User\UserController@updateStatus')->name('update-status');

        Route::get('get-permission', 'API\User\UserController@getPermissionList')->name('get-permission');

        Route::post('add-academic-history', 'API\User\UserController@addAcademicHistory')->name('add-academic-history');
        Route::post('add-employment-history', 'API\User\UserController@addEmploymentHistory')->name('add-employment-history');
        Route::get('get-academic-history/{id}', 'API\User\UserController@getAcademicHistory')->name('get-academic-history');
        Route::get('get-employment-history/{id}', 'API\User\UserController@getEmploymentHistory')->name('get-employment-history');

        Route::post('change-password', 'API\Auth\ChangePasswordController@updateAPIUserPassword')->name('change.password');

        Route::resource('institutes', 'API\Setup\InstituteController');

        Route::resource('contributors', 'API\User\ContributorController');
        Route::get('get-contributor/{id}', 'API\User\ContributorController@getContributor')->name('get-contributor');

        Route::resource('students', 'API\User\StudentController');
        Route::get('get-student-uid/{uid}', 'API\User\StudentController@studentByUid');
        Route::get('get-student/{id}', 'API\User\StudentController@getStudent')->name('get-student');
        Route::get('get-student-all-assessment/{id}', 'API\User\StudentController@getAllAssessment')->name('get-student-all-assessment');
        Route::get('get-student-all-assessment-uid/{id}', 'API\User\StudentController@getAllAssessmentByUid')->name('get-student-all-assessment-uid');
        Route::get('get-assessment-all-student/{id}', 'API\Question\QuestionSetAnswerController@getAllStudent')->name('get-assessment-all-student');

        Route::resource('question-categories', 'API\Question\QuestionCategoryController');
        Route::resource('questions', 'API\Question\QuestionController');
        Route::resource('question-sets', 'API\Question\QuestionSetController');

        // Route::get('attend-question-set/{id}', 'API\Question\QuestionSetController@attendQuestionSet')->name('attend-question-set');
        Route::get('question-set-status/{id}', 'API\Question\QuestionSetController@status');
        // Route::resource('question-set-answer', 'API\Question\QuestionSetAnswerController');
        Route::post('student-assessment', 'API\Question\QuestionSetAnswerController@eachStudentAssessment')->name('student-assessment');
        Route::post('student-have-assessments', 'API\Question\QuestionSetController@studentHaveAssessments')->name('student-have-assessments');

        Route::post('get-certificate', 'API\Question\QuestionSetAnswerController@getCertificate')->name('get-certificate');
        Route::post('rank-certificate', 'API\Question\QuestionSetAnswerController@rankCertificate')->name('rank-certificate');

        Route::resource('rounds', 'API\Round\RoundController');
        Route::get('institute-rounds/{id}', 'API\Round\RoundController@getInstituteRound');
        Route::get('round-status/{id}', 'API\Round\RoundController@status');
        Route::get('available-rounds', 'API\Round\RoundController@availableRounds');
        Route::get('valid-rounds', 'API\Round\RoundController@validRounds');

        Route::resource('round-candidates', 'API\Round\RoundCandidatesController');
        Route::get('each-round-candidates/{id}', 'API\Round\RoundCandidatesController@eachRoundCandidates');
        Route::get('fresher-candidates', 'API\Round\RoundCandidatesController@fresherCandidates');

        Route::resource('broadcast', 'API\Broadcast\BroadcastController');
        Route::post('broadcast-result', 'API\Broadcast\BroadcastController@broadcastResult');
        Route::post('broadcast-certificate', 'API\Broadcast\BroadcastController@broadcastCertificate');

        Route::post('bulk-entry-students', 'API\User\StudentController@bulkEntry')->name('bulk-entry-students');
        Route::get('get-questions', 'API\Question\QuestionController@get_question')->name('get_question');
    });
    Route::fallback(function () {
        return response()->json(['message' => 'Not Found.'], 404);
    })->name('api.fallback.404');
});

Route::post('image-upload', [QuestionController::class, 'imageUploadPost'])->name('image.upload.post');
