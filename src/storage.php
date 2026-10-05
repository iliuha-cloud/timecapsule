<?php
// File storage for capsule attachments.
//
// This is the only file that reads or writes uploaded files. They are kept on the local
// disk, in UPLOAD_DIR. The rest of the app only uses the three functions
// storage_save(), storage_delete() and storage_stream().

// Absolute path of the upload folder. A relative UPLOAD_DIR is relative to the project root.
function upload_dir(): string
{
    $dir = config('UPLOAD_DIR', 'storage/uploads');
    // Absolute paths look like "/var/uploads" (Linux, macOS) or "C:\uploads" (Windows).
    if (!preg_match('#^([A-Za-z]:)?[\\\\/]#', $dir)) {
        $dir = ROOT_DIR . '/' . $dir;
    }

    if (!is_dir($dir)) {
        mkdir($dir, 0775, true);
    }
    return rtrim($dir, '/\\');
}

// Save an uploaded file ($_FILES['...'] entry) and return its stored name, e.g. "3f9a...e1.png".
// The name is random, so users cannot guess other people's file names.
function storage_save(array $uploadedFile, string $extension): string
{
    $storedName = bin2hex(random_bytes(16)) . '.' . $extension;

    if (!move_uploaded_file($uploadedFile['tmp_name'], upload_dir() . '/' . $storedName)) {
        throw new RuntimeException('Could not save the uploaded file.');
    }
    return $storedName;
}

function storage_delete(?string $storedName): void
{
    if ($storedName === null || $storedName === '') {
        return;
    }

    // basename() makes sure we never delete something outside the upload folder.
    $path = upload_dir() . '/' . basename($storedName);
    if (is_file($path)) {
        unlink($path);
    }
}

// Send a stored file to the browser.
// $disposition: "inline" (show in the browser) or "attachment" (download).
function storage_stream(string $storedName, string $mime, string $downloadName, string $disposition): void
{
    $path = upload_dir() . '/' . basename($storedName);
    if (!is_file($path)) {
        abort(404);
    }

    // Keep only safe characters for the plain filename; the UTF-8 name goes in filename*.
    $asciiName = preg_replace('/[^A-Za-z0-9._ -]/', '_', $downloadName);

    header('Content-Type: ' . $mime);
    header('Content-Length: ' . filesize($path));
    header(sprintf(
        'Content-Disposition: %s; filename="%s"; filename*=UTF-8\'\'%s',
        $disposition,
        $asciiName,
        rawurlencode($downloadName)
    ));
    header('X-Content-Type-Options: nosniff');

    readfile($path);
}
