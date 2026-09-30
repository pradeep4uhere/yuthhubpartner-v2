<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EventTransactionAuth
{
    public function handle(
        Request $request,
        Closure $next
    ): Response {

        $guards = [
            'admin',
            'partner',
            'partner_center',
            'partner_placement',
            'field_agent',
        ];

        foreach ($guards as $guard) {

            if (auth()->guard($guard)->check()) {

                auth()->shouldUse($guard);

                return $next($request);
            }
        }

        return response()->json([
            'success' => false,
            'message' => 'Unauthenticated.',
        ], 401);
    }
}
