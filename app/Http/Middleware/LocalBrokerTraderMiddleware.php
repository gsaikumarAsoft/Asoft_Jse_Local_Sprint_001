<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Response;

class LocalBrokerTraderMiddleware
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
        if (! $request->user()->hasRole('TRDB')) {
            return new Response(view('unauthorized')->with('role', 'Local Broker Trader'));
        }
        return $next($request);
    }
}
