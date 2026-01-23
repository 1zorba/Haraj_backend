<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function storeToken(Request $request)
    {
        $request->validate(['fcm_token' => 'required|string']);
        $user = Auth::user();
        if ($user) {
            $user->fcm_token = $request->fcm_token;
            $user->save();
            return response()->json(['message' => 'Token stored successfully.']);
        }
        return response()->json(['message' => 'User not authenticated.'], 401);
    }
}
