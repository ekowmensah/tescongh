<?php
require_once 'config/hubtel.php';

/**
 * Hubtel Payment Gateway Integration
 * Handles mobile money and card payments for TESCON Ghana dues
 */
class HubtelPayment {
    private $clientId;
    private $clientSecret;
    private $merchantAccountNumber;
    private $apiUrl = 'https://api.hubtel.com/v1/merchantaccount';
    private $callbackUrl;

    public function __construct($callbackUrl = null) {
        // Use configuration constants
        $this->clientId = HUBTEL_CLIENT_ID;
        $this->clientSecret = HUBTEL_CLIENT_SECRET;
        $this->merchantAccountNumber = HUBTEL_MERCHANT_ACCOUNT_NUMBER;
        $this->callbackUrl = $callbackUrl ?: PAYMENT_CALLBACK_URL;
    }

    /**
     * Get access token from Hubtel
     */
    private function getAccessToken() {
        $url = 'https://api.hubtel.com/v1/merchantaccount/accesstoken';
        $credentials = base64_encode($this->clientId . ':' . $this->clientSecret);

        $headers = [
            'Authorization: Basic ' . $credentials,
            'Content-Type: application/json'
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200) {
            $data = json_decode($response, true);
            return $data['access_token'] ?? null;
        }

        return null;
    }

    /**
     * Initiate mobile money payment
     * @param array $paymentData ['amount', 'phone', 'description', 'reference']
     */
    public function initiateMobileMoneyPayment($paymentData) {
        $accessToken = $this->getAccessToken();
        if (!$accessToken) {
            return ['success' => false, 'error' => 'Failed to get access token'];
        }

        $url = $this->apiUrl . '/onlinecheckout/invoice/create';

        $payload = [
            'invoice' => [
                'items' => [
                    [
                        'name' => $paymentData['description'],
                        'quantity' => 1,
                        'unit_price' => $paymentData['amount'],
                        'total_price' => $paymentData['amount']
                    ]
                ],
                'total_amount' => $paymentData['amount'],
                'description' => $paymentData['description'],
                'customer_name' => $paymentData['customer_name'],
                'customer_phone' => $paymentData['phone'],
                'client_reference' => $paymentData['reference']
            ],
            'store' => [
                'name' => 'TESCON Ghana',
                'phone' => '0244123456',
                'postal_address' => 'TESCON Ghana Office',
                'logo_url' => 'https://example.com/logo.png'
            ],
            'actions' => [
                'cancel_url' => $this->callbackUrl . '?status=cancelled&reference=' . $paymentData['reference'],
                'return_url' => $this->callbackUrl . '?status=success&reference=' . $paymentData['reference']
            ]
        ];

        $headers = [
            'Authorization: Bearer ' . $accessToken,
            'Content-Type: application/json'
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 201) {
            $data = json_decode($response, true);
            return [
                'success' => true,
                'checkout_url' => $data['response']['checkout_url'],
                'invoice_token' => $data['response']['invoice_token']
            ];
        }

        return ['success' => false, 'error' => 'Payment initiation failed', 'response' => $response];
    }

    /**
     * Check payment status
     * @param string $invoiceToken
     */
    public function checkPaymentStatus($invoiceToken) {
        $accessToken = $this->getAccessToken();
        if (!$accessToken) {
            return ['success' => false, 'error' => 'Failed to get access token'];
        }

        $url = $this->apiUrl . '/onlinecheckout/invoice/status/' . $invoiceToken;

        $headers = [
            'Authorization: Bearer ' . $accessToken,
            'Content-Type: application/json'
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200) {
            $data = json_decode($response, true);
            return [
                'success' => true,
                'status' => $data['status'],
                'amount' => $data['amount'],
                'transaction_id' => $data['transaction_id'] ?? null
            ];
        }

        return ['success' => false, 'error' => 'Status check failed'];
    }

    /**
     * Process payment callback/webhook
     * @param array $callbackData
     */
    public function processCallback($callbackData) {
        // Verify callback data
        if (!isset($callbackData['reference']) || !isset($callbackData['status'])) {
            return ['success' => false, 'error' => 'Invalid callback data'];
        }

        $reference = $callbackData['reference'];
        $status = $callbackData['status'];

        // Update payment status in database based on callback
        // This would be called from payment_callback.php

        return [
            'success' => true,
            'reference' => $reference,
            'status' => $status,
            'data' => $callbackData
        ];
    }
}
?>
