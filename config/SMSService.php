<?php
/**
 * SMSService
 * Provides a unified abstraction for sending SMS messages/OTP codes using:
 *  - Local GSM modem (Gammu/TextBee)
 *  - Generic HTTP gateway
 *  - Default HTTP API (Semaphore-compatible)
 *  - Development mode (logs only)
 */

class SMSService {
    private $mode;
    private $gateway;
    private $apiKey;
    private $apiSecret;
    private $senderId;
    private $apiUrl;
    private $customHttp;
    private $gammuConfig;
    private $twilioConfig;
    private $internalToken;
    private $lastDevMessage;

    public function __construct() {
        $configPath = __DIR__ . '/sms_config.php';
        $config = [];
        if (file_exists($configPath)) {
            $loaded = include $configPath;
            if (is_array($loaded)) {
                $config = $loaded;
            }
        }

        $this->mode = strtolower($config['mode'] ?? getenv('SMS_MODE') ?: 'development');
        $this->gateway = strtolower($config['gateway'] ?? 'development');
        $this->apiKey = $config['api_key'] ?? getenv('SMS_API_KEY') ?: 'your_api_key_here';
        $this->apiSecret = $config['api_secret'] ?? getenv('SMS_API_SECRET') ?: 'your_api_secret_here';
        $this->senderId = $config['sender_id'] ?? getenv('SMS_SENDER_ID') ?: 'BARANGAY';
        $this->apiUrl = $config['api_url'] ?? getenv('SMS_API_URL') ?: 'https://api.semaphore.co/api/v4/messages';
        $this->customHttp = $config['custom_http'] ?? [];
        $this->gammuConfig = $config['gammu'] ?? [];
        $this->twilioConfig = $config['twilio'] ?? [];
        $this->internalToken = $config['internal_api_token'] ?? getenv('SMS_INTERNAL_API_TOKEN') ?: 'change-me';

        if (empty($this->apiKey) || $this->apiKey === 'your_api_key_here') {
            $this->mode = 'development';
        }
    }

    public static function generateCode() {
        return str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    public function isProduction() {
        return $this->mode === 'production';
    }

    public function getMode() {
        return $this->mode;
    }

    public function getInternalToken() {
        return $this->internalToken;
    }

    public function validateInternalToken($token) {
        return hash_equals((string)$this->internalToken, (string)$token);
    }

    /**
     * Send OTP message for registration
     */
    public function sendVerificationCode($phoneNumber, $code) {
        $message = "Your verification code is: {$code}. This code will expire in 10 minutes. Do not share this code with anyone.";
        return $this->sendMessage($phoneNumber, $message);
    }

    /**
     * Send OTP message for password reset
     */
    public function sendPasswordResetCode($phoneNumber, $code) {
        $message = "Your password reset code is: {$code}. This code will expire in 15 minutes. If you didn't request this, please ignore.";
        return $this->sendMessage($phoneNumber, $message);
    }

    /**
     * Send any raw SMS message (used by API endpoint)
     */
    public function sendRawMessage($phoneNumber, $message) {
        return $this->sendMessage($phoneNumber, $message);
    }

    private function sendMessage($phoneNumber, $message) {
        $phoneNumber = $this->formatPhoneNumber($phoneNumber);
        $this->lastDevMessage = [
            'phone' => $phoneNumber,
            'message' => $message,
            'timestamp' => date('Y-m-d H:i:s'),
        ];

        if ($this->mode !== 'production') {
            error_log("[SMS:{$this->gateway}] {$phoneNumber} => {$message}");
            return true;
        }

        switch ($this->gateway) {
            case 'gammu':
                return $this->sendViaGammu($phoneNumber, $message);
            case 'custom_http':
                return $this->sendViaCustomHttp($phoneNumber, $message);
            case 'twilio':
                return $this->sendViaTwilio($phoneNumber, $message);
            case 'semaphore':
            default:
                return $this->sendViaSemaphore($phoneNumber, $message);
        }
    }

    private function sendViaSemaphore($phoneNumber, $message) {
        $data = [
            'apikey' => $this->apiKey,
            'number' => $phoneNumber,
            'message' => $message,
            'sendername' => $this->senderId
        ];

        $ch = curl_init($this->apiUrl);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $response = curl_exec($ch);
        if ($response === false) {
            $error = curl_error($ch);
            curl_close($ch);
            error_log("SMS API Error: {$error}");
            return false;
        }

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200) {
            $result = json_decode($response, true);
            return isset($result[0]['status']) && strtoupper($result[0]['status']) === 'QUEUED';
        }

        error_log("SMS API Error: HTTP {$httpCode} - {$response}");
        return false;
    }

    private function sendViaCustomHttp($phoneNumber, $message) {
        $url = $this->customHttp['url'] ?? null;
        if (!$url) {
            error_log('Custom HTTP SMS gateway URL not configured.');
            return false;
        }

        $method = strtoupper($this->customHttp['method'] ?? 'POST');
        $headers = $this->customHttp['headers'] ?? [];
        $bodyTemplate = $this->customHttp['body'] ?? [];

        $placeholders = [
            '{number}' => $phoneNumber,
            '{message}' => $message,
            '{sender}' => $this->senderId,
            '{api_key}' => $this->apiKey,
            '{api_secret}' => $this->apiSecret,
        ];

        $payload = [];
        foreach ($bodyTemplate as $key => $value) {
            $payload[$key] = strtr($value, $placeholders);
        }

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        if (in_array(strtoupper($method), ['POST', 'PUT', 'PATCH'])) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($payload));
        } elseif (!empty($payload)) {
            $url .= (strpos($url, '?') === false ? '?' : '&') . http_build_query($payload);
            curl_setopt($ch, CURLOPT_URL, $url);
        }

        if (!empty($headers)) {
            $formattedHeaders = [];
            foreach ($headers as $headerKey => $headerValue) {
                $formattedHeaders[] = "{$headerKey}: {$headerValue}";
            }
            curl_setopt($ch, CURLOPT_HTTPHEADER, $formattedHeaders);
        }

        $response = curl_exec($ch);
        if ($response === false) {
            $error = curl_error($ch);
            curl_close($ch);
            error_log("Custom SMS Gateway Error: {$error}");
            return false;
        }

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode >= 200 && $httpCode < 300) {
            $successPath = $this->customHttp['success_path'] ?? '';
            if (empty($successPath)) {
                return true;
            }

            $result = json_decode($response, true);
            if (!is_array($result)) {
                error_log("Custom SMS Gateway invalid JSON: {$response}");
                return false;
            }

            $value = $this->getValueByPath($result, $successPath);
            if ($value === null) {
                error_log("Custom SMS Gateway success path '{$successPath}' not found.");
                return false;
            }

            $expected = $this->customHttp['success_value'] ?? 'queued';
            return strtolower((string)$value) === strtolower((string)$expected);
        }

        error_log("Custom SMS Gateway HTTP {$httpCode}: {$response}");
        return false;
    }

    private function sendViaGammu($phoneNumber, $message) {
        $binary = $this->gammuConfig['binary'] ?? 'gammu-smsd-inject';
        $configFile = $this->gammuConfig['config'] ?? '';
        $template = $this->gammuConfig['command_template'] ?? '"{binary}" {config} TEXT {number} -text {message}';
        $timeout = (int)($this->gammuConfig['timeout'] ?? 15);
        $env = $this->gammuConfig['env'] ?? [];

        $replacements = [
            '{binary}' => $binary,
            '{config}' => $configFile ? '--config ' . escapeshellarg($configFile) : '',
            '{number}' => escapeshellarg($phoneNumber),
            '{message}' => escapeshellarg($message),
        ];

        $command = strtr($template, $replacements);
        $descriptorSpec = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = proc_open($command, $descriptorSpec, $pipes, null, $env);
        if (!is_resource($process)) {
            error_log('Unable to start Gammu process.');
            return false;
        }

        stream_set_blocking($pipes[1], true);
        stream_set_blocking($pipes[2], true);

        $output = '';
        $errorOutput = '';
        $startTime = time();

        while (true) {
            if (feof($pipes[1]) && feof($pipes[2])) {
                break;
            }

            $output .= stream_get_contents($pipes[1]);
            $errorOutput .= stream_get_contents($pipes[2]);

            if ((time() - $startTime) > $timeout) {
                proc_terminate($process);
                error_log('Gammu command timed out.');
                return false;
            }

            usleep(100000); // 0.1s
        }

        $status = proc_close($process);
        if ($status === 0) {
            return true;
        }

        error_log("Gammu Error (status {$status}): {$errorOutput} {$output}");
        return false;
    }

    private function sendViaTwilio($phoneNumber, $message) {
        $accountSid = $this->twilioConfig['account_sid'] ?? '';
        $authToken = $this->twilioConfig['auth_token'] ?? '';
        $fromNumber = $this->twilioConfig['from_number'] ?? '';

        if (empty($accountSid) || empty($authToken) || empty($fromNumber)) {
            error_log('Twilio credentials are not configured.');
            return false;
        }

        $url = "https://api.twilio.com/2010-04-01/Accounts/{$accountSid}/Messages.json";

        $payload = http_build_query([
            'To' => $phoneNumber,
            'From' => $fromNumber,
            'Body' => $message,
        ]);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_USERPWD, "{$accountSid}:{$authToken}");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);
        if ($response === false) {
            $error = curl_error($ch);
            curl_close($ch);
            error_log("Twilio API Error: {$error}");
            return false;
        }

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode >= 200 && $httpCode < 300) {
            return true;
        }

        error_log("Twilio API Error ({$httpCode}): {$response}");
        return false;
    }

    private function formatPhoneNumber($phoneNumber) {
        $phoneNumber = preg_replace('/[^0-9+]/', '', $phoneNumber);
        if (substr($phoneNumber, 0, 1) === '0') {
            $phoneNumber = '+63' . substr($phoneNumber, 1);
        } elseif (substr($phoneNumber, 0, 2) === '63') {
            $phoneNumber = '+' . $phoneNumber;
        } elseif (substr($phoneNumber, 0, 1) !== '+') {
            $phoneNumber = '+63' . $phoneNumber;
        }
        return $phoneNumber;
    }

    private function getValueByPath(array $data, $path) {
        $segments = explode('.', $path);
        $value = $data;
        foreach ($segments as $segment) {
            if (is_array($value) && array_key_exists($segment, $value)) {
                $value = $value[$segment];
            } else {
                return null;
            }
        }
        return $value;
    }

    public function getLastDevMessage() {
        return $this->lastDevMessage;
    }
}
