<?php
// boiler plates
function is_authenticated(): bool
{
    return isset($_SESSION['user_id'], $_SESSION['username']) && $_SESSION['user_id'] !== '';
}

function current_username(): ?string
{
    if (!is_authenticated()) {
        return null;
    }

    return (string) $_SESSION['username'];
}

function require_authentication(): void
{
    if (is_authenticated()) {
        return;
    }

    header('Location: login.php');
    exit;
}
// main auth function
function authenticate_user(string $username, string $password): ?array
{
    $username = trim($username);
    if ($username === '' || $password === '') {
        return null;
    }

    $pdo = get_pdo();
    $stmt = $pdo->prepare(
        'SELECT id, username, password_hash
         FROM users
         WHERE username = :username
         LIMIT 1'
    );
    $stmt->execute(['username' => $username]);
    $user = $stmt->fetch();

    if ($user === false) {
        return null;
    }

    if (!password_verify($password, (string) $user['password_hash'])) {
        return null;
    }

    return [
        'id' => (string) $user['id'],
        'username' => (string) $user['username'],
    ];
}
