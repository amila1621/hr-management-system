<?php

namespace App\Http\Middleware;

use App\Models\ActivityLog;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ActivityLogMiddleware
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
        try {
            ActivityLog::create([
                'logged_in_user' => Auth::check() ? Auth::user()->email : 'guest',
                'page_endpoint_route' => $request->path(),
                'ip_address' => $request->ip()
            ]);
        } catch (\Exception $e) {
            Log::emergency('DEBUG Error: ' . $e->getMessage());
        }


        $response = $next($request);

        return $response;
    }
}
