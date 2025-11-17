<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PaymentController extends Controller
{
    protected $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    /**
     * Initiate a payment
     */
    public function initiate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'booking_id' => 'required|uuid|exists:bookings,id',
            'payment_method' => 'required|in:card,d17,wallet,cash',
            'gateway' => 'nullable|in:paytech,clictopay,stripe',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Cash payments don't need gateway processing
            if ($request->payment_method === 'cash') {
                return response()->json([
                    'message' => 'Cash payment selected',
                    'payment_method' => 'cash',
                    'instructions' => 'Pay the provider in cash after service completion'
                ]);
            }

            $gateway = $request->gateway ?? 'paytech';

            $result = $this->paymentService->initiatePayment(
                $request->booking_id,
                $request->payment_method,
                $gateway
            );

            return response()->json([
                'message' => 'Payment initiated successfully',
                'payment' => $result['payment'],
                'payment_url' => $result['payment_url'],
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to initiate payment',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get payment status
     */
    public function status($id)
    {
        try {
            $result = $this->paymentService->getPaymentStatus($id);

            return response()->json($result);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Payment not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Process refund
     */
    public function refund(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'nullable|numeric|min:0',
            'reason' => 'required|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $payment = $this->paymentService->processRefund(
                $id,
                $request->amount
            );

            return response()->json([
                'message' => 'Refund processed successfully',
                'payment' => $payment
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to process refund',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
