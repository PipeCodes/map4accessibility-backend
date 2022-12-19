<?php

namespace App\Http\Middleware;

use App\Http\Traits\ApiResponseTrait;
use Closure;
use Illuminate\Http\Request;

class CheckApiKey
{
    use ApiResponseTrait;

    public function handle(Request $request, Closure $next)
    {
        $requestKey = $request->headers->get('x-api-key');
        $apiKey = env('API_KEY');

        if (empty($requestKey) || empty($apiKey) || $requestKey !== $apiKey) {
            return $this->respondForbidden(__('api.api_key_mismatch'));
        }

        return $next($request);
    }
}
