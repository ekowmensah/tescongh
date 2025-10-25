<?php
/**
 * SMS Notification Utilities
 * Functions for sending automated SMS notifications
 */

require_once 'config/database.php';
require_once 'includes/SMSService.php';

class SMSNotifications {
    private $smsService;
    private $pdo;

    public function __construct() {
        $this->smsService = new SMSService();
        global $pdo;
        $this->pdo = $pdo;
    }

    /**
     * Send payment confirmation SMS
     * @param int $memberId Member ID
     * @param int $paymentId Payment ID
     * @return array Result of SMS sending
     */
    public function sendPaymentConfirmation($memberId, $paymentId) {
        try {
            // Get payment and member details
            $stmt = $this->pdo->prepare("
                SELECT p.*, d.year, d.amount, m.fullname, m.phone
                FROM payments p
                JOIN dues d ON p.dues_id = d.id
                JOIN members m ON p.member_id = m.id
                WHERE p.id = ? AND p.member_id = ?
            ");
            $stmt->execute([$paymentId, $memberId]);
            $payment = $stmt->fetch();

            if (!$payment || empty($payment['phone'])) {
                return ['success' => false, 'error' => 'Payment or phone number not found'];
            }

            // Get payment confirmation template
            $stmt = $this->pdo->prepare("SELECT content FROM sms_templates WHERE name = 'Payment Confirmation' LIMIT 1");
            $stmt->execute();
            $template = $stmt->fetch();

            if ($template) {
                $message = str_replace(
                    ['{year}', '{amount}'],
                    [$payment['year'], number_format($payment['amount'], 2)],
                    $template['content']
                );
            } else {
                $message = "Thank you for paying your TESCON membership dues for {$payment['year']}. Your payment of GH₵" . number_format($payment['amount'], 2) . " has been received successfully.";
            }

            return $this->smsService->sendSMS($payment['phone'], $message);

        } catch (PDOException $e) {
            return ['success' => false, 'error' => 'Database error: ' . $e->getMessage()];
        }
    }

    /**
     * Send dues reminder SMS to unpaid members
     * @param int $year Year for dues reminder (default: current year)
     * @return array Results of bulk SMS sending
     */
    public function sendDuesReminders($year = null) {
        if (!$year) {
            $year = date('Y');
        }

        try {
            // Get dues information
            $stmt = $this->pdo->prepare("SELECT * FROM dues WHERE year = ? LIMIT 1");
            $stmt->execute([$year]);
            $dues = $stmt->fetch();

            if (!$dues) {
                return ['success' => false, 'error' => 'No dues found for year ' . $year];
            }

            // Get unpaid members with phone numbers
            $stmt = $this->pdo->prepare("
                SELECT m.id, m.fullname, m.phone
                FROM members m
                LEFT JOIN payments p ON m.id = p.member_id
                    AND p.status = 'completed'
                    AND p.dues_id = ?
                WHERE m.phone IS NOT NULL
                    AND m.phone != ''
                    AND p.id IS NULL
            ");
            $stmt->execute([$dues['id']]);
            $unpaidMembers = $stmt->fetchAll();

            if (empty($unpaidMembers)) {
                return ['success' => true, 'message' => 'No unpaid members found', 'count' => 0];
            }

            // Get dues reminder template
            $stmt = $this->pdo->prepare("SELECT content FROM sms_templates WHERE name = 'Dues Reminder' LIMIT 1");
            $stmt->execute();
            $template = $stmt->fetch();

            if ($template) {
                $message = str_replace(
                    ['{year}', '{amount}'],
                    [$year, number_format($dues['amount'], 2)],
                    $template['content']
                );
            } else {
                $message = "Dear TESCON member, your annual membership dues for {$year} (GH₵" . number_format($dues['amount'], 2) . ") are due. Pay now to avoid penalties. Visit our portal to pay.";
            }

            // Send to all unpaid members
            $phoneNumbers = array_column($unpaidMembers, 'phone');
            $results = $this->smsService->sendBulkSMS($phoneNumbers, $message);

            return [
                'success' => true,
                'message' => 'Dues reminders sent',
                'count' => count($phoneNumbers),
                'results' => $results
            ];

        } catch (PDOException $e) {
            return ['success' => false, 'error' => 'Database error: ' . $e->getMessage()];
        }
    }

    /**
     * Send welcome SMS to new members
     * @param int $memberId Member ID
     * @return array Result of SMS sending
     */
    public function sendWelcomeMessage($memberId) {
        try {
            // Get member details
            $stmt = $this->pdo->prepare("SELECT fullname, phone FROM members WHERE id = ?");
            $stmt->execute([$memberId]);
            $member = $stmt->fetch();

            if (!$member || empty($member['phone'])) {
                return ['success' => false, 'error' => 'Member or phone number not found'];
            }

            // Get welcome template
            $stmt = $this->pdo->prepare("SELECT content FROM sms_templates WHERE name = 'Welcome Message' LIMIT 1");
            $stmt->execute();
            $template = $stmt->fetch();

            $message = $template ? $template['content'] :
                "Welcome to TESCON Ghana! Complete your registration and pay your dues to access all member benefits. Visit our portal for more information.";

            return $this->smsService->sendSMS($member['phone'], $message);

        } catch (PDOException $e) {
            return ['success' => false, 'error' => 'Database error: ' . $e->getMessage()];
        }
    }

    /**
     * Send event notification SMS
     * @param int $eventId Event ID
     * @param array $recipientTypes Array of recipient types ('all', 'paid', 'unpaid', 'executives')
     * @return array Results of bulk SMS sending
     */
    public function sendEventNotification($eventId, $recipientTypes = ['all']) {
        try {
            // Get event details
            $stmt = $this->pdo->prepare("SELECT * FROM events WHERE id = ?");
            $stmt->execute([$eventId]);
            $event = $stmt->fetch();

            if (!$event) {
                return ['success' => false, 'error' => 'Event not found'];
            }

            // Get recipients based on types
            $phoneNumbers = [];

            foreach ($recipientTypes as $type) {
                $phones = [];

                switch ($type) {
                    case 'all':
                        $stmt = $this->pdo->query("SELECT phone FROM members WHERE phone IS NOT NULL AND phone != ''");
                        $phones = $stmt->fetchAll(PDO::FETCH_COLUMN);
                        break;

                    case 'paid':
                        $currentYear = date('Y');
                        $stmt = $this->pdo->prepare("
                            SELECT DISTINCT m.phone FROM members m
                            JOIN payments p ON m.id = p.member_id
                            WHERE p.status = 'completed'
                            AND p.dues_id = (SELECT id FROM dues WHERE year = ? LIMIT 1)
                            AND m.phone IS NOT NULL AND m.phone != ''
                        ");
                        $stmt->execute([$currentYear]);
                        $phones = $stmt->fetchAll(PDO::FETCH_COLUMN);
                        break;

                    case 'unpaid':
                        $currentYear = date('Y');
                        $stmt = $this->pdo->prepare("
                            SELECT m.phone FROM members m
                            LEFT JOIN payments p ON m.id = p.member_id
                                AND p.status = 'completed'
                                AND p.dues_id = (SELECT id FROM dues WHERE year = ? LIMIT 1)
                            WHERE m.phone IS NOT NULL AND m.phone != ''
                            AND p.id IS NULL
                        ");
                        $stmt->execute([$currentYear]);
                        $phones = $stmt->fetchAll(PDO::FETCH_COLUMN);
                        break;

                    case 'executives':
                        $stmt = $this->pdo->query("
                            SELECT DISTINCT m.phone FROM members m
                            JOIN campus_executives ce ON m.id = ce.member_id
                            WHERE m.phone IS NOT NULL AND m.phone != ''
                        ");
                        $phones = $stmt->fetchAll(PDO::FETCH_COLUMN);
                        break;
                }

                $phoneNumbers = array_merge($phoneNumbers, $phones);
            }

            $phoneNumbers = array_unique($phoneNumbers);

            if (empty($phoneNumbers)) {
                return ['success' => false, 'error' => 'No recipients found'];
            }

            // Get event notification template
            $stmt = $this->pdo->prepare("SELECT content FROM sms_templates WHERE name = 'Event Notification' LIMIT 1");
            $stmt->execute();
            $template = $stmt->fetch();

            if ($template) {
                $message = str_replace(
                    ['{event_name}', '{event_date}', '{event_location}'],
                    [$event['title'], date('d/m/Y', strtotime($event['event_date'])), $event['location']],
                    $template['content']
                );
            } else {
                $message = "TESCON Event: {$event['title']} on " . date('d/m/Y', strtotime($event['event_date'])) . " at {$event['location']}. All members are encouraged to attend.";
            }

            $results = $this->smsService->sendBulkSMS($phoneNumbers, $message);

            return [
                'success' => true,
                'message' => 'Event notifications sent',
                'count' => count($phoneNumbers),
                'results' => $results
            ];

        } catch (PDOException $e) {
            return ['success' => false, 'error' => 'Database error: ' . $e->getMessage()];
        }
    }

    /**
     * Send executive appointment notification
     * @param int $executiveId Campus executive ID
     * @return array Result of SMS sending
     */
    public function sendExecutiveAppointment($executiveId) {
        try {
            // Get executive details with campus info
            $stmt = $this->pdo->prepare("
                SELECT ce.*, m.fullname, m.phone, c.name as campus_name, i.name as institution
                FROM campus_executives ce
                JOIN members m ON ce.member_id = m.id
                JOIN campuses c ON ce.campus_id = c.id
                JOIN institutions i ON c.institution_id = i.id
                WHERE ce.id = ?
            ");
            $stmt->execute([$executiveId]);
            $executive = $stmt->fetch();

            if (!$executive || empty($executive['phone'])) {
                return ['success' => false, 'error' => 'Executive or phone number not found'];
            }

            // Get executive appointment template
            $stmt = $this->pdo->prepare("SELECT content FROM sms_templates WHERE name = 'Executive Appointment' LIMIT 1");
            $stmt->execute();
            $template = $stmt->fetch();

            if ($template) {
                $message = str_replace(
                    ['{position}', '{campus_name}'],
                    [$executive['position'], $executive['institution'] . ' - ' . $executive['campus_name']],
                    $template['content']
                );
            } else {
                $message = "Congratulations! You have been appointed as {$executive['position']} for {$executive['institution']} - {$executive['campus_name']}. Contact the national secretariat for your responsibilities.";
            }

            return $this->smsService->sendSMS($executive['phone'], $message);

        } catch (PDOException $e) {
            return ['success' => false, 'error' => 'Database error: ' . $e->getMessage()];
        }
    }
}

// Example usage functions that can be called from various parts of the application

/**
 * Send payment confirmation SMS (call this after successful payment)
 */
function sendPaymentConfirmationSMS($memberId, $paymentId) {
    $notifications = new SMSNotifications();
    return $notifications->sendPaymentConfirmation($memberId, $paymentId);
}

/**
 * Send welcome SMS to new member (call this after registration)
 */
function sendWelcomeSMS($memberId) {
    $notifications = new SMSNotifications();
    return $notifications->sendWelcomeMessage($memberId);
}

/**
 * Send monthly dues reminders (can be called by cron job)
 */
function sendMonthlyDuesReminders($year = null) {
    $notifications = new SMSNotifications();
    return $notifications->sendDuesReminders($year);
}

/**
 * Send event notification SMS
 */
function sendEventNotificationSMS($eventId, $recipientTypes = ['all']) {
    $notifications = new SMSNotifications();
    return $notifications->sendEventNotification($eventId, $recipientTypes);
}

/**
 * Send executive appointment SMS
 */
function sendExecutiveAppointmentSMS($executiveId) {
    $notifications = new SMSNotifications();
    return $notifications->sendExecutiveAppointment($executiveId);
}
?>
