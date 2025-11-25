<?php

/**
 * Hubtel Online Checkout API Integration
 * 
 * This class provides integration with Hubtel's Online Checkout API
 * for accepting online payments via:
 * - Mobile Money
 * - Bank Card
 * - Wallet (Hubtel, G-Money, Zeepay)
 * - GhQR
 * 
 * @see https://developers.hubtel.com/documentations/online-checkout
 */
class HubtelCheckout
{
    private $clientId;
    private $clientSecret;
    private $merchantAccountNumber;
    private $baseUrl = 'https://payproxyapi.hubtel.com';
    private $statusCheckUrl = 'https://api-txnstatus.hubtel.com';
    
    /**
     * Constructor
     * 
     * @param string $clientId Hubtel Client ID
     * @param string $clientSecret Hubtel Client Secret
     * @param string $merchantAccountNumber POS Sales ID
     */
    public function __construct($clientId, $clientSecret, $merchantAccountNumber)
    {
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
        $this->merchantAccountNumber = $merchantAccountNumber;
    }
    
    /**
     * Initiate online checkout
     * 
     * @param float $totalAmount Total amount to be paid
     * @param string $description Brief description of the purchase
     * @param string $callbackUrl URL to receive payment status
     * @param string $returnUrl URL to redirect customer after payment
     * @param string $clientReference Unique transaction reference
     * @param array $optional Optional parameters (payeeName, payeeMobileNumber, payeeEmail, cancellationUrl)
     * @return array Response array with keys: success, data, error, http_code
     */
    public function initiateCheckout($totalAmount, $description, $callbackUrl, $returnUrl, $clientReference, $optional = [])
    {
        // Validate required parameters
        if (empty($totalAmount) || $totalAmount <= 0) {
            return [
                'success' => false,
                'data' => null,
                'error' => 'Total amount must be greater than 0',
                'http_code' => 400
            ];
        }
        
        if (strlen($clientReference) > 32) {
            return [
                'success' => false,
                'data' => null,
                'error' => 'Client reference must not exceed 32 characters',
                'http_code' => 400
            ];
        }
        
        // Build request payload
        $payload = [
            'totalAmount' => round($totalAmount, 2),
            'description' => $description,
            'callbackUrl' => $callbackUrl,
            'returnUrl' => $returnUrl,
            'merchantAccountNumber' => $this->merchantAccountNumber,
            'cancellationUrl' => isset($optional['cancellationUrl']) ? $optional['cancellationUrl'] : $returnUrl,
            'clientReference' => $clientReference
        ];
        
        // Add optional parameters
        if (isset($optional['payeeName'])) {
            $payload['payeeName'] = $optional['payeeName'];
        }
        if (isset($optional['payeeMobileNumber'])) {
            $payload['payeeMobileNumber'] = $optional['payeeMobileNumber'];
        }
        if (isset($optional['payeeEmail'])) {
            $payload['payeeEmail'] = $optional['payeeEmail'];
        }
        
        $url = $this->baseUrl . '/items/initiate';
        
        return $this->makeRequest('POST', $url, $payload);
    }
    
    /**
     * Check transaction status
     * 
     * @param string $clientReference The client reference of the transaction
     * @param string|null $hubtelTransactionId Optional Hubtel transaction ID
     * @param string|null $networkTransactionId Optional network transaction ID
     * @return array Response array
     */
    public function checkTransactionStatus($clientReference, $hubtelTransactionId = null, $networkTransactionId = null)
    {
        $params = [];
        
        if (!empty($clientReference)) {
            $params['clientReference'] = $clientReference;
        }
        if (!empty($hubtelTransactionId)) {
            $params['hubtelTransactionId'] = $hubtelTransactionId;
        }
        if (!empty($networkTransactionId)) {
            $params['networkTransactionId'] = $networkTransactionId;
        }
        
        if (empty($params)) {
            return [
                'success' => false,
                'data' => null,
                'error' => 'At least one transaction identifier is required',
                'http_code' => 400
            ];
        }
        
        $url = $this->statusCheckUrl . '/transactions/' . $this->merchantAccountNumber . '/status?' . http_build_query($params);
        
        return $this->makeRequest('GET', $url);
    }
    
    /**
     * Make HTTP request to Hubtel API
     * 
     * @param string $method HTTP method (GET or POST)
     * @param string $url Full URL
     * @param array|null $data Request data for POST
     * @return array Standardized response array
     */
    private function makeRequest($method, $url, $data = null)
    {
        $ch = curl_init();
        
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        // Set headers with Basic Authentication
        $auth = base64_encode($this->clientId . ':' . $this->clientSecret);
        $headers = [
            'Authorization: Basic ' . $auth,
            'Content-Type: application/json',
            'Accept: application/json',
            'Cache-Control: no-cache'
        ];
        
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        
        // Set method and data
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if ($data) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            }
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        
        curl_close($ch);
        
        // Handle curl errors
        if ($error) {
            return [
                'success' => false,
                'data' => null,
                'error' => 'cURL Error: ' . $error,
                'http_code' => $httpCode,
                'raw_response' => $response
            ];
        }
        
        // Parse JSON response
        $responseData = json_decode($response, true);
        
        // Determine success based on HTTP code and response
        $success = in_array($httpCode, [200, 201]);
        
        // Check for Hubtel-specific success indicators
        if ($success && isset($responseData['responseCode'])) {
            $success = ($responseData['responseCode'] === '0000');
        }
        
        // Build standardized response
        return [
            'success' => $success,
            'data' => $responseData,
            'error' => $success ? null : $this->getErrorMessage($httpCode, $responseData),
            'http_code' => $httpCode,
            'raw_response' => $response
        ];
    }
    
    /**
     * Get error message from response
     * 
     * @param int $httpCode HTTP status code
     * @param array|null $responseData Response data
     * @return string Error message
     */
    private function getErrorMessage($httpCode, $responseData)
    {
        // Check for error in response data
        if (is_array($responseData)) {
            if (isset($responseData['message'])) {
                return $responseData['message'];
            }
            if (isset($responseData['data']['message'])) {
                return $responseData['data']['message'];
            }
            if (isset($responseData['error'])) {
                return $responseData['error'];
            }
        }
        
        // Default error messages based on HTTP code
        $errorMessages = [
            400 => 'Bad Request - Invalid parameters',
            401 => 'Unauthorized - Invalid credentials',
            402 => 'Payment Required - Insufficient credit',
            403 => 'Forbidden - Access denied or IP not whitelisted',
            404 => 'Not Found - Resource not found',
            500 => 'Internal Server Error',
            502 => 'Bad Gateway',
            504 => 'Gateway Timeout'
        ];
        
        return $errorMessages[$httpCode] ?? 'Unknown error occurred (HTTP ' . $httpCode . ')';
    }
    
    /**
     * Parse callback data from Hubtel
     * 
     * @param string $jsonData JSON string from callback
     * @return array|null Parsed callback data or null if invalid
     */
    public static function parseCallback($jsonData)
    {
        $data = json_decode($jsonData, true);
        
        if (!$data || !isset($data['Data'])) {
            return null;
        }
        
        return [
            'responseCode' => $data['ResponseCode'] ?? null,
            'status' => $data['Status'] ?? null,
            'checkoutId' => $data['Data']['CheckoutId'] ?? null,
            'salesInvoiceId' => $data['Data']['SalesInvoiceId'] ?? null,
            'clientReference' => $data['Data']['ClientReference'] ?? null,
            'transactionStatus' => $data['Data']['Status'] ?? null,
            'amount' => $data['Data']['Amount'] ?? null,
            'customerPhoneNumber' => $data['Data']['CustomerPhoneNumber'] ?? null,
            'paymentType' => $data['Data']['PaymentDetails']['PaymentType'] ?? null,
            'channel' => $data['Data']['PaymentDetails']['Channel'] ?? null,
            'mobileMoneyNumber' => $data['Data']['PaymentDetails']['MobileMoneyNumber'] ?? null,
            'description' => $data['Data']['Description'] ?? null
        ];
    }
    
    /**
     * Get status description
     * 
     * @param string $status Status from API
     * @return string Human-readable status description
     */
    public static function getStatusDescription($status)
    {
        $descriptions = [
            'Success' => 'Payment completed successfully',
            'Paid' => 'Payment has been processed and confirmed',
            'Unpaid' => 'Payment has not been completed',
            'Failed' => 'Payment failed',
            'Pending' => 'Payment is being processed',
            'Refunded' => 'Payment has been refunded'
        ];
        
        return $descriptions[$status] ?? $status;
    }
}
