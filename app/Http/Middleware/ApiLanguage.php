<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ApiLanguage
{
    /**
     * Handle an incoming request.
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $this->language = $request->headers->get('Accept-Language') ?? 'en';

        app()->setLocale($this->language);

        return $next($request);
    }
}
