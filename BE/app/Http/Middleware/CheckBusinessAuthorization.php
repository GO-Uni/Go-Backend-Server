<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Services\ApiResponseService;
use Illuminate\Support\Facades\Auth;

class CheckBusinessAuthorization
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

        if (!$user) {
            return ApiResponseService::error('Unauthorized', null, 401);
        }

        if ($user->role_id !== 3) {
            return ApiResponseService::error('Access denied. Business authorization required.', null, 403);
        }

        // Check if business profile exists
        if (!$user->businessProfile) {
            return ApiResponseService::error('Business profile not found.', null, 404);
        }

        // Check if subscription is active
        if ($user->subscription && !$user->subscription->active) {
            return ApiResponseService::error('Inactive business subscription.', null, 403);
        }

        return $next($request);
    }
}
