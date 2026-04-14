<?php
session_start();
require_once __DIR__ . '/user_store.php';

$id = trim($_GET['id'] ?? '');
$user = $id === '' ? null : find_user($id);

if ($user === null) {
    header('Location: listUsers.php');
    exit;
}

$countries = countries();
$skillOptions = skill_options();

$values = [
    'first_name' => $user['first_name'] ?? '',
    'last_name' => $user['last_name'] ?? '',
    'address' => $user['address'] ?? '',
    'country' => $user['country'] ?? 'Select Country',
    'gender' => $user['gender'] ?? '',
    'skills' => array_values(array_intersect($skillOptions, (array) ($user['skills'] ?? []))),
    'username' => $user['username'] ?? '',
    'department' => $user['department'] ?? '',
];

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $values = [
        'first_name' => trim($_POST['first_name'] ?? ''),
        'last_name' => trim($_POST['last_name'] ?? ''),
        'address' => trim($_POST['address'] ?? ''),
        'country' => trim($_POST['country'] ?? 'Select Country'),
        'gender' => trim($_POST['gender'] ?? ''),
        'skills' => array_values(array_intersect($skillOptions, (array) ($_POST['skills'] ?? []))),
        'username' => trim($_POST['username'] ?? ''),
        'department' => trim($_POST['department'] ?? ''),
    ];

    if ($values['first_name'] === '') {
        $errors['first_name'] = 'First Name is required.';
    }

    if ($values['last_name'] === '') {
        $errors['last_name'] = 'Last Name is required.';
    }

    if ($values['address'] === '') {
        $errors['address'] = 'Address is required.';
    }

    if ($values['country'] === '' || $values['country'] === 'Select Country') {
        $errors['country'] = 'Please select a country.';
    }

    if ($values['gender'] !== 'Male' && $values['gender'] !== 'Female') {
        $errors['gender'] = 'Gender is required.';
    }

    if ($values['skills'] === []) {
        $errors['skills'] = 'Select at least one skill.';
    }

    if ($values['username'] === '') {
        $errors['username'] = 'Username is required.';
    }

    if ($values['department'] === '') {
        $errors['department'] = 'Department is required.';
    }

    if ($errors === []) {
        try {
            update_user($id, [
                'first_name' => $values['first_name'],
                'last_name' => $values['last_name'],
                'address' => $values['address'],
                'country' => $values['country'],
                'gender' => $values['gender'],
                'skills' => $values['skills'],
                'username' => $values['username'],
                'department' => $values['department'],
            ]);

            header('Location: viewUser.php?id=' . urlencode($id));
            exit;
        } catch (InvalidArgumentException $e) {
            $errors['username'] = $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit User</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1 class="h3 mb-0">Edit User</h1>
            <a class="btn btn-outline-secondary" href="viewUser.php?id=<?php echo urlencode($id); ?>">Cancel</a>
        </div>

        <?php if ($errors !== []): ?>
            <div class="alert alert-danger">
                Please fix the highlighted errors and submit again.
            </div>
        <?php endif; ?>

        <form method="post" class="card card-body bg-white" novalidate>
            <div class="mb-3">
                <label for="first_name" class="form-label">First Name</label>
                <input type="text" class="form-control <?php echo isset($errors['first_name']) ? 'is-invalid' : ''; ?>" id="first_name" name="first_name" value="<?php echo h($values['first_name']); ?>">
                <div class="invalid-feedback d-block"><?php echo h($errors['first_name'] ?? ''); ?></div>
            </div>

            <div class="mb-3">
                <label for="last_name" class="form-label">Last Name</label>
                <input type="text" class="form-control <?php echo isset($errors['last_name']) ? 'is-invalid' : ''; ?>" id="last_name" name="last_name" value="<?php echo h($values['last_name']); ?>">
                <div class="invalid-feedback d-block"><?php echo h($errors['last_name'] ?? ''); ?></div>
            </div>

            <div class="mb-3">
                <label for="address" class="form-label">Address</label>
                <textarea class="form-control <?php echo isset($errors['address']) ? 'is-invalid' : ''; ?>" id="address" name="address"><?php echo h($values['address']); ?></textarea>
                <div class="invalid-feedback d-block"><?php echo h($errors['address'] ?? ''); ?></div>
            </div>

            <div class="mb-3">
                <label for="country" class="form-label">Country</label>
                <select class="form-select <?php echo isset($errors['country']) ? 'is-invalid' : ''; ?>" id="country" name="country">
                    <?php foreach ($countries as $country): ?>
                        <option value="<?php echo h($country); ?>" <?php echo $values['country'] === $country ? 'selected' : ''; ?>><?php echo h($country); ?></option>
                    <?php endforeach; ?>
                </select>
                <div class="invalid-feedback d-block"><?php echo h($errors['country'] ?? ''); ?></div>
            </div>

            <div class="mb-3">
                <label class="form-label d-block">Gender</label>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" id="gender_male" name="gender" value="Male" <?php echo $values['gender'] === 'Male' ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="gender_male">Male</label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" id="gender_female" name="gender" value="Female" <?php echo $values['gender'] === 'Female' ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="gender_female">Female</label>
                </div>
                <div class="invalid-feedback d-block"><?php echo h($errors['gender'] ?? ''); ?></div>
            </div>

            <div class="mb-3">
                <label class="form-label d-block">Skills</label>
                <div class="d-flex flex-wrap gap-3">
                    <?php foreach ($skillOptions as $skill): ?>
                        <label class="form-check-label">
                            <input class="form-check-input me-1" type="checkbox" name="skills[]" value="<?php echo h($skill); ?>" <?php echo in_array($skill, $values['skills'], true) ? 'checked' : ''; ?>><?php echo h($skill); ?>
                        </label>
                    <?php endforeach; ?>
                </div>
                <div class="invalid-feedback d-block"><?php echo h($errors['skills'] ?? ''); ?></div>
            </div>

            <div class="mb-3">
                <label for="username" class="form-label">Username</label>
                <input type="text" class="form-control <?php echo isset($errors['username']) ? 'is-invalid' : ''; ?>" id="username" name="username" value="<?php echo h($values['username']); ?>">
                <div class="invalid-feedback d-block"><?php echo h($errors['username'] ?? ''); ?></div>
            </div>

            <div class="mb-3">
                <label for="department" class="form-label">Department</label>
                <input type="text" class="form-control <?php echo isset($errors['department']) ? 'is-invalid' : ''; ?>" id="department" name="department" value="<?php echo h($values['department']); ?>">
                <div class="invalid-feedback d-block"><?php echo h($errors['department'] ?? ''); ?></div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">Save Changes</button>
                <a class="btn btn-outline-secondary" href="viewUser.php?id=<?php echo urlencode($id); ?>">Cancel</a>
            </div>
        </form>
    </div>
</body>
</html>
