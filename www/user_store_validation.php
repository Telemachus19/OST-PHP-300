<?php

function password_validation_error(string $password): ?string
{
    if ($password === '') {
        return 'Password is required.';
    }

    if (strlen($password) < 8) {
        return 'Password must be at least 8 characters.';
    }

    if (preg_match('/[A-Z]/', $password) === 1) {
        return 'Password must not contain uppercase letters.';
    }

    if (preg_match('/^[a-z0-9_]+$/', $password) !== 1) {
        return 'Password can contain only lowercase letters, numbers, and underscore.';
    }

    return null;
}

function validate_user_payload(array $values, bool $requirePassword): array
{
    // error keys 
    $errors = [];
    // trim 
    $firstName = trim((string) ($values['first_name'] ?? ''));
    $lastName = trim((string) ($values['last_name'] ?? ''));
    $address = trim((string) ($values['address'] ?? ''));
    $country = trim((string) ($values['country'] ?? ''));
    $gender = trim((string) ($values['gender'] ?? ''));
    $username = trim((string) ($values['username'] ?? ''));
    $department = trim((string) ($values['department'] ?? ''));
    $skills = (array) ($values['skills'] ?? []);

    if ($firstName === '') {
        $errors['first_name'] = 'First Name is required.';
    } elseif (preg_match('/\d/', $firstName) === 1) {
        $errors['first_name'] = 'First Name must not contain numbers.';
    }

    if ($lastName === '') {
        $errors['last_name'] = 'Last Name is required.';
    } elseif (preg_match('/\d/', $lastName) === 1) {
        $errors['last_name'] = 'Last Name must not contain numbers.';
    }

    if ($address === '') {
        $errors['address'] = 'Address is required.';
    }

    if ($country === '' || $country === 'Select Country') {
        $errors['country'] = 'Please select a country.';
    }

    if ($gender !== 'Male' && $gender !== 'Female') {
        $errors['gender'] = 'Gender is required.';
    }

    if ($username === '') {
        $errors['username'] = 'Username is required.';
    }

    if ($department === '') {
        $errors['department'] = 'Department is required.';
    }

    if ($skills === []) {
        $errors['skills'] = 'Select at least one skill.';
    }

    if ($requirePassword) {
        $passwordError = password_validation_error((string) ($values['password'] ?? ''));
        if ($passwordError !== null) {
            $errors['password'] = $passwordError;
        }
    }

    return $errors;
}

function validate_profile_picture_upload(?array $file): ?string
{
    if ($file === null) {
        return null;
    }

    $errorCode = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
    if ($errorCode === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if ($errorCode !== UPLOAD_ERR_OK) {
        return 'Profile picture upload failed.';
    }

    $size = (int) ($file['size'] ?? 0);
    if ($size <= 0) {
        return 'Profile picture upload failed.';
    }

    if ($size > PROFILE_PICTURE_MAX_BYTES) {
        return 'Profile picture must be 5 MB or less.';
    }

    $tmpName = (string) ($file['tmp_name'] ?? '');
    if ($tmpName === '' || !is_uploaded_file($tmpName)) {
        return 'Invalid profile picture upload.';
    }

    $allowedMimeToExt = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
    ];

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = (string) $finfo->file($tmpName);
    if (!isset($allowedMimeToExt[$mimeType])) {
        return 'Profile picture must be JPG or PNG.';
    }

    $originalName = strtolower((string) ($file['name'] ?? ''));
    $extension = pathinfo($originalName, PATHINFO_EXTENSION);
    if (!in_array($extension, ['jpg', 'jpeg', 'png'], true)) {
        return 'Profile picture must be JPG or PNG.';
    }

    if ($mimeType === 'image/png' && $extension !== 'png') {
        return 'Profile picture extension does not match file type.';
    }

    if ($mimeType === 'image/jpeg' && !in_array($extension, ['jpg', 'jpeg'], true)) {
        return 'Profile picture extension does not match file type.';
    }

    return null;
}
