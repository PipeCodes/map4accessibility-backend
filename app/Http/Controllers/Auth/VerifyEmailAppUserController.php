<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

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
        $user = auth('sanctum')->user();
        $user->markEmailAsActive();

        return redirect(env('APP_FRONTEND_URL').'/email-confirmation');
    }
}
