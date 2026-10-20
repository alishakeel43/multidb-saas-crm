<?php

namespace Webkul\AiSales\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateAiSales
{
    public function handle(Request $request, Closure $next): Response
    {
        $expectedHash = (string) config('ai-sales.token_hash', '');
        $token = (string) $request->bearerToken();

        if ($expectedHash === '' || $token === '' || ! hash_equals($expectedHash, hash('sha256', $token))) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        return $next($request);
    }
}
