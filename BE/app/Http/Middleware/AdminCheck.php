<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminCheck
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();

        // Check if the user is authenticated and has an admin role
        if (!$user || $user->role_name !== 'Admin') {
            return response()->json([
                'status' => 'error',
                'message' => 'Access denied. Admins only.',
            ], 403);
        }

        return $next($request);
    }
}
