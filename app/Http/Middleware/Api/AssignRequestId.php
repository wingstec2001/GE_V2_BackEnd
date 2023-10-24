<?php

namespace App\Http\Middleware\Api;

use Closure;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AssignRequestId
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
        $requestId = (string) Str::uuid();

        Log::withContext([
            'request-id' => $requestId
        ]);
        $request->headers->set('Request-Id', $requestId);
        $response = $next($request);
        if (method_exists($response, "header")) {
            return $response->header('Request-Id', $requestId);
        }
        return $response;
    }
}
