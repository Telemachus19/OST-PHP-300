<?php
session_start();
require_once __DIR__ . '/user_store.php';

$loggedInUsername = current_username();

$captchaCode = $_SESSION['captcha_code'] ?? '';
if ($captchaCode === '') {
    $captchaCode = strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
    $_SESSION['captcha_code'] = $captchaCode;
}

$countries = countries();
$skillOptions = skill_options();
$defaults = [
    'first_name' => '',
    'last_name' => '',
    'address' => '',
    'country' => 'Select Country',
    'gender' => '',
    'skills' => ['J2SE', 'MySQL'],
    'username' => '',
    'department' => '',
    'password' => '',
    'captcha' => '',
];

$values = array_merge($defaults, $_SESSION['registration_form'] ?? []);
$values['skills'] = array_values(array_intersect($skillOptions, (array) ($values['skills'] ?? [])));
if ($values['skills'] === []) {
    $values['skills'] = ['J2SE', 'MySQL'];
}

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
        'password' => (string) ($_POST['password'] ?? ''),
        'captcha' => trim($_POST['captcha'] ?? ''),
    ];

    $errors = validate_user_payload($values, true);

    $uploadError = validate_profile_picture_upload($_FILES['profile_picture'] ?? null);
    if ($uploadError !== null) {
        $errors['profile_picture'] = $uploadError;
    }

    if ($values['captcha'] === '') {
        $errors['captcha'] = 'CAPTCHA is required.';
    } elseif (strcasecmp($values['captcha'], $captchaCode) !== 0) {
        $errors['captcha'] = 'CAPTCHA does not match.';
    }

    if ($errors === []) {
        $profilePicturePath = null;
        try {
            $profilePicturePath = store_profile_picture($_FILES['profile_picture'] ?? null);

            add_user([
                'first_name' => $values['first_name'],
                'last_name' => $values['last_name'],
                'address' => $values['address'],
                'country' => $values['country'],
                'gender' => $values['gender'],
                'skills' => $values['skills'],
                'username' => $values['username'],
                'department' => $values['department'],
                'password' => $values['password'],
                'profile_picture_path' => $profilePicturePath,
            ]);
            unset($_SESSION['captcha_code']);
            unset($_SESSION['registration_form']);
            header('Location: login.php');
            exit;
        } catch (InvalidArgumentException $e) {
            if ($profilePicturePath !== null) {
                delete_profile_picture($profilePicturePath);
            }

            if ($e->getMessage() === 'Username already exists.') {
                $errors['username'] = $e->getMessage();
            } else {
                $errors['password'] = $e->getMessage();
            }
        } catch (RuntimeException $e) {
            if ($profilePicturePath !== null) {
                delete_profile_picture($profilePicturePath);
            }

            $errors['profile_picture'] = $e->getMessage();
        }
    }

    $sessionValues = $values;
    $sessionValues['password'] = '';
    $_SESSION['registration_form'] = $sessionValues;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration Form</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container py-4">
        <?php if ($loggedInUsername !== null): ?>
            <div class="d-flex justify-content-end mb-3">
                <div class="d-flex align-items-center gap-2">
                    <span class="text-muted">Logged in as <strong><?php echo h($loggedInUsername); ?></strong></span>
                    <a class="btn btn-sm btn-outline-danger" href="logout.php">Logout</a>
                </div>
            </div>
        <?php endif; ?>

        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1 class="h3 mb-0">Registration Form</h1>
            <a class="btn btn-outline-secondary" href="listUsers.php">Users List</a>
        </div>
        <p class="text-muted">All fields are required. Please complete the form and match the CAPTCHA.</p>

        <?php if ($errors !== []): ?>
            <div class="alert alert-danger">
                Please fix the highlighted errors and submit again.
            </div>
        <?php endif; ?>

        <form id="registrationForm" method="post" action="registration.php" enctype="multipart/form-data" novalidate class="card card-body bg-white">
            <div class="mb-3">
                <label for="first_name" class="form-label">First Name</label>
                <input type="text" class="form-control <?php echo isset($errors['first_name']) ? 'is-invalid' : ''; ?>" id="first_name" name="first_name" value="<?php echo h($values['first_name']); ?>" required>
                <div class="invalid-feedback d-block" id="first_name_error"><?php echo h($errors['first_name'] ?? ''); ?></div>
            </div>

            <div class="mb-3">
                <label for="last_name" class="form-label">Last Name</label>
                <input type="text" class="form-control <?php echo isset($errors['last_name']) ? 'is-invalid' : ''; ?>" id="last_name" name="last_name" value="<?php echo h($values['last_name']); ?>" required>
                <div class="invalid-feedback d-block" id="last_name_error"><?php echo h($errors['last_name'] ?? ''); ?></div>
            </div>

            <div class="mb-3">
                <label for="address" class="form-label">Address</label>
                <textarea class="form-control <?php echo isset($errors['address']) ? 'is-invalid' : ''; ?>" id="address" name="address" required><?php echo h($values['address']); ?></textarea>
                <div class="invalid-feedback d-block" id="address_error"><?php echo h($errors['address'] ?? ''); ?></div>
            </div>

            <div class="mb-3">
                <label for="country" class="form-label">Country</label>
                <select class="form-select <?php echo isset($errors['country']) ? 'is-invalid' : ''; ?>" id="country" name="country" required>
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
                <input type="text" class="form-control <?php echo isset($errors['username']) ? 'is-invalid' : ''; ?>" id="username" name="username" value="<?php echo h($values['username']); ?>" required>
                <div class="invalid-feedback d-block" id="username_error"><?php echo h($errors['username'] ?? ''); ?></div>
            </div>

            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input type="password" class="form-control <?php echo isset($errors['password']) ? 'is-invalid' : ''; ?>" id="password" name="password" required>
                <div class="invalid-feedback d-block" id="password_error"><?php echo h($errors['password'] ?? ''); ?></div>
                <div class="form-text">Minimum 8 characters. Allowed: lowercase letters, numbers, underscore.</div>
            </div>

            <div class="mb-3">
                <label for="department" class="form-label">Department</label>
                <input type="text" class="form-control <?php echo isset($errors['department']) ? 'is-invalid' : ''; ?>" id="department" name="department" placeholder="OpenSource" value="<?php echo h($values['department']); ?>" required>
                <div class="invalid-feedback d-block" id="department_error"><?php echo h($errors['department'] ?? ''); ?></div>
            </div>

            <div class="mb-3">
                <label for="profile_picture" class="form-label">Profile Picture</label>
                <input type="file" class="form-control <?php echo isset($errors['profile_picture']) ? 'is-invalid' : ''; ?>" id="profile_picture" name="profile_picture" accept=".jpg,.jpeg,.png,image/jpeg,image/png">
                <div class="invalid-feedback d-block" id="profile_picture_error"><?php echo h($errors['profile_picture'] ?? ''); ?></div>
                <div class="form-text">Optional. JPG or PNG only. Maximum 5 MB.</div>
            </div>

            <div class="mb-3">
                <label class="form-label">CAPTCHA</label>
                <div class="alert alert-secondary py-2 mb-2 text-center fw-bold"><?php echo h($captchaCode); ?></div>
                <p class="text-muted small">Please insert the code in the box below.</p>
                <input type="text" class="form-control <?php echo isset($errors['captcha']) ? 'is-invalid' : ''; ?>" id="captcha" name="captcha" value="<?php echo h($values['captcha']); ?>" required>
                <div class="invalid-feedback d-block" id="captcha_error"><?php echo h($errors['captcha'] ?? ''); ?></div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">Submit</button>
                <button type="reset" class="btn btn-outline-secondary">Reset</button>
            </div>
        </form>
    </div>

    <script>
        const MAX_PROFILE_BYTES = 5 * 1024 * 1024;
        const ALLOWED_PROFILE_TYPES = ['image/jpeg', 'image/png'];

        document.getElementById('registrationForm').addEventListener('submit', function (event) {
            const errors = [];
            const requiredFields = ['first_name', 'last_name', 'address', 'username', 'password', 'department', 'captcha'];

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

            const password = document.getElementById('password').value;
            if (password.length > 0) {
                if (password.length < 8) {
                    errors.push('password_length');
                }
                if (/[A-Z]/.test(password)) {
                    errors.push('password_uppercase');
                }
                if (!/^[a-z0-9_]+$/.test(password)) {
                    errors.push('password_charset');
                }
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

            const captchaValue = document.getElementById('captcha').value.trim();
            const captchaCode = document.querySelector('.alert.alert-secondary').textContent.trim();
            if (captchaValue !== captchaCode) {
                errors.push('captcha');
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
                    if (field === 'password_length' || field === 'password_uppercase' || field === 'password_charset') {
                        errorId = 'password_error';
                    }
                    if (field === 'profile_picture_type' || field === 'profile_picture_size') {
                        errorId = 'profile_picture_error';
                    }

                    const errorNode = document.getElementById(errorId);
                    if (errorNode) {
                        if (field === 'captcha') {
                            errorNode.textContent = 'CAPTCHA does not match.';
                        } else if (field === 'first_name_number') {
                            errorNode.textContent = 'First Name must not contain numbers.';
                        } else if (field === 'last_name_number') {
                            errorNode.textContent = 'Last Name must not contain numbers.';
                        } else if (field === 'password_length') {
                            errorNode.textContent = 'Password must be at least 8 characters.';
                        } else if (field === 'password_uppercase') {
                            errorNode.textContent = 'Password must not contain uppercase letters.';
                        } else if (field === 'password_charset') {
                            errorNode.textContent = 'Password can contain only lowercase letters, numbers, and underscore.';
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
                    }
                });
            }
        });
    </script>
</body>
</html>
