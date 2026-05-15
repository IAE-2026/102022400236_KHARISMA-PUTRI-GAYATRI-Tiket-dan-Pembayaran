<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IAEkey
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $apiKey = $request->header('X-IAE-KEY');

        if ($apiKey !== '102022400236') {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized: invalid or missing X-IAE-KEY',
                'data' => null,
            ], 401);
        }
        return $next($request);
    }
}
