<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderMessage;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    public function index(Request $request, Order $order)
    {
        $user = $request->user();

        // Security: Only customer or assigned rider can see messages
        if ($order->customer_id !== $user->id && $order->rider_id !== $user->rider?->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $messages = $order->messages()->with('sender:id,name')->get();

        // Mark as read for the current user
        $order->messages()
            ->where('sender_id', '!=', $user->id)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return response()->json($messages);
    }

    public function store(Request $request, Order $order)
    {
        $user = $request->user();

        // Security: Only customer or assigned rider can send messages
        if ($order->customer_id !== $user->id && $order->rider_id !== $user->rider?->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'message' => 'required|string|max:1000',
        ]);

        $message = $order->messages()->create([
            'sender_id' => $user->id,
            'message' => $validated['message'],
        ]);

        return response()->json($message->load('sender:id,name'));
    }
}
