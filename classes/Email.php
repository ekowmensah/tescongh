<?php

/**
 * Email Class - SMTP Email Sending
 * 
 * Simple SMTP email class using PHP's built-in mail() function
 * or PHPMailer if available for better reliability
 */
class Email
{
    private $smtpHost;
    private $smtpPort;
    private $smtpUsername;
    private $smtpPassword;
    private $smtpFromEmail;
    private $smtpFromName;
    private $smtpEncryption;
    
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->smtpHost = defined('SMTP_HOST') ? SMTP_HOST : 'localhost';
        $this->smtpPort = defined('SMTP_PORT') ? SMTP_PORT : 587;
        $this->smtpUsername = defined('SMTP_USERNAME') ? SMTP_USERNAME : '';
        $this->smtpPassword = defined('SMTP_PASSWORD') ? SMTP_PASSWORD : '';
        $this->smtpFromEmail = defined('SMTP_FROM_EMAIL') ? SMTP_FROM_EMAIL : 'noreply@tesconghana.org';
        $this->smtpFromName = defined('SMTP_FROM_NAME') ? SMTP_FROM_NAME : 'TESCON Ghana';
        $this->smtpEncryption = defined('SMTP_ENCRYPTION') ? SMTP_ENCRYPTION : 'tls';
    }
    
    /**
     * Send email using SMTP
     * 
     * @param string $to Recipient email address
     * @param string $subject Email subject
     * @param string $body Email body (HTML)
     * @param string|null $altBody Plain text alternative body
     * @return array Result with success status and message
     */
    public function send($to, $subject, $body, $altBody = null)
    {
        // Check if PHPMailer is available
        if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
            return $this->sendWithPHPMailer($to, $subject, $body, $altBody);
        } else {
            return $this->sendWithMailFunction($to, $subject, $body);
        }
    }
    
    /**
     * Send email using PHPMailer library
     */
    private function sendWithPHPMailer($to, $subject, $body, $altBody = null)
    {
        try {
            require_once __DIR__ . '/../vendor/autoload.php';
            
            $mail = new PHPMailer\PHPMailer\PHPMailer(true);
            
            // Server settings
            $mail->isSMTP();
            $mail->Host = $this->smtpHost;
            $mail->SMTPAuth = true;
            $mail->Username = $this->smtpUsername;
            $mail->Password = $this->smtpPassword;
            $mail->SMTPSecure = $this->smtpEncryption;
            $mail->Port = $this->smtpPort;
            
            // Recipients
            $mail->setFrom($this->smtpFromEmail, $this->smtpFromName);
            $mail->addAddress($to);
            
            // Content
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $body;
            if ($altBody) {
                $mail->AltBody = $altBody;
            }
            
            $mail->send();
            
            return [
                'success' => true,
                'message' => 'Email sent successfully'
            ];
        } catch (Exception $e) {
            error_log('Email Error (PHPMailer): ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to send email',
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Send email using PHP's mail() function
     */
    private function sendWithMailFunction($to, $subject, $body)
    {
        try {
            $headers = [];
            $headers[] = 'MIME-Version: 1.0';
            $headers[] = 'Content-type: text/html; charset=utf-8';
            $headers[] = 'From: ' . $this->smtpFromName . ' <' . $this->smtpFromEmail . '>';
            $headers[] = 'Reply-To: ' . $this->smtpFromEmail;
            $headers[] = 'X-Mailer: PHP/' . phpversion();
            
            // Suppress mail() warnings and log them instead
            $success = @mail($to, $subject, $body, implode("\r\n", $headers));
            
            if ($success) {
                return [
                    'success' => true,
                    'message' => 'Email sent successfully'
                ];
            } else {
                // Log the error but don't expose technical details to user
                $lastError = error_get_last();
                if ($lastError) {
                    error_log('Email Error: ' . $lastError['message']);
                }
                
                return [
                    'success' => false,
                    'message' => 'Email service not configured. Please contact administrator or use the link displayed on screen.'
                ];
            }
        } catch (Exception $e) {
            error_log('Email Error (mail function): ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Email service not configured. Please contact administrator or use the link displayed on screen.',
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Send password reset email
     * 
     * @param string $to Recipient email
     * @param string $resetLink Password reset link
     * @param string $userName User's name (optional)
     * @return array Result
     */
    public function sendPasswordReset($to, $resetLink, $userName = null)
    {
        $subject = 'Password Reset Request - TESCON Ghana';
        
        $greeting = $userName ? "Hello {$userName}," : "Hello,";
        
        $body = $this->getPasswordResetTemplate($greeting, $resetLink);
        
        $altBody = "Password Reset Request\n\n"
                 . "{$greeting}\n\n"
                 . "You have requested to reset your password for your TESCON Ghana account.\n\n"
                 . "Click the link below to reset your password:\n"
                 . $resetLink . "\n\n"
                 . "This link will expire in 1 hour.\n\n"
                 . "If you did not request this password reset, please ignore this email.\n\n"
                 . "Best regards,\n"
                 . "TESCON Ghana Team";
        
        return $this->send($to, $subject, $body, $altBody);
    }
    
    /**
     * Get HTML template for password reset email
     */
    private function getPasswordResetTemplate($greeting, $resetLink)
    {
        return '
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Reset</title>
</head>
<body style="margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #f4f4f4;">
    <table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #f4f4f4; padding: 20px 0;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" border="0" style="background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                    <!-- Header -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #1e40af 0%, #dc2626 100%); padding: 30px; text-align: center;">
                            <h1 style="color: #ffffff; margin: 0; font-size: 24px; font-weight: bold;">TESCON Ghana</h1>
                            <p style="color: #ffffff; margin: 10px 0 0 0; font-size: 14px;">Password Reset Request</p>
                        </td>
                    </tr>
                    
                    <!-- Body -->
                    <tr>
                        <td style="padding: 40px 30px;">
                            <p style="color: #333333; font-size: 16px; line-height: 1.6; margin: 0 0 20px 0;">
                                ' . htmlspecialchars($greeting) . '
                            </p>
                            
                            <p style="color: #666666; font-size: 14px; line-height: 1.6; margin: 0 0 20px 0;">
                                You have requested to reset your password for your TESCON Ghana account. Click the button below to proceed with resetting your password.
                            </p>
                            
                            <table width="100%" cellpadding="0" cellspacing="0" border="0" style="margin: 30px 0;">
                                <tr>
                                    <td align="center">
                                        <a href="' . htmlspecialchars($resetLink) . '" style="display: inline-block; padding: 15px 40px; background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%); color: #ffffff; text-decoration: none; border-radius: 5px; font-weight: bold; font-size: 16px;">Reset Password</a>
                                    </td>
                                </tr>
                            </table>
                            
                            <p style="color: #666666; font-size: 13px; line-height: 1.6; margin: 20px 0; padding: 15px; background-color: #fff3cd; border-left: 4px solid #ffc107; border-radius: 4px;">
                                <strong>⚠️ Security Notice:</strong> This link will expire in 1 hour for security reasons.
                            </p>
                            
                            <p style="color: #999999; font-size: 12px; line-height: 1.6; margin: 20px 0 0 0;">
                                If the button above doesn\'t work, copy and paste this link into your browser:<br>
                                <a href="' . htmlspecialchars($resetLink) . '" style="color: #1e40af; word-break: break-all;">' . htmlspecialchars($resetLink) . '</a>
                            </p>
                            
                            <p style="color: #999999; font-size: 12px; line-height: 1.6; margin: 20px 0 0 0;">
                                If you did not request this password reset, please ignore this email. Your password will remain unchanged.
                            </p>
                        </td>
                    </tr>
                    
                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #f8f9fa; padding: 20px 30px; text-align: center; border-top: 1px solid #e9ecef;">
                            <p style="color: #6c757d; font-size: 12px; margin: 0 0 10px 0;">
                                Best regards,<br>
                                <strong>TESCON Ghana Team</strong>
                            </p>
                            <p style="color: #adb5bd; font-size: 11px; margin: 0;">
                                &copy; ' . date('Y') . ' TESCON Ghana. All rights reserved.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>';
    }
    
    /**
     * Validate email address
     * 
     * @param string $email Email to validate
     * @return bool
     */
    public static function validateEmail($email)
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
}
