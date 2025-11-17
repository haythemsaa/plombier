<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Booking;
use Illuminate\Support\Str;

class PaymentService
{
    /**
     * Initiate a payment
     */
    public function initiatePayment($bookingId, $paymentMethod, $gateway = 'paytech')
    {
        $booking = Booking::findOrFail($bookingId);

        // Check if booking is payable
        if (!in_array($booking->status, ['pending', 'confirmed'])) {
            throw new \Exception('This booking cannot be paid at this time');
        }

        // Check if already paid
        if ($booking->payment_status === 'paid') {
            throw new \Exception('This booking has already been paid');
        }

        // Create payment record
        $payment = Payment::create([
            'booking_id' => $bookingId,
            'amount' => $booking->total,
            'payment_method' => $paymentMethod,
            'gateway' => $gateway,
            'status' => 'pending',
            'metadata' => [
                'booking_number' => $booking->booking_number,
                'client_id' => $booking->client_id,
            ],
        ]);

        // Generate payment URL based on gateway
        $paymentUrl = $this->generatePaymentUrl($payment, $gateway);

        return [
            'payment' => $payment,
            'payment_url' => $paymentUrl,
        ];
    }

    /**
     * Generate payment URL for gateway
     */
    protected function generatePaymentUrl($payment, $gateway)
    {
        switch ($gateway) {
            case 'paytech':
                return $this->generatePayTechUrl($payment);

            case 'clictopay':
                return $this->generateClicToPayUrl($payment);

            case 'stripe':
                return $this->generateStripeUrl($payment);

            default:
                throw new \Exception('Unsupported payment gateway');
        }
    }

    /**
     * Generate PayTech payment URL
     */
    protected function generatePayTechUrl($payment)
    {
        // PayTech API integration
        $merchantId = config('services.paytech.merchant_id');
        $apiKey = config('services.paytech.api_key');
        $apiUrl = config('services.paytech.api_url');

        // Prepare payment data
        $data = [
            'item_name' => 'ServiceHub Booking #' . $payment->booking->booking_number,
            'item_price' => $payment->amount,
            'currency' => 'TND',
            'ref_command' => $payment->id,
            'command_name' => 'Booking Payment',
            'env' => config('app.env') === 'production' ? 'prod' : 'test',
            'ipn_url' => route('webhooks.paytech'),
            'success_url' => config('app.url') . '/payment/success',
            'cancel_url' => config('app.url') . '/payment/cancel',
        ];

        // TODO: Implement actual PayTech API call
        // For now, return a mock URL
        return "https://paytech.sn/payment?ref=" . $payment->id;
    }

    /**
     * Generate ClicToPay URL
     */
    protected function generateClicToPayUrl($payment)
    {
        // TODO: Implement ClicToPay integration
        return "https://clictopay.com/payment?ref=" . $payment->id;
    }

    /**
     * Generate Stripe URL
     */
    protected function generateStripeUrl($payment)
    {
        // TODO: Implement Stripe integration
        return "https://checkout.stripe.com?ref=" . $payment->id;
    }

    /**
     * Process payment callback/webhook
     */
    public function processPaymentCallback($paymentId, $status, $transactionId = null, $metadata = [])
    {
        $payment = Payment::findOrFail($paymentId);

        // Update payment status
        $payment->update([
            'status' => $status,
            'transaction_id' => $transactionId,
            'metadata' => array_merge($payment->metadata ?? [], $metadata),
        ]);

        // Update booking payment status
        if ($status === 'completed') {
            $payment->booking->update([
                'payment_status' => 'paid',
                'payment_method' => $payment->payment_method,
            ]);
        }

        return $payment;
    }

    /**
     * Process refund
     */
    public function processRefund($paymentId, $amount = null)
    {
        $payment = Payment::findOrFail($paymentId);

        if ($payment->status !== 'completed') {
            throw new \Exception('Can only refund completed payments');
        }

        $refundAmount = $amount ?? $payment->amount;

        // TODO: Implement actual refund logic with payment gateway

        $payment->update([
            'status' => 'refunded',
            'metadata' => array_merge($payment->metadata ?? [], [
                'refund_amount' => $refundAmount,
                'refunded_at' => now()->toIso8601String(),
            ]),
        ]);

        $payment->booking->update([
            'payment_status' => 'refunded',
        ]);

        return $payment;
    }

    /**
     * Get payment status
     */
    public function getPaymentStatus($paymentId)
    {
        $payment = Payment::with('booking')->findOrFail($paymentId);

        return [
            'payment' => $payment,
            'can_refund' => $payment->status === 'completed',
        ];
    }
}
