<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Mail\EmailConfirmation;
use App\Models\AppUser;
use Illuminate\Support\Facades\Mail;

class VerifyEmailAppUserController extends Controller
{
    /**
     * Instantiate a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth:sanctum')->only('changeStatus');
        $this->middleware('ability:email-confirmation')->only('changeStatus');
    }

    public function changeStatus()
    {
        /** @var AppUser $user */
        $user = auth('sanctum')->user();
        $user->markEmailAsActive();

        return redirect(env('APP_FRONTEND_URL').'/email-confirmation');
    }

    public function emailConfirmationResend($email)
    {
        try {
            $user = AppUser::where('email', $email)->first();

            if (!$user) {
                return $this->respondNotFound();
            }

            Mail::to($user->email)->send(new EmailConfirmation($user));

            return redirect(env('APP_FRONTEND_URL').'/confirmation-resent');
        } catch (\Throwable $th) {
            return $this->respondInternalError($th->getMessage());
        }
    }

}
