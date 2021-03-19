<?php

namespace App\Http\Controllers\API\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class VerificationApiController extends Controller
{
    public $successStatus = 200;
    public $failedStatus = 500;
    public $invalidStatus = 403;
    public $unAuthenticated = 401;

    public function __construct() {
        $this->middleware('auth:api')->except(['verify']);
    }

    /**
     * Mark the authenticated user’s email address as verified.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function verify(Request $request): JsonResponse
    {
        $expires = $request->query('expires');
        if (($expires && Carbon::now()->getTimestamp() > $expires)) {
            return response()->json(['success'=>false, 'message'=> "Expired url provided."], $this->unAuthenticated);
        }

        if (! hash_equals((string) $request->route('id'), (string) $request->user()->getKey())) {
            return response()->json(['success'=>false, 'message'=> "Invalid/Expired url provided."], $this->invalidStatus);
        }

        if (! hash_equals((string) $request->route('hash'), sha1($request->user()->getEmailForVerification()))) {
            return response()->json(['success'=>false, 'message'=> "Invalid/Expired url provided."], $this->invalidStatus);
        }

        if ($request->user()->hasVerifiedEmail()) {
            return response()->json(['success'=>true, 'message'=> "Email already verified."], $this->successStatus);
        }

        if ($request->user()->markEmailAsVerified()) {
            event(new Verified($request->user()));
            return response()->json(['success'=>true, 'message'=> "Email verified."], $this->successStatus);
        }

        return response()->json(['success'=>true, 'message'=> 'Email Verified!'], $this->successStatus);
    }

    /**
     * Check the authenticated user’s email address verified or not.
     *
     * @return JsonResponse
     */
    public function resend(): JsonResponse
    {
        if (auth()->user()->hasVerifiedEmail()) {
            return response()->json(["msg" => "Email already verified."], $this->invalidStatus);
        }

        auth()->user()->sendEmailVerificationNotification();

        return response()->json(["msg" => "Email verification link sent on your email id"]);
    }
}
