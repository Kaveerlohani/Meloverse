<?php
declare(strict_types=1);

/**
 * Local-first storage. Point storage.public_base_url in config to a CDN/S3 public URL
 * after syncing uploaded files to your bucket for production cloud delivery.
 */
function meloverse_save_audio_upload(array $file): array
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Upload failed.');
    }
    $tmp = (string) $file['tmp_name'];
    $name = (string) $file['name'];
    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    $allowedExt = ['mp3', 'wav'];
    if (!in_array($ext, $allowedExt, true)) {
        throw new RuntimeException('Only MP3 and WAV files are allowed.');
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($tmp) ?: '';
    $allowedMime = [
        'audio/mpeg',
        'audio/wave',
        'audio/wav',
        'audio/x-wav',
    ];
    if (!in_array($mime, $allowedMime, true)) {
        throw new RuntimeException('Invalid audio file type.');
    }

    $destDir = dirname(__DIR__) . '/uploads/audio';
    if (!is_dir($destDir) && !mkdir($destDir, 0755, true) && !is_dir($destDir)) {
        throw new RuntimeException('Could not create upload directory.');
    }

    $safe = bin2hex(random_bytes(16)) . '.' . $ext;
    $destPath = $destDir . '/' . $safe;
    if (!move_uploaded_file($tmp, $destPath)) {
        throw new RuntimeException('Could not store file.');
    }

    $relative = 'uploads/audio/' . $safe;
    return [
        'relative_path' => $relative,
        'filename' => $name,
        'mime' => $mime,
        'absolute_path' => $destPath,
    ];
}

function meloverse_save_avatar_upload(array $file): string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Avatar upload failed.');
    }
    $tmp = (string) $file['tmp_name'];
    $ext = strtolower(pathinfo((string) $file['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
    if (!in_array($ext, $allowed, true)) {
        throw new RuntimeException('Avatar must be JPG, PNG, WEBP, or GIF.');
    }
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($tmp) ?: '';
    $okMime = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    if (!in_array($mime, $okMime, true)) {
        throw new RuntimeException('Invalid image type.');
    }

    $destDir = dirname(__DIR__) . '/uploads/avatars';
    if (!is_dir($destDir) && !mkdir($destDir, 0755, true) && !is_dir($destDir)) {
        throw new RuntimeException('Could not create avatar directory.');
    }
    $safe = bin2hex(random_bytes(12)) . '.' . $ext;
    $destPath = $destDir . '/' . $safe;
    if (!move_uploaded_file($tmp, $destPath)) {
        throw new RuntimeException('Could not store avatar.');
    }
    return 'uploads/avatars/' . $safe;
}

function meloverse_delete_file_if_exists(?string $relative): void
{
    if (!$relative) {
        return;
    }
    $path = dirname(__DIR__) . '/' . ltrim(str_replace(['..', '\\'], ['', '/'], $relative), '/');
    if (is_file($path)) {
        @unlink($path);
    }
}
