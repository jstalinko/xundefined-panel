<?php

$apiPublicKey  = 'de9ad6420a09763ea4b45e3e2cb2c89156ab1b40290872eb12ba7001586b5422';
$apiPrivateKey = 'b6c071A0d85516394491be03ad4eec918bD666c755731a3515454C5d947d68D4';

function coinPaymentsRequest(
    string $cmd,
    array $params,
    string $publicKey,
    string $privateKey
): array {
    
    $url = 'https://www.coinpayments.net/api.php';

    // Base parameters required by CoinPayments Legacy API
    $postData = array_merge([
        'version' => 1,
        'key'     => $publicKey,
        'cmd'     => $cmd,
        'format'  => 'json',
    ], $params);

    /**
     * IMPORTANT:
     * This exact string is used for HMAC.
     */
    $postDataString = http_build_query(
        $postData,
        '',
        '&',
        PHP_QUERY_RFC1738
    );

    // Generate HMAC SHA-512
    $hmac = hash_hmac(
        'sha512',
        $postDataString,
        $privateKey
    );

    $ch = curl_init($url);

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $postDataString,

        CURLOPT_HTTPHEADER => [
            'Content-Type: application/x-www-form-urlencoded',
            'HMAC: ' . $hmac,
        ],

        CURLOPT_TIMEOUT => 30,
    ]);

    $response = curl_exec($ch);

    if ($response === false) {
        throw new Exception(
            'cURL Error: ' . curl_error($ch)
        );
    }

    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    curl_close($ch);

    $result = json_decode($response, true);

    return [
        'http_code' => $httpCode,
        'response'  => $result,
        'raw'       => $response,
    ];
}

$cps = coinPaymentsRequest('get_tx_ids', [],
    $apiPublicKey,
    $apiPrivateKey);

print_r($cps);
