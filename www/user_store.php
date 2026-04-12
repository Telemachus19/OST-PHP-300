<?php

const USERS_FILE = __DIR__ . '/users.json';

function h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function countries(): array
{
    return [
        'Select Country',
        'Egypt',
        'Saudi Arabia',
        'United Arab Emirates',
        'Jordan',
        'Kuwait',
        'Qatar',
        'Bahrain',
        'Oman',
        'Lebanon',
        'Syria',
        'Iraq',
        'Palestine',
        'Sudan',
        'Libya',
        'Morocco',
        'Algeria',
        'Tunisia',
        'United States',
        'United Kingdom',
        'Canada',
        'Australia',
        'India',
        'Pakistan',
        'Bangladesh',
        'China',
        'Japan',
        'Germany',
        'France',
    ];
}

function skill_options(): array
{
    return ['PHP', 'J2SE', 'MySQL', 'PostgreSQL'];
}

function load_users(): array
{
    if (!file_exists(USERS_FILE)) {
        return [];
    }

    $raw = file_get_contents(USERS_FILE);
    if ($raw === false || trim($raw) === '') {
        return [];
    }

    $decoded = json_decode($raw, true);
    if (!is_array($decoded)) {
        return [];
    }

    return array_values(array_filter($decoded, static fn ($row) => is_array($row) && isset($row['id'])));
}

function save_users(array $users): void
{
    file_put_contents(USERS_FILE, json_encode(array_values($users), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
}

function make_user_id(): string
{
    return uniqid('usr_', true);
}

function add_user(array $user): string
{
    $users = load_users();
    $id = make_user_id();
    $now = date('Y-m-d H:i:s');

    $user['id'] = $id;
    $user['created_at'] = $now;
    $user['updated_at'] = $now;

    $users[] = $user;
    save_users($users);

    return $id;
}

function find_user(string $id): ?array
{
    $users = load_users();

    foreach ($users as $user) {
        if (($user['id'] ?? '') === $id) {
            return $user;
        }
    }

    return null;
}

function update_user(string $id, array $updated): bool
{
    $users = load_users();

    foreach ($users as $index => $user) {
        if (($user['id'] ?? '') === $id) {
            $updated['id'] = $id;
            $updated['created_at'] = $user['created_at'] ?? date('Y-m-d H:i:s');
            $updated['updated_at'] = date('Y-m-d H:i:s');
            $users[$index] = $updated;
            save_users($users);
            return true;
        }
    }

    return false;
}

function delete_user(string $id): bool
{
    $users = load_users();
    $kept = [];
    $deleted = false;

    foreach ($users as $user) {
        if (($user['id'] ?? '') === $id) {
            $deleted = true;
            continue;
        }

        $kept[] = $user;
    }

    if ($deleted) {
        save_users($kept);
    }

    return $deleted;
}
