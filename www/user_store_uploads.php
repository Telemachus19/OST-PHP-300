<?php

function store_profile_picture(?array $file): ?string
{
    if ($file === null || (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    $uploadError = validate_profile_picture_upload($file);
    if ($uploadError !== null) {
        throw new InvalidArgumentException($uploadError);
    }

    if (!is_dir(PROFILE_PICTURE_UPLOAD_DIR) && !mkdir(PROFILE_PICTURE_UPLOAD_DIR, 0775, true) && !is_dir(PROFILE_PICTURE_UPLOAD_DIR)) {
        throw new RuntimeException('Unable to create upload directory.');
    }

    $tmpName = (string) $file['tmp_name'];
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = (string) $finfo->file($tmpName);
    $extension = $mimeType === 'image/png' ? 'png' : 'jpg';
    $filename = 'profile_' . bin2hex(random_bytes(16)) . '.' . $extension;
    $destination = PROFILE_PICTURE_UPLOAD_DIR . '/' . $filename;

    if (!move_uploaded_file($tmpName, $destination)) {
        throw new RuntimeException('Failed to save profile picture.');
    }

    return PROFILE_PICTURE_WEB_DIR . $filename;
}

function delete_profile_picture(?string $relativePath): void
{
    if ($relativePath === null || $relativePath === '') {
        return;
    }

    $uploadsDir = realpath(PROFILE_PICTURE_UPLOAD_DIR);
    if ($uploadsDir === false) {
        return;
    }

    $fullPath = realpath(__DIR__ . '/' . ltrim($relativePath, '/'));
    if ($fullPath === false) {
        return;
    }

    if (strpos($fullPath, $uploadsDir) !== 0) {
        return;
    }

    if (is_file($fullPath)) {
        unlink($fullPath);
    }
}
