<?php

use App\Services\Payments\Gateways\CreditCardGateway;
use App\Services\Payments\Gateways\PaypalGateway;

return [

    /*
    |--------------------------------------------------------------------------
    | Forced simulation outcome
    |--------------------------------------------------------------------------
    | Take-home gateways simulate processing. Leave empty for the default
    | (always successful) behaviour, or set to "failed" to force the decline
    | path for demos and tests. Injected into every gateway as `fake_outcome`.
    */
    'fake_outcome' => env('PAYMENT_FAKE_OUTCOME'),

    /*
    |--------------------------------------------------------------------------
    | Gateway registry
    |--------------------------------------------------------------------------
    | Maps a payment `method` to the gateway class that handles it, plus that
    | gateway's credentials (pulled from .env). This is the ONLY place to touch
    | when adding a new gateway:
    |
    |   1. Create a class implementing PaymentGatewayInterface
    |      (extend AbstractSimulatedGateway for the simulated variant).
    |   2. Add an entry below: 'method' => ['driver' => YourGateway::class, ...creds].
    |   3. Add credentials to .env.
    |
    | The `fake_outcome` value is merged into each gateway's config by the
    | factory so simulated gateways honour it without extra wiring.
    */
    'gateways' => [

        'credit_card' => [
            'driver' => CreditCardGateway::class,
            'key' => env('CREDIT_CARD_API_KEY'),
            'secret' => env('CREDIT_CARD_API_SECRET'),
            'fake_outcome' => env('PAYMENT_FAKE_OUTCOME'),
        ],

        'paypal' => [
            'driver' => PaypalGateway::class,
            'client_id' => env('PAYPAL_CLIENT_ID'),
            'secret' => env('PAYPAL_SECRET'),
            'fake_outcome' => env('PAYMENT_FAKE_OUTCOME'),
        ],

    ],

];
