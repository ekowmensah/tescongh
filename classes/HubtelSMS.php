<?php

/**
 * Hubtel SMS API Integration
 * 
 * This class provides comprehensive integration with the Hubtel SMS API
 * supporting all SMS functionalities including:
 * - Simple messaging (GET and POST)
 * - Batch simple messaging
 * - Batch personalized messaging
 * - Message status checking (by messageId and batchId)
 * 
 * @see https://developers.hubtel.com/documentations/sms-api
 */
class HubtelSMS
{
    private $clientId;
    private $clientSecret;
    private $senderId;
    private $baseUrl = 'https://sms.hubtel.com/v1';
    
    /**
     * Constructor
     * 
     * @param string $clientId Hubtel Client ID
     * @param string $clientSecret Hubtel Client Secret
     * @param string $senderId Sender ID (max 11 characters)
     */
    public function __construct($clientId, $clientSecret, $senderId = 'TESCON-GH')
    {
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
        $this->senderId = substr($senderId, 0, 11); // Enforce 11 character limit
    }
    
    /**
     * Send a simple SMS using GET method
     * 
     * @param string $to Recipient phone number (e.g., 0244123456 or 233244123456)
     * @param string $content Message content
     * @param string|null $from Optional sender ID (defaults to class senderId)
     * @return array Response array with keys: success, status, data, error
     */
    public function sendSimpleGET($to, $content, $from = null)
    {
        $from = $from ?: $this->senderId;
        $to = $this->formatPhoneNumber($to);
        
        $params = [
            'clientid' => $this->clientId,
            'clientsecret' => $this->clientSecret,
            'from' => substr($from, 0, 11),
            'to' => $to,
            'content' => $content
        ];
        
        $url = $this->baseUrl . '/messages/send?' . http_build_query($params);
        
        return $this->makeRequest('GET', $url);
    }
    
    /**
     * Send a simple SMS using POST method (recommended)
     * 
     * @param string $to Recipient phone number
     * @param string $content Message content
     * @param string|null $from Optional sender ID
     * @param string|null $clientReference Optional client reference
     * @return array Response array
     */
    public function sendSimplePOST($to, $content, $from = null, $clientReference = null)
    {
        $from = $from ?: $this->senderId;
        $to = $this->formatPhoneNumber($to);
        
        $data = [
            'From' => substr($from, 0, 11),
            'To' => $to,
            'Content' => $content
        ];
        
        if ($clientReference) {
            $data['ClientReference'] = $clientReference;
        }
        
        $url = $this->baseUrl . '/messages/send';
        
        return $this->makeRequest('POST', $url, $data, true);
    }
    
    /**
     * Send batch simple messages to multiple recipients
     * 
     * @param array $recipients Array of phone numbers
     * @param string $content Message content (same for all recipients)
     * @param string|null $from Optional sender ID
     * @return array Response array with batchId and individual message details
     */
    public function sendBatchSimple(array $recipients, $content, $from = null)
    {
        $from = $from ?: $this->senderId;
        
        // Format all phone numbers
        $formattedRecipients = array_map([$this, 'formatPhoneNumber'], $recipients);
        
        $data = [
            'From' => substr($from, 0, 11),
            'Recipients' => $formattedRecipients,
            'Content' => $content
        ];
        
        $url = $this->baseUrl . '/messages/batch/simple/send';
        
        return $this->makeRequest('POST', $url, $data, true);
    }
    
    /**
     * Send batch personalized messages
     * 
     * @param array $personalizedRecipients Array of arrays with 'To' and 'Content' keys
     *                                       Example: [['To' => '0244123456', 'Content' => 'Hi John'], ...]
     * @param string|null $from Optional sender ID
     * @return array Response array with batchId and individual message details
     */
    public function sendBatchPersonalized(array $personalizedRecipients, $from = null)
    {
        $from = $from ?: $this->senderId;
        
        // Format phone numbers in personalized recipients
        $formatted = [];
        foreach ($personalizedRecipients as $recipient) {
            $formatted[] = [
                'To' => $this->formatPhoneNumber($recipient['To']),
                'Content' => $recipient['Content']
            ];
        }
        
        $data = [
            'From' => substr($from, 0, 11),
            'personalizedRecipients' => $formatted
        ];
        
        $url = $this->baseUrl . '/messages/batch/personalized/send';
        
        return $this->makeRequest('POST', $url, $data, true);
    }
    
    /**
     * Check message status by messageId
     * 
     * @param string $messageId The unique message ID
     * @return array Response array with message status details
     */
    public function getMessageStatus($messageId)
    {
        $url = $this->baseUrl . '/messages/' . urlencode($messageId);
        
        return $this->makeRequest('GET', $url, null, true);
    }
    
    /**
     * Check batch status by batchId
     * 
     * @param string $batchId The batch ID
     * @return array Response array with batch status details
     */
    public function getBatchStatus($batchId)
    {
        $url = $this->baseUrl . '/messages/batch/' . urlencode($batchId);
        
        return $this->makeRequest('GET', $url, null, true);
    }
    
    /**
     * Format phone number to Hubtel's expected format
     * Converts various formats to 233XXXXXXXXX
     * 
     * @param string $phone Phone number in various formats
     * @return string Formatted phone number
     */
    private function formatPhoneNumber($phone)
    {
        // Remove all non-numeric characters
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        // If starts with 0, replace with 233
        if (substr($phone, 0, 1) === '0') {
            $phone = '233' . substr($phone, 1);
        }
        
        // If doesn't start with 233, add it
        if (substr($phone, 0, 3) !== '233') {
            $phone = '233' . $phone;
        }
        
        return $phone;
    }
    
    /**
     * Make HTTP request to Hubtel API
     * 
     * @param string $method HTTP method (GET or POST)
     * @param string $url Full URL
     * @param array|null $data Request data for POST
     * @param bool $useBasicAuth Whether to use Basic Authentication
     * @return array Standardized response array
     */
    private function makeRequest($method, $url, $data = null, $useBasicAuth = false)
    {
        $ch = curl_init();
        
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        // Set headers
        $headers = ['Content-Type: application/json'];
        
        if ($useBasicAuth) {
            $auth = base64_encode($this->clientId . ':' . $this->clientSecret);
            $headers[] = 'Authorization: Basic ' . $auth;
        }
        
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
                'status' => null,
                'data' => null,
                'error' => 'cURL Error: ' . $error,
                'http_code' => $httpCode,
                'raw_response' => $response
            ];
        }
        
        // Parse JSON response
        $responseData = json_decode($response, true);
        
        // Determine success based on HTTP code
        $success = in_array($httpCode, [200, 201]);
        
        // Extract status from response
        $status = null;
        if (isset($responseData['status'])) {
            $status = $responseData['status'];
        } elseif (isset($responseData['statusDescription'])) {
            $status = $responseData['statusDescription'];
        }
        
        // Build standardized response
        return [
            'success' => $success,
            'status' => $status,
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
            if (isset($responseData['statusDescription'])) {
                return $responseData['statusDescription'];
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
            403 => 'Forbidden - Recipient has not given approval',
            404 => 'Not Found - Message not found',
            500 => 'Internal Server Error',
            502 => 'Bad Gateway'
        ];
        
        return $errorMessages[$httpCode] ?? 'Unknown error occurred (HTTP ' . $httpCode . ')';
    }
    
    /**
     * Get SMS status description
     * 
     * @param string $status Status from API
     * @return string Human-readable status description
     */
    public static function getStatusDescription($status)
    {
        $descriptions = [
            'Delivered' => 'Message successfully delivered to recipient',
            'Sent' => 'Message sent to network operator, pending delivery',
            'Pending' => 'Message queued, awaiting dispatch',
            'Blacklisted' => 'Recipient has opted out of bulk messages',
            'Undeliverable' => 'Message could not be delivered',
            'Failed' => 'Message delivery failed',
            'Unrouteable' => 'Message could not be routed',
            'Error' => 'An error occurred',
            'Rejected' => 'Message was rejected',
            'NACK/0x0000000b/Invalid Destination Address' => 'Invalid recipient phone number',
            'NACK/0x0000000a/Invalid Source Address' => 'Invalid sender ID (max 11 characters)'
        ];
        
        return $descriptions[$status] ?? $status;
    }
    
    /**
     * Validate phone number format
     * 
     * @param string $phone Phone number to validate
     * @return bool True if valid
     */
    public static function validatePhoneNumber($phone)
    {
        // Remove non-numeric characters
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        // Check if it's a valid Ghanaian number
        // Should be 10 digits starting with 0, or 12 digits starting with 233
        if (preg_match('/^0[0-9]{9}$/', $phone)) {
            return true;
        }
        if (preg_match('/^233[0-9]{9}$/', $phone)) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Validate sender ID
     * 
     * @param string $senderId Sender ID to validate
     * @return bool True if valid
     */
    public static function validateSenderId($senderId)
    {
        // Max 11 characters, alphanumeric
        return strlen($senderId) <= 11 && preg_match('/^[a-zA-Z0-9]+$/', $senderId);
    }
    
    /**
     * Calculate SMS cost estimate
     * Note: Actual cost depends on your Hubtel pricing plan
     * 
     * @param string $message Message content
     * @param int $recipientCount Number of recipients
     * @return array Array with message_count and estimated_cost
     */
    public static function estimateCost($message, $recipientCount = 1)
    {
        $messageLength = strlen($message);
        
        // Standard SMS is 160 characters
        // Messages longer than 160 are split into multiple SMS
        $messageCount = ceil($messageLength / 160);
        
        // Estimated cost per SMS (adjust based on your plan)
        $costPerSMS = 0.03; // GHS 0.03 per SMS (example rate)
        
        $totalMessages = $messageCount * $recipientCount;
        $estimatedCost = $totalMessages * $costPerSMS;
        
        return [
            'message_length' => $messageLength,
            'messages_per_recipient' => $messageCount,
            'total_recipients' => $recipientCount,
            'total_messages' => $totalMessages,
            'estimated_cost' => $estimatedCost,
            'currency' => 'GHS'
        ];
    }
}
