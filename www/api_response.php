<?php

function api_json_headers(): void
{
    header('Content-Type: application/json');
}

function api_success(array $data, string $message = 'OK', int $statusCode = 200): void
{
    http_response_code($statusCode);
    api_json_headers();
    echo json_encode([
        'success' => true,
        'data' => $data,
        'message' => $message,
    ]);
    exit;
}

function api_error(string $message, int $statusCode, ?array $errors = null): void
{
    http_response_code($statusCode);
    api_json_headers();

    $payload = [
        'success' => false,
        'message' => $message,
    ];

    if ($errors !== null && $errors !== []) {
        $payload['errors'] = $errors;
    }

    echo json_encode($payload);
    exit;
}

function api_validation_failed(array $errors): void
{
    api_error('Validation Failed', 422, $errors);
}

function api_read_json_input(): array
{
    $raw = file_get_contents('php://input');
    if ($raw === false || trim($raw) === '') {
        return [];
    }

    $decoded = json_decode($raw, true);
    if (!is_array($decoded)) {
        api_error('Invalid JSON payload.', 400);
    }

    return $decoded;
}