<?php
/**
 * Generic SMS API endpoint
 *
 * Usage:
 * POST /api/sms_gateway.php
 * Body (JSON or form-data):
 *   - token (required): matches config/sms_config.php internal_api_token
 *   - phone / number (required)
 *   - message (optional if code provided)
 *   - code (optional) : if provided and message missing, default OTP template is used
 *   - type (optional) : 'otp' | 'notification' (for logging only)
 */

require_once __DIR__ . '/../config/SMSService.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'POST method only']);
    exit;
}

$rawInput = file_get_contents('php://input');
$payload = json_decode($rawInput, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    $payload = $_POST;
}

$token = $payload['token'] ?? $payload['api_key'] ?? '';
$phone = $payload['phone'] ?? $payload['number'] ?? '';
$message = $payload['message'] ?? '';
$code = $payload['code'] ?? null;
$type = $payload['type'] ?? 'otp';

$service = new SMSService();

if (!$service->validateInternalToken($token)) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Invalid token']);
    exit;
}

if (empty($phone)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Phone number is required']);
    exit;
}

if (empty($message)) {
    if (!empty($code)) {
        $message = "Your verification code is: {$code}. This code will expire shortly. Do not share this code.";
    } else {
        $code = SMSService::generateCode();
        $message = "Your verification code is: {$code}. This code will expire shortly. Do not share this code.";
    }
}

$sent = $service->sendRawMessage($phone, $message);

if ($sent) {
    $response = [
        'success' => true,
        'message' => 'SMS dispatched successfully',
        'mode' => $service->getMode(),
        'type' => $type,
    ];
    if (!$service->isProduction()) {
        $response['dev_code'] = $code;
    }
    echo json_encode($response);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to send SMS. Check gateway configuration.']);
}

