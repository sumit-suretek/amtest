<?php

namespace App\Http\Middleware;

use App\Exceptions\WrongTokenException;
use App\Exceptions\MissingApiTokenException;
use Closure;

class VerifyApiToken
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
        if (empty($request->header('API-TOKEN'))) {
            throw new MissingApiTokenException();
        } elseif (env('API_TOKEN') != $request->header('API-TOKEN')) {
            throw new WrongTokenException();
        }
        return $next($request);
    }
}
