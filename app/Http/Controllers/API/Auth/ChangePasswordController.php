<?php

namespace App\Http\Controllers\API\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Rules\CheckOldPassword;
use Illuminate\Support\Facades\Hash;
use App\User;

class ChangePasswordController extends Controller
{
    /**
     * Update user password.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function updateAPIUserPassword(Request $request)
    {
        $successStatus = 200;
        request()->validate([
            'current_password' => ['required', new CheckOldPassword],
            'new_password' => ['required'],
            'new_confirm_password' => ['same:new_password'],
        ]);

        User::find(auth()->user()->id)->update(['password'=> Hash::make($request->new_password)]);

        return response()->json(['success' => true, 'message' => 'Password Updated'], $successStatus);
    }
}
