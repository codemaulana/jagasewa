<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Services\Billing\XenditWebhookHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class XenditWebhookController extends Controller
{
    /**
     * Handle incoming Xendit webhook callback.
     */
    public function handle(Request $request, XenditWebhookHandler $handler): JsonResponse
    {
        // Step 1: Verify signature / callback token (SEC-23 / BLT-08)
        $token = $request->header('x-callback-token');

        if (! $handler->verifyCallbackToken($token)) {
            Log::warning('Xendit webhook rejected: Invalid or missing x-callback-token', [
                'ip' => $request->ip(),
                'has_token' => ! empty($token),
            ]);

            return response()->json([
                'error' => 'Unauthorized: Invalid callback token',
            ], 401);
        }

        // Steps 2 - 10: Process webhook through handler
        $result = $handler->handle($request->all());

        return response()->json([
            'status' => $result['status'],
            'message' => $result['message'],
        ], $result['code']);
    }
}
