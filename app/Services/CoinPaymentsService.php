<?php

namespace App\Services;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class CoinPaymentsService
{
    protected string $publicKey;
    protected string $privateKey;
    protected string $merchantId;
    protected string $ipnSecret;
    protected string $apiUrl;
    protected int $timeout;
    protected ?string $debugEmail;
    protected string $defaultCurrency;
    protected string $defaultCrypto;

    public function __construct()
    {
        $this->publicKey = config('coinpayments.public_key') ?? env('COINPAYMENTS_PUBLIC_KEY', '');
        $this->privateKey = config('coinpayments.private_key') ?? env('COINPAYMENTS_PRIVATE_KEY', '');
        $this->merchantId = config('coinpayments.merchant_id') ?? env('COINPAYMENTS_MERCHANT_ID', '');
        $this->ipnSecret = config('coinpayments.ipn_secret') ?? env('COINPAYMENTS_IPN_SECRET', '');
        $this->apiUrl = config('coinpayments.api_url', 'https://www.coinpayments.net/api.php');
        $this->timeout = (int) config('coinpayments.timeout', 30);
        $this->debugEmail = config('coinpayments.debug_email') ?? env('COINPAYMENTS_DEBUG_EMAIL', null);
        $this->defaultCurrency = config('coinpayments.default_currency', 'IDR');
        $this->defaultCrypto = config('coinpayments.default_crypto', 'USDT.TRC20');
    }

    /**
     * Send API request to CoinPayments Legacy API.
     *
     * @param string $cmd The API command (e.g. create_transaction, get_tx_info, rates)
     * @param array $params Additional command parameters
     * @return array Decoded response from CoinPayments
     * @throws Exception
     */
    public function request(string $cmd, array $params = []): array
    {
        if (empty($this->publicKey) || empty($this->privateKey)) {
            throw new Exception('CoinPayments API Public Key or Private Key is not configured.');
        }

        $postData = array_merge([
            'version' => 1,
            'key'     => $this->publicKey,
            'cmd'     => $cmd,
            'format'  => 'json',
        ], $params);

        // Build exact query string required for HMAC-SHA512
        $postDataString = http_build_query($postData, '', '&', PHP_QUERY_RFC1738);

        // Generate HMAC SHA-512 signature using private key
        $hmac = hash_hmac('sha512', $postDataString, $this->privateKey);

        $ch = curl_init($this->apiUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $postDataString,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/x-www-form-urlencoded',
                'HMAC: ' . $hmac,
            ],
            CURLOPT_TIMEOUT        => $this->timeout,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);

        $rawResponse = curl_exec($ch);

        if ($rawResponse === false) {
            $curlError = curl_error($ch);
            curl_close($ch);
            Log::error('CoinPayments cURL request error', [
                'cmd'   => $cmd,
                'error' => $curlError,
            ]);
            throw new Exception('CoinPayments cURL Error: ' . $curlError);
        }

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $result = json_decode($rawResponse, true);

        if ($result === null) {
            Log::error('CoinPayments invalid JSON response', [
                'cmd'       => $cmd,
                'http_code' => $httpCode,
                'raw'       => $rawResponse,
            ]);
            throw new Exception('CoinPayments returned invalid response from server.');
        }

        if (isset($result['error']) && $result['error'] !== 'ok') {
            Log::warning('CoinPayments API returned error', [
                'cmd'   => $cmd,
                'error' => $result['error'],
            ]);
            throw new Exception('CoinPayments API Error: ' . $result['error']);
        }

        return [
            'http_code' => $httpCode,
            'result'    => $result['result'] ?? [],
            'raw'       => $rawResponse,
        ];
    }

    /**
     * Create a new transaction via CoinPayments API.
     *
     * @see https://legacy.coinpayments.net/apidoc-create-transaction
     *
     * @param array $params Required: amount, currency1, currency2, buyer_email
     *                      Optional: buyer_name, item_name, item_number, invoice, custom, ipn_url, success_url, cancel_url, address
     * @return array Transaction details containing txn_id, address, amount, qrcode_url, status_url, checkout_url, timeout, confirms_needed
     * @throws Exception
     */
    public function createTransaction(array $params): array
    {
        $required = ['amount', 'currency1', 'currency2', 'buyer_email'];
        foreach ($required as $field) {
            if (empty($params[$field])) {
                throw new Exception("Missing required field '{$field}' for CoinPayments create_transaction.");
            }
        }

        $response = $this->request('create_transaction', $params);

        return $response['result'] ?? [];
    }

    /**
     * Get transaction information.
     *
     * @param string $txId CoinPayments transaction ID
     * @param bool $full Include full detailed checkout and shipping information
     * @return array
     * @throws Exception
     */
    public function getTxInfo(string $txId, bool $full = true): array
    {
        $response = $this->request('get_tx_info', [
            'txid' => $txId,
            'full' => $full ? 1 : 0,
        ]);

        return $response['result'] ?? [];
    }

    /**
     * Get exchange rates and supported coins.
     *
     * @param array $params
     * @return array
     * @throws Exception
     */
    public function getRates(array $params = ['accepted' => 1, 'short' => 1]): array
    {
        $response = $this->request('rates', $params);
        return $response['result'] ?? [];
    }

    /**
     * Get basic merchant account information.
     *
     * @return array
     * @throws Exception
     */
    public function getBasicInfo(): array
    {
        $response = $this->request('get_basic_info');
        return $response['result'] ?? [];
    }

    /**
     * Get popular/enabled cryptocurrencies with display metadata.
     *
     * @return array
     */
    public function getAcceptedCoins(): array
    {
        try {
            $rates = $this->getRates(['accepted' => 1, 'short' => 1]);
            $coins = [];

            // Preset popular crypto icons and names
            $popularMap = [
                'USDT.TRC20' => ['name' => 'Tether USDT (TRC-20)', 'icon' => 'fa-solid fa-dollar-sign', 'network' => 'TRON Network (Fast & Low Fee)'],
                'BTC'        => ['name' => 'Bitcoin', 'icon' => 'fa-brands fa-bitcoin', 'network' => 'Bitcoin Network'],
                'LTC'        => ['name' => 'Litecoin', 'icon' => 'fa-solid fa-coins', 'network' => 'Litecoin Network (Fast Confirmation)'],
                'ETH'        => ['name' => 'Ethereum', 'icon' => 'fa-brands fa-ethereum', 'network' => 'ERC-20 Mainnet'],
                'TRX'        => ['name' => 'TRON (TRX)', 'icon' => 'fa-solid fa-bolt', 'network' => 'TRON Network'],
                'SOL'        => ['name' => 'Solana', 'icon' => 'fa-solid fa-sun', 'network' => 'Solana Network'],
                'DOGE'       => ['name' => 'Dogecoin', 'icon' => 'fa-solid fa-dog', 'network' => 'Dogecoin Network'],
                'BCH'        => ['name' => 'Bitcoin Cash', 'icon' => 'fa-brands fa-bitcoin', 'network' => 'Bitcoin Cash Network'],
                'XMR'        => ['name' => 'Monero', 'icon' => 'fa-solid fa-user-secret', 'network' => 'Monero Network (Private)'],
                'USDC.TRC20' => ['name' => 'USD Coin (TRC-20)', 'icon' => 'fa-solid fa-dollar-sign', 'network' => 'TRON Network'],
                'USDT.BEP20' => ['name' => 'Tether USDT (BEP-20)', 'icon' => 'fa-solid fa-dollar-sign', 'network' => 'BNB Smart Chain'],
                'USDT.ERC20' => ['name' => 'Tether USDT (ERC-20)', 'icon' => 'fa-solid fa-dollar-sign', 'network' => 'Ethereum Network'],
            ];

            foreach ($rates as $symbol => $data) {
                // Skip fiat tickers unless needed
                if (in_array($symbol, ['USD', 'EUR', 'GBP', 'CAD', 'AUD', 'IDR', 'JPY', 'INR', 'CNY', 'RUB', 'BRL', 'SGD', 'HKD', 'NZD', 'CHF', 'SEK', 'PLN', 'PHP', 'THB', 'MYR', 'VND', 'MXN', 'TRY', 'ZAR', 'AED', 'ARS', 'CLP', 'COP', 'CZK', 'DKK', 'HUF', 'ILS', 'NOK', 'PEN', 'RON', 'SAR', 'UAH'])) {
                    continue;
                }

                $meta = $popularMap[$symbol] ?? [
                    'name'    => $data['name'] ?? $symbol,
                    'icon'    => 'fa-solid fa-coins',
                    'network' => $symbol . ' Network',
                ];

                $coins[$symbol] = [
                    'symbol'         => $symbol,
                    'name'           => $meta['name'],
                    'icon'           => $meta['icon'],
                    'network'        => $meta['network'],
                    'rate_btc'       => $data['rate_btc'] ?? null,
                    'is_fiat'        => ($data['is_fiat'] ?? 0) == 1,
                    'confirms'       => $data['confirms'] ?? 1,
                    'accepted'       => ($data['accepted'] ?? 0) == 1,
                ];
            }

            // If popular coins are available, keep priority order
            $priorityKeys = ['USDT.TRC20', 'BTC', 'LTC', 'ETH', 'TRX', 'SOL', 'DOGE', 'BCH', 'XMR', 'USDT.BEP20'];
            $sorted = [];
            foreach ($priorityKeys as $key) {
                if (isset($coins[$key])) {
                    $sorted[$key] = $coins[$key];
                    unset($coins[$key]);
                }
            }

            return array_merge($sorted, $coins);
        } catch (Exception $e) {
            Log::error('Failed to fetch CoinPayments accepted coins', ['error' => $e->getMessage()]);

            // Fallback list of standard accepted coins
            return [
                'USDT.TRC20' => [
                    'symbol'  => 'USDT.TRC20',
                    'name'    => 'Tether USDT (TRC-20)',
                    'icon'    => 'fa-solid fa-dollar-sign',
                    'network' => 'TRON Network (Fast & Low Fee)',
                ],
                'BTC' => [
                    'symbol'  => 'BTC',
                    'name'    => 'Bitcoin',
                    'icon'    => 'fa-brands fa-bitcoin',
                    'network' => 'Bitcoin Network',
                ],
                'LTC' => [
                    'symbol'  => 'LTC',
                    'name'    => 'Litecoin',
                    'icon'    => 'fa-solid fa-coins',
                    'network' => 'Litecoin Network',
                ],
                'ETH' => [
                    'symbol'  => 'ETH',
                    'name'    => 'Ethereum',
                    'icon'    => 'fa-brands fa-ethereum',
                    'network' => 'ERC-20 Mainnet',
                ],
                'TRX' => [
                    'symbol'  => 'TRX',
                    'name'    => 'TRON (TRX)',
                    'icon'    => 'fa-solid fa-bolt',
                    'network' => 'TRON Network',
                ],
            ];
        }
    }

    /**
     * Validate incoming IPN Callback request.
     *
     * @see https://legacy.coinpayments.net/downloads/cpipn.phps
     * @see https://legacy.coinpayments.net/merchant-tools-ipn#auth
     *
     * @param Request $request
     * @return array ['valid' => bool, 'error' => ?string, 'data' => array]
     */
    public function validateIpn(Request $request): array
    {
        $postData = $request->all();

        // 1. Validate IPN mode
        $ipnMode = $request->input('ipn_mode');
        if ($ipnMode !== 'hmac') {
            $msg = 'IPN Mode is not HMAC (received: ' . ($ipnMode ?: 'none') . ')';
            $this->sendDebugReport('IPN Mode Error', $msg, $postData);
            return ['valid' => false, 'error' => $msg, 'data' => $postData];
        }

        // 2. Validate HMAC header
        $headerHmac = $request->header('HMAC') ?? $request->server('HTTP_HMAC');
        if (empty($headerHmac)) {
            $msg = 'No HMAC signature sent in HTTP headers.';
            $this->sendDebugReport('IPN Header Error', $msg, $postData);
            return ['valid' => false, 'error' => $msg, 'data' => $postData];
        }

        // 3. Validate raw POST body
        $rawBody = $request->getContent();
        if ($rawBody === false || $rawBody === '') {
            $msg = 'Error reading POST data / raw body is empty.';
            $this->sendDebugReport('IPN Body Error', $msg, $postData);
            return ['valid' => false, 'error' => $msg, 'data' => $postData];
        }

        // 4. Validate Merchant ID
        $merchant = trim((string) $request->input('merchant'));
        $expectedMerchant = trim($this->merchantId);

        if (empty($merchant) || (!empty($expectedMerchant) && $merchant !== $expectedMerchant)) {
            $msg = 'No or incorrect Merchant ID passed (received: ' . $merchant . ', expected: ' . $expectedMerchant . ')';
            $this->sendDebugReport('IPN Merchant ID Error', $msg, $postData);
            return ['valid' => false, 'error' => $msg, 'data' => $postData];
        }

        // 5. Compute and verify HMAC-SHA512 signature
        $ipnSecret = trim($this->ipnSecret);
        if (empty($ipnSecret)) {
            $msg = 'CoinPayments IPN Secret is not configured on the server.';
            $this->sendDebugReport('IPN Config Error', $msg, $postData);
            return ['valid' => false, 'error' => $msg, 'data' => $postData];
        }

        $calculatedHmac = hash_hmac('sha512', $rawBody, $ipnSecret);

        if (!hash_equals($calculatedHmac, $headerHmac)) {
            $msg = 'HMAC signature does not match incoming payload.';
            $this->sendDebugReport('IPN Signature Error', $msg, $postData);
            return ['valid' => false, 'error' => $msg, 'data' => $postData];
        }

        return [
            'valid' => true,
            'error' => null,
            'data'  => $postData,
        ];
    }

    /**
     * Send debug email notification on IPN error or critical events.
     *
     * @param string $subject
     * @param string $errorMessage
     * @param array $postData
     */
    public function sendDebugReport(string $subject, string $errorMessage, array $postData = []): void
    {
        Log::warning('CoinPayments IPN Issue: ' . $subject, [
            'error'     => $errorMessage,
            'post_data' => $postData,
        ]);

        if (!empty($this->debugEmail)) {
            try {
                $report = "Error: " . $errorMessage . "\n\n";
                $report .= "Timestamp: " . date('Y-m-d H:i:s T') . "\n\n";
                $report .= "POST Data:\n";
                foreach ($postData as $k => $v) {
                    $valStr = is_array($v) ? json_encode($v) : (string) $v;
                    $report .= "| {$k} | = | {$valStr} |\n";
                }

                @mail($this->debugEmail, 'CoinPayments IPN: ' . $subject, $report);
            } catch (Exception $e) {
                Log::error('Failed to dispatch CoinPayments debug email: ' . $e->getMessage());
            }
        }
    }

    public function getDefaultCurrency(): string
    {
        return $this->defaultCurrency;
    }

    public function getDefaultCrypto(): string
    {
        return $this->defaultCrypto;
    }
}
