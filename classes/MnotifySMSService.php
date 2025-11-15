<?php

/**
 * MnotifySMSService
 * Wrapper around mNotify BMS SMS Quick endpoint
 */
class MnotifySMSService
{
    private $apiKey;
    private $senderId;
    private $baseUrl;

    public function __construct()
    {
        // Ensure config is loaded
        if (!defined('MNOTIFY_API_KEY') || !defined('MNOTIFY_SENDER_ID') || !defined('MNOTIFY_API_BASE_URL')) {
            // Allow the caller to handle this via return error instead of fatal
            throw new Exception('mNotify configuration constants are not defined. Include config/mnotify.php first.');
        }

        $this->apiKey   = MNOTIFY_API_KEY;
        $this->senderId = MNOTIFY_SENDER_ID;
        $this->baseUrl  = rtrim(MNOTIFY_API_BASE_URL, '/');
    }

    /**
     * Send quick SMS to one or more recipients
     *
     * @param array $recipients Array of phone numbers as strings
     * @param string $message SMS content
     * @param bool $isSchedule Whether to schedule the SMS
     * @param string|null $scheduleDate Datetime in 'Y-m-d H:i' when scheduling
     * @return array ['success' => bool, 'status' => string|null, 'campaign_id' => string|null, 'error' => string|null, 'raw' => mixed]
     */
    public function sendQuickSMS(array $recipients, $message, $isSchedule = false, $scheduleDate = null)
    {
        if (empty($recipients)) {
            return [
                'success' => false,
                'status'  => null,
                'campaign_id' => null,
                'error'   => 'No recipients provided',
                'raw'     => null,
            ];
        }

        $url = $this->baseUrl . '/sms/quick?key=' . urlencode($this->apiKey);

        $payload = [
            'recipient'     => array_values($recipients),
            'sender'        => $this->senderId,
            'message'       => $message,
            'is_schedule'   => (bool)$isSchedule,
            'schedule_date' => $isSchedule && $scheduleDate ? $scheduleDate : '',
            // Do NOT include sms_type by default to avoid OTP billing behaviour
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            return [
                'success' => false,
                'status'  => null,
                'campaign_id' => null,
                'error'   => 'cURL error: ' . $curlError,
                'raw'     => null,
            ];
        }

        $data = json_decode($response, true);

        if ($httpCode === 200 && is_array($data) && isset($data['status']) && $data['status'] === 'success') {
            return [
                'success' => true,
                'status'  => $data['status'],
                'campaign_id' => isset($data['summary']['_id']) ? $data['summary']['_id'] : null,
                'error'   => null,
                'raw'     => $data,
            ];
        }

        $errorMessage = 'Unknown error';
        if (is_array($data) && isset($data['message'])) {
            $errorMessage = $data['message'];
        } elseif (!empty($response)) {
            $errorMessage = 'HTTP ' . $httpCode . ' - ' . $response;
        }

        return [
            'success' => false,
            'status'  => isset($data['status']) ? $data['status'] : null,
            'campaign_id' => isset($data['summary']['_id']) ? $data['summary']['_id'] : null,
            'error'   => $errorMessage,
            'raw'     => $data,
        ];
    }
}
