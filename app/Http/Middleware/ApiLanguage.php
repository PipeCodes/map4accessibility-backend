<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Closure;

class ApiLanguage
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $this->language = $request->headers->get('Accept-Language') ?? 'en';

        app()->setLocale($this->language);

        return $next($request);
    }
}
