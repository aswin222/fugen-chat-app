<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class NotificationController extends Controller
{
    public function index()
    {
        $userId = Auth::id();

        $notifications = DB::table('notifications AS n')
            ->join('users AS u', 'n.sender_id', '=', 'u.id')
            ->where('n.user_id', $userId)
            ->where('n.is_read', false)
            ->select(
                'n.id',
                'n.sender_id',
                'u.name AS sender_name',
                'n.message_id',
                'n.created_at'
            )
            ->orderBy('n.created_at', 'desc')
            ->get();

        return response()->json($notifications);
    }


    public function markAsRead(Request $request)
    {
        $validatedData = $request->validate([
            'sender_id' => 'required|exists:users,id'
        ]);

        $userId = Auth::id();
        $senderId = $validatedData['sender_id'];

        $updatedCount = DB::table('notifications')
            ->where('user_id', $userId)
            ->where('sender_id', $senderId)
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'updated_at' => now()
            ]);

        return response()->json([
            'message' => 'Notifications marked as read',
            'updated_count' => $updatedCount
        ]);
    }


    public function unreadCount()
    {
        $userId = Auth::id();

        $unreadCount = Notification::where('user_id', $userId)
            ->where('is_read', false)
            ->count();

        return response()->json([
            'unread_count' => $unreadCount
        ]);
    }
}
