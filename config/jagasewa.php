<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Identity Document Retention Policy
    |--------------------------------------------------------------------------
    |
    | Defines document retention periods for identity documents (KTP & selfie):
    | - closed_days: retention period after booking is closed (default: 7 days)
    | - cancelled_days: retention period after booking is cancelled or rejected (default: 3 days)
    | - fallback_days: safety retention period if booking is never finalized (default: 90 days)
    |
    */
    'retention' => [
        'closed_days' => (int) env('DOC_RETENTION_CLOSED_DAYS', env('DOCUMENT_RETENTION_DAYS', 7)),
        'cancelled_days' => (int) env('DOC_RETENTION_CANCELLED_DAYS', 3),
        'fallback_days' => (int) env('DOC_RETENTION_FALLBACK_DAYS', 90),
    ],

    /*
    |--------------------------------------------------------------------------
    | Temporary Signed URL TTL
    |--------------------------------------------------------------------------
    |
    | Maximum TTL in minutes for vendor-signed URLs accessing private documents.
    |
    */
    'signed_url_ttl_minutes' => (int) env('SIGNED_URL_TTL_MINUTES', 60),
];
