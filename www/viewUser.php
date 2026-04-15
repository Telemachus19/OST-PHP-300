<?php
session_start();
require_once __DIR__ . '/user_store.php';

require_authentication();
$loggedInUsername = current_username();

$id = trim($_GET['id'] ?? '');
$user = $id === '' ? null : find_user($id);

if ($user === null) {
    header('Location: listUsers.php');
    exit;
}

$skills = (array) ($user['skills'] ?? []);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View User</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container py-4">
        <div class="d-flex justify-content-end mb-3">
            <div class="d-flex align-items-center gap-2">
                <span class="text-muted">Logged in as <strong><?php echo h((string) $loggedInUsername); ?></strong></span>
                <a class="btn btn-sm btn-outline-danger" href="logout.php">Logout</a>
            </div>
        </div>

        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h1 class="h5 mb-0">User Details</h1>
                <div class="d-flex gap-2">
                    <a class="btn btn-sm btn-outline-warning" href="editUser.php?id=<?php echo urlencode($user['id']); ?>">Edit</a>
                    <a class="btn btn-sm btn-outline-secondary" href="listUsers.php">Back to List</a>
                </div>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="text-muted small">First Name</div>
                        <div><?php echo h($user['first_name'] ?? ''); ?></div>
                    </div>
                    <div class="col-md-6">
                        <div class="text-muted small">Profile Picture</div>
                        <div>
                            <?php if (!empty($user['profile_picture_path'])): ?>
                                <img src="<?php echo h((string) $user['profile_picture_path']); ?>" alt="Profile picture" class="img-thumbnail" style="max-width: 180px;">
                            <?php else: ?>
                                <span class="text-muted">No image uploaded.</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="text-muted small">Last Name</div>
                        <div><?php echo h($user['last_name'] ?? ''); ?></div>
                    </div>
                    <div class="col-md-6">
                        <div class="text-muted small">Username</div>
                        <div><?php echo h($user['username'] ?? ''); ?></div>
                    </div>
                    <div class="col-md-6">
                        <div class="text-muted small">Gender</div>
                        <div><?php echo h($user['gender'] ?? ''); ?></div>
                    </div>
                    <div class="col-md-6">
                        <div class="text-muted small">Country</div>
                        <div><?php echo h($user['country'] ?? ''); ?></div>
                    </div>
                    <div class="col-md-6">
                        <div class="text-muted small">Department</div>
                        <div><?php echo h($user['department'] ?? ''); ?></div>
                    </div>
                    <div class="col-12">
                        <div class="text-muted small">Address</div>
                        <div><?php echo nl2br(h($user['address'] ?? '')); ?></div>
                    </div>
                    <div class="col-12">
                        <div class="text-muted small">Skills</div>
                        <div><?php echo h(implode(', ', $skills)); ?></div>
                    </div>
                    <div class="col-md-6">
                        <div class="text-muted small">Created At</div>
                        <div><?php echo h($user['created_at'] ?? ''); ?></div>
                    </div>
                    <div class="col-md-6">
                        <div class="text-muted small">Updated At</div>
                        <div><?php echo h($user['updated_at'] ?? ''); ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
