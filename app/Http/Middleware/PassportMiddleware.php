<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;



class PassportMiddleware
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
        return $next($request);
    }
    private function unauthorized($message = null){
        return response()->json([
            'message' => $message ? $message : 'Usted no esta autorizado para realizar esta accion',
            'success' => false
        ], 401);
    }
}
