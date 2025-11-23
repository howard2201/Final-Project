<?php
/**
 * SMS Service Class
 * Handles sending SMS verification codes
 * 
 * Note: This is a template implementation. You'll need to integrate with an actual SMS provider
 * like Twilio, Semaphore, or your local SMS gateway.
 */
class SMSService {
    private $apiKey;
    private $apiSecret;
    private $senderId;
    private $apiUrl;

    public function __construct() {
        // Configure your SMS provider credentials here
        // For development/testing, you can use a mock service
        $this->apiKey = getenv('SMS_API_KEY') ?: 'your_api_key_here';
        $this->apiSecret = getenv('SMS_API_SECRET') ?: 'your_api_secret_here';
        $this->senderId = getenv('SMS_SENDER_ID') ?: 'BARANGAY';
        $this->apiUrl = getenv('SMS_API_URL') ?: 'https://api.semaphore.co/api/v4/messages';
    }

    /**
     * Send SMS verification code
     * @param string $phoneNumber Phone number in international format (e.g., +639123456789)
     * @param string $code Verification code (6 digits)
     * @return bool Success status
     */
    public function sendVerificationCode($phoneNumber, $code) {
        // Format phone number to ensure it starts with +
        $phoneNumber = $this->formatPhoneNumber($phoneNumber);
        
        $message = "Your verification code is: {$code}. This code will expire in 10 minutes. Do not share this code with anyone.";
        
        // For development: Log the code instead of sending (remove in production)
        if (getenv('SMS_MODE') === 'development' || empty($this->apiKey) || $this->apiKey === 'your_api_key_here') {
            error_log("SMS Verification Code for {$phoneNumber}: {$code}");
            // In development, you can also display it in the UI
            return true;
        }

        // Example implementation using cURL (adjust based on your SMS provider)
        return $this->sendViaAPI($phoneNumber, $message);
    }

    /**
     * Send password reset SMS
     * @param string $phoneNumber Phone number
     * @param string $code Verification code
     * @return bool Success status
     */
    public function sendPasswordResetCode($phoneNumber, $code) {
        $phoneNumber = $this->formatPhoneNumber($phoneNumber);
        $message = "Your password reset code is: {$code}. This code will expire in 15 minutes. If you didn't request this, please ignore.";
        
        if (getenv('SMS_MODE') === 'development' || empty($this->apiKey) || $this->apiKey === 'your_api_key_here') {
            error_log("SMS Password Reset Code for {$phoneNumber}: {$code}");
            return true;
        }

        return $this->sendViaAPI($phoneNumber, $message);
    }

    /**
     * Format phone number to international format
     * @param string $phoneNumber
     * @return string
     */
    private function formatPhoneNumber($phoneNumber) {
        // Remove all non-numeric characters
        $phoneNumber = preg_replace('/[^0-9]/', '', $phoneNumber);
        
        // If it starts with 0, replace with +63 (Philippines)
        if (substr($phoneNumber, 0, 1) === '0') {
            $phoneNumber = '+63' . substr($phoneNumber, 1);
        } elseif (substr($phoneNumber, 0, 2) === '63') {
            $phoneNumber = '+' . $phoneNumber;
        } elseif (substr($phoneNumber, 0, 1) !== '+') {
            $phoneNumber = '+63' . $phoneNumber;
        }
        
        return $phoneNumber;
    }

    /**
     * Send SMS via API (Example using Semaphore API)
     * Adjust this method based on your SMS provider
     */
    private function sendViaAPI($phoneNumber, $message) {
        $ch = curl_init();
        
        // Example for Semaphore SMS API
        $data = [
            'apikey' => $this->apiKey,
            'number' => $phoneNumber,
            'message' => $message,
            'sendername' => $this->senderId
        ];

        curl_setopt($ch, CURLOPT_URL, $this->apiUrl);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200) {
            $result = json_decode($response, true);
            return isset($result[0]['status']) && $result[0]['status'] === 'QUEUED';
        }

        error_log("SMS API Error: HTTP {$httpCode} - {$response}");
        return false;
    }

    /**
     * Generate a random 6-digit verification code
     * @return string
     */
    public static function generateCode() {
        return str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
    }
}

