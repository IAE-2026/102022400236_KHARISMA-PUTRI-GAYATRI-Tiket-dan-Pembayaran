<?php

namespace App\Http\Middleware;

use Closure;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IAEkey
{
    private const ALLOWED_IAE_KEY = '102022400236';

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $apiKey = trim((string) $request->header('X-IAE-KEY', ''));

        if (! hash_equals(self::ALLOWED_IAE_KEY, $apiKey)) {
            return ApiResponse::error('Unauthorized: X-IAE-KEY must match NIM 102022400236', null, 401);
        }
        return $next($request);
    }
}
