<?php

function api_base64url_encode(string $data): string
{
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function api_base64url_decode(string $data): string
{
    $padding = strlen($data) % 4;
    if ($padding > 0) {
        $data .= str_repeat('=', 4 - $padding);
    }

    $decoded = base64_decode(strtr($data, '-_', '+/'), true);
    return $decoded === false ? '' : $decoded;
}

function issue_jwt(array $user): string
{
    $header = [
        'alg' => 'HS256',
        'typ' => 'JWT',
    ];

    $now = time();
    $payload = [
        'sub' => (string) $user['id'],
        'username' => (string) $user['username'],
        'iat' => $now,
        'exp' => $now + JWT_TTL_SECONDS,
    ];

    $headerEncoded = api_base64url_encode((string) json_encode($header));
    $payloadEncoded = api_base64url_encode((string) json_encode($payload));
    $signature = hash_hmac('sha256', $headerEncoded . '.' . $payloadEncoded, JWT_SECRET, true);
    $signatureEncoded = api_base64url_encode($signature);

    return $headerEncoded . '.' . $payloadEncoded . '.' . $signatureEncoded;
}

function api_get_bearer_token(): ?string
{
    $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if ($header === '' && function_exists('getallheaders')) {
        $headers = getallheaders();
        if (is_array($headers)) {
            $header = (string) ($headers['Authorization'] ?? $headers['authorization'] ?? '');
        }
    }

    if ($header === '') {
        return null;
    }

    if (preg_match('/^Bearer\s+(.+)$/i', $header, $matches) !== 1) {
        return null;
    }

    return trim((string) $matches[1]);
}

function verify_jwt(string $token, ?string &$error = null): ?array
{
    $parts = explode('.', $token);
    if (count($parts) !== 3) {
        $error = 'Invalid token format.';
        return null;
    }

    [$headerEncoded, $payloadEncoded, $signatureEncoded] = $parts;
    $expectedSignature = api_base64url_encode(hash_hmac('sha256', $headerEncoded . '.' . $payloadEncoded, JWT_SECRET, true));
    if (!hash_equals($expectedSignature, $signatureEncoded)) {
        $error = 'Invalid token signature.';
        return null;
    }

    $payloadJson = api_base64url_decode($payloadEncoded);
    if ($payloadJson === '') {
        $error = 'Invalid token payload.';
        return null;
    }

    $payload = json_decode($payloadJson, true);
    if (!is_array($payload)) {
        $error = 'Invalid token payload.';
        return null;
    }

    $exp = isset($payload['exp']) ? (int) $payload['exp'] : 0;
    if ($exp <= 0 || time() >= $exp) {
        $error = 'Token expired.';
        return null;
    }

    if (!isset($payload['sub']) || (string) $payload['sub'] === '') {
        $error = 'Invalid token subject.';
        return null;
    }

    return $payload;
}

function require_api_auth_user(): array
{
    $token = api_get_bearer_token();
    if ($token === null || $token === '') {
        api_error('Unauthorized: JWT token is missing.', 401);
    }

    $error = null;
    $payload = verify_jwt($token, $error);
    if ($payload === null) {
        api_error('Unauthorized: ' . ($error ?? 'Invalid token.'), 401);
    }

    return [
        'id' => (string) $payload['sub'],
        'username' => (string) ($payload['username'] ?? ''),
    ];
}