<?php
session_start();
require_once __DIR__ . '/user_store.php';

require_authentication();
$loggedInUsername = current_username();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    $id = trim($_POST['id'] ?? '');
    if ($id !== '') {
        delete_user($id);
    }
    header('Location: listUsers.php');
    exit;
}

$users = load_users();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users List</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h1 class="h3 mb-0">Registered Users</h1>
                <p class="mb-0 text-muted small">Logged in as <strong><?php echo h((string) $loggedInUsername); ?></strong></p>
            </div>
            <div class="d-flex gap-2">
                <a class="btn btn-primary" href="registration.php">New Registration</a>
                <a class="btn btn-outline-success" href="posts.php">Posts</a>
                <a class="btn btn-outline-primary" href="products.php">Products</a>
                <a class="btn btn-outline-danger" href="logout.php">Logout</a>
            </div>
        </div>

        <?php if ($users === []): ?>
            <div class="alert alert-info mb-0">No users saved yet.</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped table-bordered bg-white align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>Photo</th>
                            <th>Name</th>
                            <th>Username</th>
                            <th>Country</th>
                            <th>Department</th>
                            <th>Created At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td>
                                    <?php if (!empty($user['profile_picture_path'])): ?>
                                        <img src="<?php echo h((string) $user['profile_picture_path']); ?>" alt="Profile" class="rounded" style="width: 48px; height: 48px; object-fit: cover;">
                                    <?php else: ?>
                                        <span class="text-muted small">No Image</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo h(trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''))); ?></td>
                                <td><?php echo h($user['username'] ?? ''); ?></td>
                                <td><?php echo h($user['country'] ?? ''); ?></td>
                                <td><?php echo h($user['department'] ?? ''); ?></td>
                                <td><?php echo h($user['created_at'] ?? ''); ?></td>
                                <td>
                                    <div class="d-flex gap-2">
                                        <a class="btn btn-sm btn-outline-primary" href="viewUser.php?id=<?php echo urlencode($user['id']); ?>">View</a>
                                        <a class="btn btn-sm btn-outline-warning" href="editUser.php?id=<?php echo urlencode($user['id']); ?>">Edit</a>
                                        <form method="post" class="d-inline" onsubmit="return confirm('Delete this user?');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?php echo h($user['id']); ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
