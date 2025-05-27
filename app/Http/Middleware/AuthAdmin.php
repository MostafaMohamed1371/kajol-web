<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;


class AuthAdmin
{

    public function handle(Request $request, Closure $next): Response
    {
        if(Auth::check())
        {
            if(Auth::user()->utype === 'ADM')
            {
                return $next($request);
            }
            else
            {
                session()->flush();
                return redirect()->route('login');
            }
        }
        else
        {
            session()->flush();
            return redirect()->route('login');
        }      
        
        
    }
}
