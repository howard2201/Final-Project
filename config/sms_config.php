<?php
/**
 * SMS Gateway Configuration
 *
 * Copy/adjust these values to match your environment.
 * mode:
 *   - production: sends real SMS using the configured gateway
 *   - development: does not send SMS, logs/returns codes instead
 *
 * gateway:
 *   - semaphore  : default HTTP API (requires api_key/api_secret)
 *   - custom_http: generic HTTP endpoint (configure custom_http array)
 *   - gammu      : uses local GSM modem via gammu-smsd-inject
 */
return [
    'mode' => 'production',
    'gateway' => 'twilio', // semaphore | custom_http | twilio | gammu | development

    // Shared credentials (used by semaphore/custom_http when placeholders exist)
    'api_key' => getenv('SMS_API_KEY') ?: '',
    'api_secret' => getenv('SMS_API_SECRET') ?: '',
    'sender_id' => getenv('SMS_SENDER_ID') ?: 'BARANGAY',

    // Semaphore / default HTTP API settings
    'api_url' => getenv('SMS_API_URL') ?: 'https://api.semaphore.co/api/v4/messages',

    // Generic HTTP gateway configuration
    'custom_http' => [
        'url' => 'http://localhost:8000/send-sms', // Replace with your local gateway endpoint
        'method' => 'POST',
        'headers' => [
            // 'Authorization' => 'Bearer your_token',
        ],
        'body' => [
            // Supports placeholders: {number}, {message}, {sender}, {api_key}, {api_secret}
            'api_key' => '{api_key}',
            'to' => '{number}',
            'message' => '{message}',
            'sender' => '{sender}',
        ],
        // Response success check (JSON path). Leave empty to treat HTTP 200 as success.
        'success_path' => '', // e.g., 'data.status'
        'success_value' => 'queued',
    ],

    // Local GSM modem settings (Gammu / TextBee)
    'gammu' => [
        'binary' => 'C:\\Gammu\\gammu-smsd-inject.exe', // full path to gammu-smsd-inject
        'config' => 'C:\\Gammu\\gammurc', // optional gammu config file
        // Command template. Supports placeholders: {binary}, {config}, {number}, {message}
        'command_template' => '"{binary}" {config} TEXT {number} -text {message}',
        // Additional environment variables if needed
        'env' => [
            // 'PATH' => 'C:\\Gammu;{PATH}',
        ],
        // Timeout in seconds
        'timeout' => 15,
    ],

    // Twilio configuration (use when gateway = 'twilio')
    'twilio' => [
        'account_sid' => getenv('TWILIO_ACCOUNT_SID') ?: 'TWILIO_ACCOUNT_SID',
        'auth_token' => getenv('TWILIO_AUTH_TOKEN') ?: 'TWILIO_AUTH_TOKEN',
        'from_number' => getenv('TWILIO_FROM_NUMBER') ?: 'TWILIO_FROM_NUMBER',
    ],

    // Internal API token (used by api/sms_gateway.php)
    'internal_api_token' => getenv('SMS_INTERNAL_API_TOKEN') ?: 'change-me',
];

