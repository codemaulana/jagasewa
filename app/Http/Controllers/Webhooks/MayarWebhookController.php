<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Services\Billing\MayarWebhookHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MayarWebhookController extends Controller
{
    /**
     * Handle incoming Mayar.id webhook notification.
     */
    public function handle(Request $request, MayarWebhookHandler $handler): JsonResponse
    {
        // 1. Verify signature or token (SEC-23 / BLT-08)
        $signature = $request->header('x-mayar-signature')
            ?? $request->header('x-signature')
            ?? $request->header('x-mayar-token')
            ?? $request->bearerToken()
            ?? $request->header('authorization')
            ?? $request->query('token');

        $rawBody = $request->getContent();

        Log::info('Mayar webhook incoming', [
            'headers' => $request->headers->all(),
            'query' => $request->query(),
            'event' => $request->input('event'),
        ]);

        // Acknowledge Mayar dashboard test ping directly (no state change, completely safe)
        if ($request->input('event') === 'testing' || $request->input('type') === 'Testing URL') {
            Log::info('Mayar webhook testing URL ping acknowledged.');

            return response()->json([
                'status' => 'success',
                'message' => 'Mayar webhook test ping acknowledged',
            ], 200);
        }

        if (! $handler->verifySignature($signature, $rawBody)) {
            if (app()->environment('local')) {
                Log::warning('Mayar webhook signature bypassed for local testing environment');
            } else {
                Log::warning('Mayar webhook rejected: Invalid signature or token', [
                    'ip' => $request->ip(),
                    'has_signature' => ! empty($signature),
                ]);

                return response()->json([
                    'error' => 'Unauthorized: Invalid Mayar webhook signature',
                ], 401);
            }
        }

        // 2. Process payload through handler
        $result = $handler->handle($request->all());

        return response()->json([
            'status' => $result['status'],
            'message' => $result['message'],
        ], $result['code']);
    }
}
