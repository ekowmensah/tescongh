<?php

interface SMSClient
{
    /**
     * Send a single SMS message
     *
     * @param string $phone Recipient phone number (local format for mNotify: 0XXXXXXXXX)
     * @param string $message Message content
     * @return array ['success' => bool, 'status' => string|null, 'message_id' => string|null, 'error' => string|null, 'raw' => mixed]
     */
    public function send(string $phone, string $message): array;
}

class MnotifySMSClient implements SMSClient
{
    private $service;

    public function __construct()
    {
        $this->service = new MnotifySMSService();
    }

    public function send(string $phone, string $message): array
    {
        $result = $this->service->sendQuickSMS([$phone], $message);
        return [
            'success'    => $result['success'],
            'status'     => $result['status'] ?? null,
            'message_id' => $result['campaign_id'] ?? null,
            'error'      => $result['error'] ?? null,
            'raw'        => $result['raw'] ?? null,
        ];
    }
}

class HubtelSMSClient implements SMSClient
{
    private $hubtel;

    public function __construct()
    {
        require_once __DIR__ . '/HubtelSMS.php';
        
        // Get credentials from config
        $clientId = defined('HUBTEL_CLIENT_ID') ? HUBTEL_CLIENT_ID : '';
        $clientSecret = defined('HUBTEL_CLIENT_SECRET') ? HUBTEL_CLIENT_SECRET : '';
        $senderId = defined('SMS_SENDER_ID') ? SMS_SENDER_ID : 'TESCON-GH';
        
        if (empty($clientId) || empty($clientSecret)) {
            throw new Exception('Hubtel SMS credentials not configured. Please set HUBTEL_CLIENT_ID and HUBTEL_CLIENT_SECRET in config.php');
        }
        
        $this->hubtel = new HubtelSMS($clientId, $clientSecret, $senderId);
    }

    public function send(string $phone, string $message): array
    {
        try {
            // Use POST method for sending (recommended by Hubtel)
            $result = $this->hubtel->sendSimplePOST($phone, $message);
            
            return [
                'success'    => $result['success'],
                'status'     => $result['status'],
                'message_id' => $result['data']['messageId'] ?? null,
                'error'      => $result['error'],
                'raw'        => $result['data'],
            ];
        } catch (Exception $e) {
            return [
                'success'    => false,
                'status'     => null,
                'message_id' => null,
                'error'      => 'Exception: ' . $e->getMessage(),
                'raw'        => null,
            ];
        }
    }
}

class SMSClientFactory
{
    public static function create(string $provider): SMSClient
    {
        $provider = strtolower($provider);

        if ($provider === 'hubtel') {
            return new HubtelSMSClient();
        }

        // Default to mNotify
        return new MnotifySMSClient();
    }
}
