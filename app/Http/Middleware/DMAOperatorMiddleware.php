<?php


namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Response;

class DMDOperatorMiddleware
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
        if ($request->user() && $request->user()->type != 'dma_operator') {
            return new Response (view('unauthorized')->with('role', 'DMA Operator'));
        }
        return $next($request);
    }
}
 