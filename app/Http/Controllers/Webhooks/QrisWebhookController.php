<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Services\QrisService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QrisWebhookController extends Controller
{
    public function __construct(
        protected QrisService $qrisService
    ) {}

    /**
     * Webhook receiver for QRIS settlements (from bank or aggregator).
     */
    public function handle(Request $request): JsonResponse
    {
        $referenceCode = $request->input('reference_code')
            ?? $request->input('order_id')
            ?? $request->input('bill_number');

        if (empty($referenceCode)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Field reference_code or order_id is required.',
            ], 422);
        }

        $provider = (string) ($request->input('provider') ?? 'qris_gateway');
        $externalId = (string) ($request->input('transaction_id') ?? $request->input('id') ?? '');

        $result = $this->qrisService->settlePayment($referenceCode, $provider, $externalId);

        if (! ($result['success'] ?? false)) {
            return response()->json([
                'status' => 'error',
                'message' => $result['message'] ?? 'Transaction not found.',
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Payment settled successfully.',
            'data' => $result,
        ]);
    }

    /**
     * Public / testing simulation endpoint.
     */
    public function simulate(string $referenceCode): JsonResponse
    {
        $result = $this->qrisService->settlePayment($referenceCode, 'manual_simulator');

        if (! ($result['success'] ?? false)) {
            return response()->json([
                'status' => 'error',
                'message' => $result['message'] ?? 'Transaction not found.',
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Simulated payment completed.',
            'data' => $result,
        ]);
    }
}
