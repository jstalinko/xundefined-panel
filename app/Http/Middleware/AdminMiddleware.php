<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth()->check()) {
            return redirect()->route('login')->withErrors(['email' => 'Please log in to access the control panel.']);
        }

        $user = auth()->user();
        if (!$user->isAdmin()) {
            return redirect('/')->withErrors(['error' => 'UNAUTHORIZED // Administrator clearance required.']);
        }

        return $next($request);
    }
}
