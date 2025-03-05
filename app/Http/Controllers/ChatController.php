<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Message;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ChatController extends Controller
{
    public function storeMessage(Request $request)
    {
        try {
            $request->validate([
                'message' => 'nullable|string',
                'file' => 'nullable|file|max:10240|mimes:jpg,jpeg,png,pdf,doc,docx',
                'sender_id' => 'required|integer',
                'receiver_id' => 'required|integer'
            ]);


            $message = new Message();
            $message->sender_id = Auth::id();
            $message->receiver_id = $request->receiver_id;
            $message->message = $request->message;

            if ($request->hasFile('file')) {
                $filePath = $request->file('file')->store('chat_files', 'public');
                $message->file_path = $filePath;
            }

            $message->save();

            return response()->json([
                'success' => true,
                'message' => 'Message sent successfully',
                'new_message' => $message
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send message',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function getMessages(Request $request)
    {
        $receiverId = $request->receiver_id;
        $messages = Message::where(function ($query) use ($receiverId) {
            $query->where('sender_id', Auth::id())
                ->where('receiver_id', $receiverId);
        })->orWhere(function ($query) use ($receiverId) {
            $query->where('receiver_id', Auth::id())
                ->where('sender_id', $receiverId);
        })->orderBy('created_at', 'asc')->get();

        $messages->transform(function ($message) {
            if ($message->file_path) {
                $message->file_url = asset('storage/' . $message->file_path);
            }
            return $message;
        });

        return response()->json($messages);
    }
    public function markAsRead(Request $request)
    {
        $userId = Auth::id();
        $senderId = $request->input('sender_id');

        $unreadMessages = Message::where('sender_id', $senderId)
            ->where('receiver_id', $userId)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        // broadcast(new MessageRead($senderId, $userId))->toOthers();

        return response()->json([
            'message' => 'Messages marked as read',
            'updated_count' => $unreadMessages
        ]);
    }
}
