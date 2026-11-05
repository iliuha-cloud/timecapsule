<?php
// S3 storage for TimeCapsule attachments (Lab 4, Task 11).
// The rest of the application continues to call storage_save(),
// storage_delete() and storage_stream() exactly as before.
//
// The AWS SDK uses the EC2 instance role automatically. Do not configure
// AWS_ACCESS_KEY_ID or AWS_SECRET_ACCESS_KEY in the application .env.

/** Return a shared S3 client, using the EC2 instance role for credentials. */
function storage_s3_client(): \Aws\S3\S3Client
{
    static $client = null;

    if ($client === null) {
        // ROOT_DIR is defined by the application's bootstrap (as in the original module).
        require_once ROOT_DIR . '/vendor/autoload.php';

        $client = new \Aws\S3\S3Client([
            'version' => 'latest',
            'region' => config('AWS_REGION', 'eu-central-1'),
        ]);
    }

    return $client;
}

/** Get the configured S3 bucket. */
function storage_s3_bucket(): string
{
    $bucket = trim((string) config('S3_BUCKET', ''));
    if ($bucket === '') {
        throw new RuntimeException('S3_BUCKET is not configured.');
    }

    return $bucket;
}

/** Convert the stored filename to an S3 key without allowing path traversal. */
function storage_s3_key(string $storedName): string
{
    $name = basename(str_replace('\\', '/', $storedName));
    if ($name === '' || $name === '.' || $name === '..') {
        throw new InvalidArgumentException('Invalid stored file name.');
    }

    return 'uploads/' . $name;
}

/**
 * Save an uploaded file to S3 and return its random stored filename.
 * The database continues to store only the filename, not the S3 key or URL.
 */
function storage_save(array $uploadedFile, string $extension): string
{
    $storedName = bin2hex(random_bytes(16)) . '.' . $extension;
    $tmpName = $uploadedFile['tmp_name'] ?? '';

    if (!is_string($tmpName) || !is_uploaded_file($tmpName)) {
        throw new RuntimeException('Could not read the uploaded file.');
    }

    $contentType = mime_content_type($tmpName) ?: 'application/octet-stream';

    storage_s3_client()->putObject([
        'Bucket' => storage_s3_bucket(),
        'Key' => storage_s3_key($storedName),
        'SourceFile' => $tmpName,
        'ContentType' => $contentType,
    ]);

    return $storedName;
}

/** Delete the object from S3. With versioning enabled, S3 creates a delete marker. */
function storage_delete(?string $storedName): void
{
    if ($storedName === null || $storedName === '') {
        return;
    }

    storage_s3_client()->deleteObject([
        'Bucket' => storage_s3_bucket(),
        'Key' => storage_s3_key($storedName),
    ]);
}

/**
 * Redirect the browser to a 5-minute presigned S3 GetObject URL.
 * Access checks for the capsule must happen in the calling application.
 * $disposition is "inline" (preview) or "attachment" (download).
 */
function storage_stream(string $storedName, string $mime, string $downloadName, string $disposition): void
{
    $disposition = ($disposition === 'inline') ? 'inline' : 'attachment';

    // Preserve the original filename in Content-Disposition, including UTF-8.
    // Remove line breaks to avoid injecting response headers.
    $downloadName = str_replace(["\r", "\n"], '', $downloadName);
    $asciiName = preg_replace('/[^A-Za-z0-9._ -]/', '_', $downloadName);
    if ($asciiName === '') {
        $asciiName = 'download';
    }

    $contentDisposition = sprintf(
        '%s; filename="%s"; filename*=UTF-8\'\'%s',
        $disposition,
        $asciiName,
        rawurlencode($downloadName)
    );

    $command = storage_s3_client()->getCommand('GetObject', [
        'Bucket' => storage_s3_bucket(),
        'Key' => storage_s3_key($storedName),
        'ResponseContentType' => str_replace(["\r", "\n"], '', $mime),
        'ResponseContentDisposition' => $contentDisposition,
    ]);

    $request = storage_s3_client()->createPresignedRequest($command, '+5 minutes');

    header('Cache-Control: private, no-store');
    header('Location: ' . (string) $request->getUri(), true, 302);
    exit;
}
