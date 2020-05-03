<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Response;

class ForeignBrokerAdminMiddleware
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
               // return '113';
               if (!$request->user()->hasRole('BRKF')) {
                return new Response (view('unauthorized')->with('role', 'Outbound Foreign Broker'));
            }
            return $next($request);
    }
}
