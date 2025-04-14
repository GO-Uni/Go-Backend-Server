<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ApiResponseService;
use App\Models\User;

class AdminController extends Controller
{
    /**
     * Ban a user.
     */
    public function banUser(Request $request)
    {
        $request->validate([
            'user_id' => 'required|integer|exists:users,id',
        ]);

        $user = User::find($request->user_id);

        if ($user->status === 'banned') {
            return ApiResponseService::error('User is already banned.', null, 400);
        }

        $user->status = 'banned';
        $user->save();

        return ApiResponseService::success('User banned successfully.');
    }

    /**
     * Unban a user.
     */
    public function unbanUser(Request $request)
    {
        $request->validate([
            'user_id' => 'required|integer|exists:users,id',
        ]);

        $user = User::find($request->user_id);

        if ($user->status !== 'banned') {
            return ApiResponseService::error('User is not banned.', null, 400);
        }

        $user->status = 'active';
        $user->save();

        return ApiResponseService::success('User unbanned successfully.');
    }
}
