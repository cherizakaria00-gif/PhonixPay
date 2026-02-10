<?php

namespace App\Http\Middleware;

use Closure;
use App\Constants\Status;
use Illuminate\Http\Request;

class UserRestricted
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {   
        $user = auth()->user();
      
        if($user->kv != Status::KYC_VERIFIED){    
            return response()->view(activeTemplate().'user.restricted', ['pageTitle'=>'Restricted Page']);
        }

        return $next($request);
    }
}
