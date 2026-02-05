<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    /**
     * Create a payment intent for an order
     */
    public function createPaymentIntent(Request $request)
    {
        $validated = $request->validate([
            'order_id' => 'required|exists:orders,id',
            'payment_method' => 'required|in:cod,card,wallet',
        ]);

        $order = Order::findOrFail($validated['order_id']);

        // Ensure the user owns this order
        if ($order->customer_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Check if payment already exists
        if ($order->payment) {
            return response()->json([
                'message' => 'Payment already exists for this order',
                'payment' => $order->payment,
            ], 400);
        }

        // Create payment record
        $payment = Payment::create([
            'order_id' => $order->id,
            'amount' => $order->total_amount,
            'method' => $validated['payment_method'],
            'status' => $validated['payment_method'] === 'cod' ? 'pending' : 'pending',
        ]);

        // For COD, mark as pending (will be completed on delivery)
        if ($validated['payment_method'] === 'cod') {
            $order->update(['payment_status' => 'pending']);

            return response()->json([
                'message' => 'Cash on Delivery selected',
                'payment' => $payment,
                'requires_action' => false,
            ]);
        }

        // For card/wallet, return payment intent (in real app, integrate with Stripe/PayPal)
        return response()->json([
            'message' => 'Payment intent created',
            'payment' => $payment,
            'requires_action' => true,
            'client_secret' => 'demo_secret_' . $payment->id, // Replace with real payment gateway secret
        ]);
    }

    /**
     * Confirm a payment (called after successful payment gateway response)
     */
    public function confirmPayment(Request $request, $paymentId)
    {
        $validated = $request->validate([
            'transaction_id' => 'nullable|string',
            'metadata' => 'nullable|array',
        ]);

        $payment = Payment::findOrFail($paymentId);
        $order = $payment->order;

        // Ensure the user owns this order
        if ($order->customer_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Update payment status
        $payment->update([
            'status' => 'completed',
            'transaction_id' => $validated['transaction_id'] ?? null,
            'metadata' => $validated['metadata'] ?? null,
        ]);

        // Update order payment status
        $order->update(['payment_status' => 'paid']);

        return response()->json([
            'message' => 'Payment confirmed successfully',
            'payment' => $payment,
            'order' => $order->load('items.product'),
        ]);
    }

    /**
     * Get payment details for an order
     */
    public function show($orderId)
    {
        $order = Order::with('payment')->findOrFail($orderId);

        // Ensure the user owns this order or is the shop owner
        if ($order->customer_id !== request()->user()->id && $order->shop->user_id !== request()->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json([
            'payment' => $order->payment,
        ]);
    }

    /**
     * Webhook handler for payment gateway callbacks (Stripe, PayPal, etc.)
     */
    public function webhook(Request $request)
    {
        // In production, verify webhook signature
        // For now, this is a placeholder for future integration

        $payload = $request->all();

        // Example: Handle Stripe webhook
        // if ($payload['type'] === 'payment_intent.succeeded') {
        //     $paymentId = $payload['data']['object']['metadata']['payment_id'];
        //     $payment = Payment::find($paymentId);
        //     $payment->update(['status' => 'completed']);
        // }

        return response()->json(['received' => true]);
    }
}
