<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Response;

class SettlementAgentMiddleware
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
        if (!$request->user()->hasRole('AGTS')) {
            return new Response(view('unauthorized')->with('role', 'Settlement Bank'));
        }
        return $next($request);
    }
}
