<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthUserSetLocale
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (\Auth::check()) {
            \App::setLocale(\Auth::user()->language ?? 'en');
        }
        elseif ($request->cookie('user_lang')) {
            \App::setLocale($request->cookie('user_lang'));
        }
/*        elseif (\Session::exists('language')) {
            \App::setLocale(\Session::get('language'));
        }*/
        return $next($request);
    }
}
