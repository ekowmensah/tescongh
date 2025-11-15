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
    public function send(string $phone, string $message): array
    {
        // Placeholder: current implementation only logs as sent.
        // Real Hubtel integration can be wired here later.
        return [
            'success'    => true,
            'status'     => 'sent',
            'message_id' => null,
            'error'      => null,
            'raw'        => null,
        ];
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
