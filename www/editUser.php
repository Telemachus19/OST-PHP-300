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
    'profile_picture_path' => $user['profile_picture_path'] ?? null,
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
        'profile_picture_path' => $user['profile_picture_path'] ?? null,
    ];

    $errors = validate_user_payload($values, false);

    $uploadError = validate_profile_picture_upload($_FILES['profile_picture'] ?? null);
    if ($uploadError !== null) {
        $errors['profile_picture'] = $uploadError;
    }

    if ($errors === []) {
        $newProfilePath = null;
        try {
            $newProfilePath = store_profile_picture($_FILES['profile_picture'] ?? null);

            $payload = [
                'first_name' => $values['first_name'],
                'last_name' => $values['last_name'],
                'address' => $values['address'],
                'country' => $values['country'],
                'gender' => $values['gender'],
                'skills' => $values['skills'],
                'username' => $values['username'],
                'department' => $values['department'],
            ];

            if ($newProfilePath !== null) {
                $payload['profile_picture_path'] = $newProfilePath;
            }

            update_user($id, $payload);

            if ($newProfilePath !== null && ($user['profile_picture_path'] ?? null) !== null) {
                delete_profile_picture((string) $user['profile_picture_path']);
            }

            header('Location: viewUser.php?id=' . urlencode($id));
            exit;
        } catch (InvalidArgumentException $e) {
            if ($newProfilePath !== null) {
                delete_profile_picture($newProfilePath);
            }

            if ($e->getMessage() === 'Username already exists.') {
                $errors['username'] = $e->getMessage();
            } else {
                $errors['profile_picture'] = $e->getMessage();
            }
        } catch (RuntimeException $e) {
            if ($newProfilePath !== null) {
                delete_profile_picture($newProfilePath);
            }

            $errors['profile_picture'] = $e->getMessage();
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
            <div>
                <h1 class="h3 mb-0">Edit User</h1>
                <p class="mb-0 text-muted small">Logged in as <strong><?php echo h((string) $loggedInUsername); ?></strong></p>
            </div>
            <div class="d-flex gap-2">
                <a class="btn btn-outline-danger" href="logout.php">Logout</a>
                <a class="btn btn-outline-secondary" href="viewUser.php?id=<?php echo urlencode($id); ?>">Cancel</a>
            </div>
        </div>

        <?php if ($errors !== []): ?>
            <div class="alert alert-danger">
                Please fix the highlighted errors and submit again.
            </div>
        <?php endif; ?>

        <form id="editUserForm" method="post" enctype="multipart/form-data" class="card card-body bg-white" novalidate>
            <div class="mb-3">
                <label for="first_name" class="form-label">First Name</label>
                <input type="text" class="form-control <?php echo isset($errors['first_name']) ? 'is-invalid' : ''; ?>" id="first_name" name="first_name" value="<?php echo h($values['first_name']); ?>">
                <div class="invalid-feedback d-block" id="first_name_error"><?php echo h($errors['first_name'] ?? ''); ?></div>
            </div>

            <div class="mb-3">
                <label for="last_name" class="form-label">Last Name</label>
                <input type="text" class="form-control <?php echo isset($errors['last_name']) ? 'is-invalid' : ''; ?>" id="last_name" name="last_name" value="<?php echo h($values['last_name']); ?>">
                <div class="invalid-feedback d-block" id="last_name_error"><?php echo h($errors['last_name'] ?? ''); ?></div>
            </div>

            <div class="mb-3">
                <label for="address" class="form-label">Address</label>
                <textarea class="form-control <?php echo isset($errors['address']) ? 'is-invalid' : ''; ?>" id="address" name="address"><?php echo h($values['address']); ?></textarea>
                <div class="invalid-feedback d-block" id="address_error"><?php echo h($errors['address'] ?? ''); ?></div>
            </div>

            <div class="mb-3">
                <label for="country" class="form-label">Country</label>
                <select class="form-select <?php echo isset($errors['country']) ? 'is-invalid' : ''; ?>" id="country" name="country">
                    <?php foreach ($countries as $country): ?>
                        <option value="<?php echo h($country); ?>" <?php echo $values['country'] === $country ? 'selected' : ''; ?>><?php echo h($country); ?></option>
                    <?php endforeach; ?>
                </select>
                <div class="invalid-feedback d-block" id="country_error"><?php echo h($errors['country'] ?? ''); ?></div>
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
                <div class="invalid-feedback d-block" id="gender_error"><?php echo h($errors['gender'] ?? ''); ?></div>
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
                <div class="invalid-feedback d-block" id="skills_error"><?php echo h($errors['skills'] ?? ''); ?></div>
            </div>

            <div class="mb-3">
                <label for="username" class="form-label">Username</label>
                <input type="text" class="form-control <?php echo isset($errors['username']) ? 'is-invalid' : ''; ?>" id="username" name="username" value="<?php echo h($values['username']); ?>">
                <div class="invalid-feedback d-block" id="username_error"><?php echo h($errors['username'] ?? ''); ?></div>
            </div>

            <div class="mb-3">
                <label for="department" class="form-label">Department</label>
                <input type="text" class="form-control <?php echo isset($errors['department']) ? 'is-invalid' : ''; ?>" id="department" name="department" value="<?php echo h($values['department']); ?>">
                <div class="invalid-feedback d-block" id="department_error"><?php echo h($errors['department'] ?? ''); ?></div>
            </div>

            <div class="mb-3">
                <label for="profile_picture" class="form-label">Profile Picture</label>
                <input type="file" class="form-control <?php echo isset($errors['profile_picture']) ? 'is-invalid' : ''; ?>" id="profile_picture" name="profile_picture" accept=".jpg,.jpeg,.png,image/jpeg,image/png">
                <div class="invalid-feedback d-block" id="profile_picture_error"><?php echo h($errors['profile_picture'] ?? ''); ?></div>
                <div class="form-text">Optional. JPG or PNG only. Maximum 5 MB.</div>
            </div>

            <?php if (!empty($values['profile_picture_path'])): ?>
                <div class="mb-3">
                    <p class="form-label mb-2">Current Picture</p>
                    <img src="<?php echo h((string) $values['profile_picture_path']); ?>" alt="Current profile picture" class="img-thumbnail" style="max-width: 180px;">
                </div>
            <?php endif; ?>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">Save Changes</button>
                <a class="btn btn-outline-secondary" href="viewUser.php?id=<?php echo urlencode($id); ?>">Cancel</a>
            </div>
        </form>
    </div>

    <script>
        const MAX_PROFILE_BYTES = 5 * 1024 * 1024;
        const ALLOWED_PROFILE_TYPES = ['image/jpeg', 'image/png'];

        document.getElementById('editUserForm').addEventListener('submit', function (event) {
            const errors = [];
            const requiredFields = ['first_name', 'last_name', 'address', 'username', 'department'];

            requiredFields.forEach(function (fieldId) {
                const element = document.getElementById(fieldId);
                if (!element.value.trim()) {
                    errors.push(fieldId);
                }
            });

            const country = document.getElementById('country').value;
            if (!country || country === 'Select Country') {
                errors.push('country');
            }

            const genderSelected = document.querySelector('input[name="gender"]:checked');
            if (!genderSelected) {
                errors.push('gender');
            }

            const skillsSelected = document.querySelectorAll('input[name="skills[]"]:checked');
            if (skillsSelected.length === 0) {
                errors.push('skills');
            }

            const firstName = document.getElementById('first_name').value.trim();
            if (/\d/.test(firstName)) {
                errors.push('first_name_number');
            }

            const lastName = document.getElementById('last_name').value.trim();
            if (/\d/.test(lastName)) {
                errors.push('last_name_number');
            }

            const profilePicture = document.getElementById('profile_picture');
            if (profilePicture.files.length > 0) {
                const file = profilePicture.files[0];
                if (!ALLOWED_PROFILE_TYPES.includes(file.type)) {
                    errors.push('profile_picture_type');
                }
                if (file.size > MAX_PROFILE_BYTES) {
                    errors.push('profile_picture_size');
                }
            }

            document.querySelectorAll('.invalid-feedback').forEach(function (node) {
                node.textContent = '';
            });

            if (errors.length > 0) {
                event.preventDefault();
                errors.forEach(function (field) {
                    let errorId = field + '_error';
                    if (field === 'first_name_number') {
                        errorId = 'first_name_error';
                    }
                    if (field === 'last_name_number') {
                        errorId = 'last_name_error';
                    }
                    if (field === 'profile_picture_type' || field === 'profile_picture_size') {
                        errorId = 'profile_picture_error';
                    }

                    const errorNode = document.getElementById(errorId);
                    if (!errorNode) {
                        return;
                    }

                    if (field === 'first_name_number') {
                        errorNode.textContent = 'First Name must not contain numbers.';
                    } else if (field === 'last_name_number') {
                        errorNode.textContent = 'Last Name must not contain numbers.';
                    } else if (field === 'profile_picture_type') {
                        errorNode.textContent = 'Profile picture must be JPG or PNG.';
                    } else if (field === 'profile_picture_size') {
                        errorNode.textContent = 'Profile picture must be 5 MB or less.';
                    } else if (field === 'skills') {
                        errorNode.textContent = 'Select at least one skill.';
                    } else if (field === 'gender') {
                        errorNode.textContent = 'Gender is required.';
                    } else if (field === 'country') {
                        errorNode.textContent = 'Please select a country.';
                    } else {
                        errorNode.textContent = 'This field is required.';
                    }
                });
            }
        });
    </script>
</body>
</html>
