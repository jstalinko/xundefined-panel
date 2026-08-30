<?php

return [
    /*
    |--------------------------------------------------------------------------
    | CoinPayments API Credentials
    |--------------------------------------------------------------------------
    |
    | Your CoinPayments Public and Private API keys generated from your
    | CoinPayments account (Account Settings -> API Keys).
    |
    */
    'public_key' => env('COINPAYMENTS_PUBLIC_KEY', ''),
    'private_key' => env('COINPAYMENTS_PRIVATE_KEY', ''),

    /*
    |--------------------------------------------------------------------------
    | CoinPayments Merchant ID & IPN Secret
    |--------------------------------------------------------------------------
    |
    | Your Merchant ID found on your CoinPayments Account Settings page,
    | and your custom IPN Secret used for HMAC-SHA512 signature verification.
    |
    */
    'merchant_id' => env('COINPAYMENTS_MERCHANT_ID', ''),
    'ipn_secret' => env('COINPAYMENTS_IPN_SECRET', ''),

    /*
    |--------------------------------------------------------------------------
    | Default Currencies & Settings
    |--------------------------------------------------------------------------
    |
    | currency1 is the original fiat or store base currency (e.g. IDR, USD, EUR).
    | default_crypto is the default destination cryptocurrency (e.g. USDT.TRC20, LTC, BTC).
    |
    */
    'default_currency' => env('COINPAYMENTS_DEFAULT_CURRENCY', 'IDR'),
    'default_crypto' => env('COINPAYMENTS_DEFAULT_CRYPTO', 'USDT.TRC20'),

    /*
    |--------------------------------------------------------------------------
    | IPN URL & Debug Settings
    |--------------------------------------------------------------------------
    |
    | ipn_url can be explicitly set or left null to automatically use the route.
    | debug_email is an optional email to receive error reports if an IPN fails.
    |
    */
    'ipn_url' => env('COINPAYMENTS_IPN_URL', null),
    'debug_email' => env('COINPAYMENTS_DEBUG_EMAIL', null),

    /*
    |--------------------------------------------------------------------------
    | API URL Endpoint
    |--------------------------------------------------------------------------
    |
    | CoinPayments API endpoint for Legacy API requests.
    |
    */
    'api_url' => env('COINPAYMENTS_API_URL', 'https://www.coinpayments.net/api.php'),

    /*
    |--------------------------------------------------------------------------
    | Request Timeout
    |--------------------------------------------------------------------------
    |
    | cURL request timeout in seconds.
    |
    */
    'timeout' => (int) env('COINPAYMENTS_TIMEOUT', 30),
];
