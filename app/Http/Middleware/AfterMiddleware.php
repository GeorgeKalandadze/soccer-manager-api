<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AfterMiddleware
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (auth()->check() && $request->route()?->getName() !== 'logout' && ! $response->headers->has('Authorization')) {
            $response->headers->set('Authorization', 'Bearer '.auth()->user()->refreshToken());
        }

        return $response;
    }
}
