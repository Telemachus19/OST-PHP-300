<?php
session_start();
require_once __DIR__ . '/user_store.php';

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
        'captcha' => trim($_POST['captcha'] ?? ''),
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

    if (trim($_POST['password'] ?? '') === '') {
        $errors['password'] = 'Password is required.';
    }

    if ($values['department'] === '') {
        $errors['department'] = 'Department is required.';
    }

    if ($values['captcha'] === '') {
        $errors['captcha'] = 'CAPTCHA is required.';
    } elseif (strcasecmp($values['captcha'], $captchaCode) !== 0) {
        $errors['captcha'] = 'CAPTCHA does not match.';
    }

    if ($errors === []) {
        try {
            add_user([
                'first_name' => $values['first_name'],
                'last_name' => $values['last_name'],
                'address' => $values['address'],
                'country' => $values['country'],
                'gender' => $values['gender'],
                'skills' => $values['skills'],
                'username' => $values['username'],
                'department' => $values['department'],
            ]);
            unset($_SESSION['captcha_code']);
            unset($_SESSION['registration_form']);
            header('Location: listUsers.php');
            exit;
        } catch (InvalidArgumentException $e) {
            $errors['username'] = $e->getMessage();
        }
    }

    $_SESSION['registration_form'] = $values;
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

        <form id="registrationForm" method="post" action="registration.php" novalidate class="card card-body bg-white">
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
            </div>

            <div class="mb-3">
                <label for="department" class="form-label">Department</label>
                <input type="text" class="form-control <?php echo isset($errors['department']) ? 'is-invalid' : ''; ?>" id="department" name="department" placeholder="OpenSource" value="<?php echo h($values['department']); ?>" required>
                <div class="invalid-feedback d-block" id="department_error"><?php echo h($errors['department'] ?? ''); ?></div>
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
                    const errorNode = document.getElementById(field + '_error');
                    if (errorNode) {
                        if (field === 'captcha') {
                            errorNode.textContent = 'CAPTCHA does not match.';
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
