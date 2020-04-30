<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Response as HttpResponse;

class AdminMiddleware
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
        if (!$request->user()->hasRole('ADMD')) {
            return new HttpResponse(view('unauthorized')->with('role', 'DMA Admin'));
        }
        return $next($request);
    }
}
