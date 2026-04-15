<?php
session_start();
require_once __DIR__ . '/user_store.php';

if (is_authenticated()) {
    header('Location: listUsers.php');
    exit;
}

$values = [
    'username' => '',
    'password' => '',
];
$errors = [];
$authError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $values = [
        'username' => trim($_POST['username'] ?? ''),
        'password' => (string) ($_POST['password'] ?? ''),
    ];

    if ($values['username'] === '') {
        $errors['username'] = 'Username is required.';
    }

    if ($values['password'] === '') {
        $errors['password'] = 'Password is required.';
    }

    if ($errors === []) {
        $authUser = authenticate_user($values['username'], $values['password']);
        if ($authUser === null) {
            $authError = 'Invalid username or password.';
        } else {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $authUser['id'];
            $_SESSION['username'] = $authUser['username'];
            header('Location: listUsers.php');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container py-5" style="max-width: 520px;">
        <h1 class="h3 mb-3">Login</h1>
        <p class="text-muted">Sign in using your username and password.</p>

        <?php if ($authError !== ''): ?>
            <div class="alert alert-danger"><?php echo h($authError); ?></div>
        <?php endif; ?>

        <form id="loginForm" method="post" class="card card-body bg-white" novalidate>
            <div class="mb-3">
                <label for="username" class="form-label">Username</label>
                <input type="text" class="form-control <?php echo isset($errors['username']) ? 'is-invalid' : ''; ?>" id="username" name="username" value="<?php echo h($values['username']); ?>" required>
                <div class="invalid-feedback d-block" id="username_error"><?php echo h($errors['username'] ?? ''); ?></div>
            </div>

            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input type="password" class="form-control <?php echo isset($errors['password']) ? 'is-invalid' : ''; ?>" id="password" name="password" required>
                <div class="invalid-feedback d-block" id="password_error"><?php echo h($errors['password'] ?? ''); ?></div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">Login</button>
                <a class="btn btn-outline-secondary" href="registration.php">Create Account</a>
            </div>
        </form>
    </div>

    <script>
        document.getElementById('loginForm').addEventListener('submit', function (event) {
            const username = document.getElementById('username').value.trim();
            const password = document.getElementById('password').value;

            document.getElementById('username_error').textContent = '';
            document.getElementById('password_error').textContent = '';

            let hasError = false;
            if (!username) {
                document.getElementById('username_error').textContent = 'Username is required.';
                hasError = true;
            }
            if (!password) {
                document.getElementById('password_error').textContent = 'Password is required.';
                hasError = true;
            }

            if (hasError) {
                event.preventDefault();
            }
        });
    </script>
</body>
</html>
