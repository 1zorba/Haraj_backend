<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ChatController extends Controller
{
    /**
     * النسخة النهائية والمصححة لحفظ الرسائل.
     */
    public function sendMessage(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'receiver_id' => 'required|integer|exists:users,id',
            'content' => 'required|string|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $user = $request->user();

            if (!$user) {
                return response()->json(['message' => 'Unauthenticated.'], 401);
            }

            $message = Message::create([
                'sender_id' => $user->id,
                'receiver_id' => $request->receiver_id,
                'content' => $request->content,
            ]);

            return response()->json($message, 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred while saving the message.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * النسخة النهائية لجلب الرسائل.
     */
    public function getMessages($userId)
    {
        $currentUser = Auth::user();
        $messages = Message::where(function ($query) use ($currentUser, $userId) {
            $query->where('sender_id', $currentUser->id)->where('receiver_id', $userId);
        })->orWhere(function ($query) use ($currentUser, $userId) {
            $query->where('sender_id', $userId)->where('receiver_id', $currentUser->id);
        })->orderBy('created_at', 'asc')->get();

        Message::where('sender_id', $userId)->where('receiver_id', $currentUser->id)->whereNull('read_at')->update(['read_at' => now()]);
        return response()->json($messages);
    }

    /**
     * النسخة النهائية لجلب المحادثات.
     */
    public function getConversations()
    {
        $userId = Auth::id();
        if (!$userId) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $partnerIds = Message::where('sender_id', $userId)
            ->orWhere('receiver_id', $userId)
            ->pluck('sender_id')
            ->merge(Message::where('sender_id', $userId)->orWhere('receiver_id', $userId)->pluck('receiver_id'))
            ->unique()
            ->reject(fn ($id) => $id == $userId)
            ->values();

        if ($partnerIds->isEmpty()) {
            return response()->json([]);
        }

        $latestMessages = Message::whereIn('id', function ($query) use ($userId) {
            $query->select(DB::raw('MAX(id)'))
                ->from('messages')
                ->where(fn ($q) => $q->where('sender_id', $userId)->orWhere('receiver_id', $userId))
                ->groupBy(DB::raw('CASE WHEN sender_id = '.$userId.' THEN receiver_id ELSE sender_id END'));
        })->orderBy('created_at', 'desc')->get();

        $partners = User::whereIn('id', $partnerIds)->get()->keyBy('id');

        $conversations = $latestMessages->map(function ($message) use ($userId, $partners) {
            $partnerId = $message->sender_id == $userId ? $message->receiver_id : $message->sender_id;
            if (!isset($partners[$partnerId])) return null;
            $partner = $partners[$partnerId];
            $unreadCount = Message::where('sender_id', $partnerId)
                                  ->where('receiver_id', $userId)
                                  ->whereNull('read_at')
                                  ->count();
            return [
                'id' => $partner->id,
                'name' => $partner->name,
                'profile_image' => $partner->image,
                'last_message' => $message->content,
                'last_message_time' => $message->created_at->toIso8601String(),
                'unread_count' => $unreadCount,
            ];
        })->filter()->values();

        return response()->json($conversations);
    }
}
