<?php

namespace App\Http\Middleware;

use App\Http\Traits\ApiResponseTrait;
use Closure;
use Illuminate\Http\Request;

class AppUserEmailConfirmed
{
    use ApiResponseTrait;

    public function handle(Request $request, Closure $next)
    {
        /** @var \App\Model\AppUser $user */
        $user = auth()->user();

        if (!$user->isEmailConfirmed()) {
            return $this->respondUnAuthorized(__('api.email_not_confirmed'));
        }

        return $next($request);
    }
}
