<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Response;

class LocalBrokerOperatorMiddleware
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
        if (! $request->user()->hasRole('OPRB')) {
            return new Response(view('unauthorized')->with('role', 'Local Broker Operator'));
        }
        return $next($request);
    }
}
