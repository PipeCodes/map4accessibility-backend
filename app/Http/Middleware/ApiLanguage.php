<?php

namespace App\Http\Middleware;

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
    public function handle($request, Closure $next)
    {
        $this->language = $request->header('Accept-Language') ?? 'en';

        app()->setLocale($this->language);

        return $next($request);
    }
}
