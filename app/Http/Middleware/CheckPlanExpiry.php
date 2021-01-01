<?php

namespace App\Http\Middleware;

use Closure;
use Carbon\Carbon;

class CheckPlanExpiry
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
        if(auth()->user()->planExpired()){
         return redirect("/plan-expired");   
        }
        return $next($request);
    }
}
