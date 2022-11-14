<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('instruction-page/{assessment_id}/{institute_id}/{profile_id}', 'API\Candidate\CandidateController@instuction')->name('instruction-page')->middleware('signed');
