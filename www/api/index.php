<?php

require_once __DIR__ . '/../user_store.php';
require_once __DIR__ . '/../api_response.php';
require_once __DIR__ . '/../api_auth.php';

function api_path_segments(): array
{
    $uriPath = parse_url($_SERVER['REQUEST_URI'] ?? '/api', PHP_URL_PATH);
    if (!is_string($uriPath)) {
        return [];
    }

    if (strpos($uriPath, '/api') !== 0) {
        return [];
    }

    $relative = substr($uriPath, 4);
    $trimmed = trim($relative, '/');
    if ($trimmed === '') {
        return [];
    }

    return array_values(array_filter(explode('/', $trimmed), static fn ($segment) => $segment !== ''));
}

function handle_api_login(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        api_error('Method Not Allowed', 405);
    }

    $payload = api_read_json_input();
    $username = trim((string) ($payload['username'] ?? ''));
    $password = (string) ($payload['password'] ?? '');

    $errors = [];
    if ($username === '') {
        $errors['username'][] = 'The username field is required.';
    }
    if ($password === '') {
        $errors['password'][] = 'The password field is required.';
    }
    if ($errors !== []) {
        api_validation_failed($errors);
    }

    $user = authenticate_user($username, $password);
    if ($user === null) {
        api_error('Unauthorized: Invalid username or password.', 401);
    }

    $token = issue_jwt($user);
    api_success([
        'token' => $token,
        'token_type' => 'Bearer',
        'expires_in' => JWT_TTL_SECONDS,
        'user' => [
            'id' => (string) $user['id'],
            'username' => (string) $user['username'],
        ],
    ], 'Login successful');
}

function handle_posts_collection(): void
{
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

    if ($method === 'GET') {
        api_success(load_posts(), 'Posts fetched successfully');
    }

    if ($method === 'POST') {
        $authUser = require_api_auth_user();
        $payload = api_read_json_input();
        $errors = validate_post_payload($payload);
        if ($errors !== []) {
            api_validation_failed($errors);
        }

        $post = create_post(
            trim((string) $payload['title']),
            trim((string) $payload['content']),
            $authUser['id']
        );

        api_success($post, 'Post created successfully', 201);
    }

    api_error('Method Not Allowed', 405);
}

function handle_post_item(int $postId): void
{
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

    if ($method === 'GET') {
        $post = find_post($postId);
        if ($post === null) {
            api_error('Post not found.', 404);
        }

        api_success($post, 'Post fetched successfully');
    }

    if ($method === 'PUT') {
        $authUser = require_api_auth_user();
        $payload = api_read_json_input();
        $errors = validate_post_payload($payload);
        if ($errors !== []) {
            api_validation_failed($errors);
        }

        $result = update_post(
            $postId,
            trim((string) $payload['title']),
            trim((string) $payload['content']),
            $authUser['id']
        );

        if ($result['status'] === 'not_found') {
            api_error('Post not found.', 404);
        }
        if ($result['status'] === 'forbidden') {
            api_error('Forbidden: You can modify only your own posts.', 403);
        }

        api_success($result['post'], 'Post updated successfully');
    }

    if ($method === 'DELETE') {
        $authUser = require_api_auth_user();
        $result = delete_post($postId, $authUser['id']);

        if ($result['status'] === 'not_found') {
            api_error('Post not found.', 404);
        }
        if ($result['status'] === 'forbidden') {
            api_error('Forbidden: You can delete only your own posts.', 403);
        }

        api_success(['id' => $postId], 'Post deleted successfully');
    }

    api_error('Method Not Allowed', 405);
}

$segments = api_path_segments();

if ($segments === []) {
    api_success([
        'name' => 'Posts Management API',
        'version' => '1.0.0',
    ], 'API root');
}

if ($segments[0] === 'auth' && count($segments) === 2 && $segments[1] === 'login') {
    handle_api_login();
}

if ($segments[0] === 'posts') {
    if (count($segments) === 1) {
        handle_posts_collection();
    }

    if (count($segments) === 2 && ctype_digit($segments[1])) {
        handle_post_item((int) $segments[1]);
    }
}

api_error('Not Found', 404);