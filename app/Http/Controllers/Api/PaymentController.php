<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\SignatureVerificationException;
use Stripe\PaymentIntent;
use Stripe\Refund;
use Stripe\Stripe;
use Stripe\Webhook;

class PaymentController extends Controller
{
    public function __construct()
    {
        Stripe::setApiKey(config('services.stripe.secret'));
    }

    /**
     * Create a payment intent for an order
     */
    public function createPaymentIntent(Request $request)
    {
        $validated = $request->validate([
            'order_id' => 'required|exists:orders,id',
            'payment_method' => 'required|in:cod,card,easypaisa,jazzcash',
        ]);

        $order = Order::findOrFail($validated['order_id']);

        // Ensure the user owns this order
        if ($order->customer_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Check if payment already exists and is completed
        if ($order->payment && $order->payment->status === 'completed') {
            return response()->json([
                'message' => 'Payment already completed for this order',
                'payment' => $order->payment,
            ], 400);
        }

        // If payment doesn't exist, create it. If it exists but pending, reuse/update it.
        $method = in_array($validated['payment_method'], ['easypaisa', 'jazzcash'], true)
            ? 'wallet'
            : $validated['payment_method'];

        $payment = $order->payment ?: new Payment(['order_id' => $order->id]);
        $payment->amount = $order->total_amount;
        $payment->method = $method;
        $payment->status = 'pending';
        $payment->save();

        if ($validated['payment_method'] === 'cod') {
            $order->update(['payment_status' => 'pending']);

            return response()->json([
                'message' => 'Cash on Delivery selected',
                'payment' => $payment,
                'requires_action' => false,
            ]);
        }

        if ($validated['payment_method'] === 'card') {
            try {
                $intent = PaymentIntent::create([
                    'amount' => (int) ($order->total_amount * 100), // Stripe expects cents
                    'currency' => 'pkr',
                    'automatic_payment_methods' => ['enabled' => true],
                    'metadata' => [
                        'order_id' => $order->id,
                        'payment_id' => $payment->id,
                        'customer_name' => $request->user()->name,
                    ],
                ]);

                $payment->update(['transaction_id' => $intent->id]);

                return response()->json([
                    'message' => 'Payment intent created',
                    'payment' => $payment,
                    'requires_action' => true,
                    'client_secret' => $intent->client_secret,
                ]);
            } catch (\Exception $e) {
                return response()->json(['message' => 'Stripe Error: ' . $e->getMessage()], 500);
            }
        }

        // Placeholder for other methods
        return response()->json([
            'message' => 'Payment method initiated',
            'payment' => $payment,
            'requires_action' => false,
        ]);
    }

    /**
     * Confirm a payment (called after successful payment gateway response)
     */
    public function confirmPayment(Request $request, $paymentId)
    {
        $validated = $request->validate([
            'payment_intent_id' => 'nullable|string',
            'metadata' => 'nullable|array',
        ]);

        $payment = Payment::findOrFail($paymentId);
        $order = $payment->order;

        if ($order->customer_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($payment->method === 'card') {
            $intentId = $validated['payment_intent_id'] ?? $payment->transaction_id;
            if (!$intentId) {
                return response()->json(['message' => 'Missing payment intent id'], 422);
            }

            try {
                $intent = PaymentIntent::retrieve($intentId);
            } catch (\Exception $e) {
                return response()->json(['message' => 'Unable to verify payment intent'], 400);
            }

            if ($intent->status !== 'succeeded') {
                return response()->json([
                    'message' => 'Payment not completed yet',
                    'status' => $intent->status,
                ], 409);
            }

            $payment->update([
                'status' => 'completed',
                'transaction_id' => $intent->id,
                'metadata' => array_merge($payment->metadata ?? [], $validated['metadata'] ?? [], [
                    'latest_intent_status' => $intent->status,
                ]),
            ]);
            $order->update(['payment_status' => 'paid']);
        } else {
            $payment->update([
                'status' => 'completed',
                'metadata' => array_merge($payment->metadata ?? [], $validated['metadata'] ?? []),
            ]);
            $order->update(['payment_status' => 'paid']);
        }

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

        if ($order->customer_id !== request()->user()->id && $order->shop->user_id !== request()->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json([
            'payment' => $order->payment,
        ]);
    }

    public function webhook(Request $request)
    {
        $secret = config('services.stripe.webhook_secret');
        $payload = $request->getContent();
        $signature = (string) $request->header('Stripe-Signature', '');

        if (empty($secret)) {
            Log::warning('Stripe webhook secret not configured.');
            return response()->json(['message' => 'Webhook secret missing'], 500);
        }

        try {
            $event = Webhook::constructEvent($payload, $signature, $secret);
        } catch (SignatureVerificationException $e) {
            return response()->json(['message' => 'Invalid signature'], 400);
        } catch (\UnexpectedValueException $e) {
            return response()->json(['message' => 'Invalid payload'], 400);
        }

        if ($event->type === 'payment_intent.succeeded') {
            $intent = $event->data->object;
            $paymentId = $intent->metadata->payment_id ?? null;
            if ($paymentId) {
                DB::transaction(function () use ($paymentId, $intent) {
                    $payment = Payment::find($paymentId);
                    if (!$payment) {
                        return;
                    }
                    $payment->update([
                        'status' => 'completed',
                        'transaction_id' => $intent->id,
                        'metadata' => array_merge($payment->metadata ?? [], [
                            'webhook_event' => 'payment_intent.succeeded',
                            'latest_intent_status' => $intent->status,
                        ]),
                    ]);
                    $payment->order->update(['payment_status' => 'paid']);
                    Log::info("Payment succeeded via webhook for Payment ID: {$paymentId}, Intent: {$intent->id}");
                });
            }
        }

        if ($event->type === 'payment_intent.payment_failed') {
            $intent = $event->data->object;
            $paymentId = $intent->metadata->payment_id ?? null;
            if ($paymentId) {
                DB::transaction(function () use ($paymentId, $intent) {
                    $payment = Payment::find($paymentId);
                    if (!$payment) {
                        return;
                    }
                    $payment->update([
                        'status' => 'failed',
                        'transaction_id' => $intent->id ?? $payment->transaction_id,
                        'metadata' => array_merge($payment->metadata ?? [], [
                            'webhook_event' => 'payment_intent.payment_failed',
                            'latest_intent_status' => $intent->status ?? 'failed',
                        ]),
                    ]);
                    $payment->order->update(['payment_status' => 'failed']);
                    Log::warning("Payment failed via webhook for Payment ID: {$paymentId}, Intent: {$intent->id}");
                });
            }
        }

        if ($event->type === 'charge.refunded') {
            $charge = $event->data->object;
            $intentId = $charge->payment_intent ?? null;
            if ($intentId) {
                DB::transaction(function () use ($intentId) {
                    $payment = Payment::where('transaction_id', $intentId)->first();
                    if (!$payment) {
                        return;
                    }
                    $payment->update([
                        'status' => 'refunded',
                        'metadata' => array_merge($payment->metadata ?? [], [
                            'webhook_event' => 'charge.refunded',
                        ]),
                    ]);
                    $payment->order->update(['payment_status' => 'refunded']);
                });
            }
        }

        return response()->json(['received' => true]);
    }

    public function refundCardPayment(Payment $payment, string $reason = 'requested_by_customer'): array
    {
        if (!$payment->transaction_id) {
            return [false, 'Missing Stripe transaction id'];
        }

        try {
            Refund::create([
                'payment_intent' => $payment->transaction_id,
                'reason' => $reason,
            ]);

            $payment->update([
                'status' => 'refunded',
                'metadata' => array_merge($payment->metadata ?? [], [
                    'refund_reason' => $reason,
                ]),
            ]);
            $payment->order->update(['payment_status' => 'refunded']);

            return [true, null];
        } catch (\Exception $e) {
            return [false, $e->getMessage()];
        }
    }
}
