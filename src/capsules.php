<?php
// Capsule database queries, access rules and form validation.

// File types we accept. The key is the MIME type detected from the file CONTENT,
// the value is the extension we save the file with.
const ALLOWED_MIME_TYPES = [
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
    'image/gif' => 'gif',
    'image/webp' => 'webp',
    'application/pdf' => 'pdf',
];

// ---------- queries ----------

function capsule_find(int $id): ?array
{
    $capsule = db_query('SELECT * FROM capsules WHERE id = ?', [$id])->fetch();
    return $capsule ?: null;
}

function capsules_for_user(int $userId): array
{
    return db_query(
        'SELECT * FROM capsules WHERE user_id = ? ORDER BY open_at ASC, id ASC',
        [$userId]
    )->fetchAll();
}

// Opened public capsules from all users, newest first.
function capsules_on_wall(): array
{
    return db_query(
        'SELECT c.*, u.email AS author_email
           FROM capsules c
           JOIN users u ON u.id = c.user_id
          WHERE c.opened_at IS NOT NULL AND c.is_public
          ORDER BY c.opened_at DESC
          LIMIT 50'
    )->fetchAll();
}

// Insert a capsule and return it as it is stored in the database.
function capsule_create(array $data): array
{
    return db_query(
        'INSERT INTO capsules (user_id, title, message, open_at, is_public, recipient_email, file_path, file_name, file_mime)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
         RETURNING *',
        [
            $data['user_id'],
            $data['title'],
            $data['message'],
            $data['open_at_utc']->format('Y-m-d H:i:s'), // UTC
            $data['is_public'] ? 'true' : 'false',
            $data['recipient_email'] !== '' ? $data['recipient_email'] : null,
            $data['file_path'],
            $data['file_name'],
            $data['file_mime'],
        ]
    )->fetch();
}

function capsule_delete(int $id): void
{
    db_query('DELETE FROM capsules WHERE id = ?', [$id]);
}

// Open every capsule whose open time has passed and add an "is now open" notification.
// Called at the start of every web request (see public/index.php).
//
// One UPDATE both opens the capsules and returns them. It only returns the rows it
// changed ("opened_at IS NULL"), so two requests running at the same time never open
// (and notify about) the same capsule twice.
function open_due_capsules(): void
{
    $now = now_utc();

    $opened = db_query(
        'UPDATE capsules c
            SET opened_at = ?
           FROM users u
          WHERE u.id = c.user_id AND c.opened_at IS NULL AND c.open_at <= ?
      RETURNING c.id, c.user_id, c.title, c.recipient_email, u.email',
        [$now, $now]
    )->fetchAll();

    foreach ($opened as $capsule) {
        notify_insert(
            (int) $capsule['id'],
            (int) $capsule['user_id'],
            $capsule['recipient_email'] ?? $capsule['email'],
            sprintf('Your capsule "%s" is now open.', $capsule['title'])
        );
    }
}

// ---------- state and access rules ----------

// A capsule is opened only when open_due_capsules() has processed it.
function capsule_is_opened(array $capsule): bool
{
    return $capsule['opened_at'] !== null;
}

function capsule_is_owner(array $capsule, ?array $user): bool
{
    return $user !== null && (int) $capsule['user_id'] === (int) $user['id'];
}

// Owners always see their capsule; everybody else only opened public capsules.
function capsule_can_view(array $capsule, ?array $user): bool
{
    return capsule_is_owner($capsule, $user)
        || ($capsule['is_public'] && capsule_is_opened($capsule));
}

function capsule_is_image(array $capsule): bool
{
    return $capsule['file_mime'] !== null && str_starts_with($capsule['file_mime'], 'image/');
}

// ---------- validation ----------

// Validate the "New capsule" form.
// Returns [$data, $errors, $upload]:
//   $data   - cleaned values (also used to re-fill the form)
//   $errors - ['field' => 'first error message']
//   $upload - null or ['file' => $_FILES entry, 'mime' => ..., 'extension' => ...]
function capsule_validate(array $input, ?array $file): array
{
    $data = [
        'title' => trim((string) ($input['title'] ?? '')),
        'message' => trim((string) ($input['message'] ?? '')),
        'open_at' => trim((string) ($input['open_at'] ?? '')), // local time, re-fills the form
        'open_at_utc' => parse_open_at(
            trim((string) ($input['open_at_utc'] ?? '')),
            trim((string) ($input['open_at'] ?? ''))
        ),
        'recipient_email' => trim((string) ($input['recipient_email'] ?? '')),
        'is_public' => !empty($input['is_public']),
    ];
    $errors = [];

    if ($data['title'] === '') {
        $errors['title'] = 'Enter a title.';
    } elseif (mb_strlen($data['title']) > 120) {
        $errors['title'] = 'Title must be at most 120 characters.';
    }

    if ($data['message'] === '') {
        $errors['message'] = 'Write a message.';
    } elseif (mb_strlen($data['message']) > 5000) {
        $errors['message'] = 'Message must be at most 5000 characters.';
    }

    if ($data['open_at_utc'] === null) {
        $errors['open_at'] = 'Choose an open date and time.';
    } elseif ($data['open_at_utc']->getTimestamp() <= time()) {
        $errors['open_at'] = 'The open time must be in the future.';
    }

    if ($data['recipient_email'] !== '' && !filter_var($data['recipient_email'], FILTER_VALIDATE_EMAIL)) {
        $errors['recipient_email'] = 'Enter a valid email address.';
    }

    [$upload, $fileError] = validate_upload($file);
    if ($fileError !== null) {
        $errors['attachment'] = $fileError;
    }

    return [$data, $errors, $upload];
}

// The moment a capsule opens, in UTC, with the seconds set to :00. Null if it is missing or invalid.
//   $utc   - "open_at_utc", filled by public/js/app.js, e.g. "2027-01-01T09:30:00.000Z"
//   $local - "open_at", e.g. "2027-01-01T10:30". Only used when JavaScript is off; the
//            server does not know the visitor's time zone, so the value is read as UTC.
function parse_open_at(string $utc, string $local): ?DateTimeImmutable
{
    return parse_utc_minute($utc, '/^(\d{4})-(\d{2})-(\d{2})T(\d{2}):(\d{2})(?::(\d{2})(?:\.\d{1,3})?)?Z$/')
        ?? parse_utc_minute($local, '/^(\d{4})-(\d{2})-(\d{2})T(\d{2}):(\d{2})$/');
}

// Read $value (matched by $pattern: year, month, day, hour, minute, optional second) as UTC.
function parse_utc_minute(string $value, string $pattern): ?DateTimeImmutable
{
    if (!preg_match($pattern, $value, $parts)) {
        return null;
    }

    [$year, $month, $day, $hour, $minute] = array_map('intval', array_slice($parts, 1, 5));
    $second = (int) ($parts[6] ?? 0);
    // Reject values like 2026-02-31 or 25:00 instead of letting PHP roll them over.
    if (!checkdate($month, $day, $year) || $hour > 23 || $minute > 59 || $second > 59) {
        return null;
    }

    return (new DateTimeImmutable('now', new DateTimeZone('UTC')))
        ->setDate($year, $month, $day)
        ->setTime($hour, $minute); // seconds are dropped
}

// Returns [$upload, $errorMessage]. Both are null when no file was chosen.
function validate_upload(?array $file): array
{
    if ($file === null || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return [null, null];
    }

    $maxMb = max_upload_mb();
    $tooBig = "File must be at most $maxMb MB.";

    // PHP itself rejects files above upload_max_filesize (php.ini).
    if ($file['error'] === UPLOAD_ERR_INI_SIZE || $file['error'] === UPLOAD_ERR_FORM_SIZE) {
        return [null, $tooBig];
    }
    if ($file['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'])) {
        return [null, 'Upload failed. Please try again.'];
    }
    if ($file['size'] > $maxMb * 1024 * 1024) {
        return [null, $tooBig];
    }

    // Never trust the file name or the browser's Content-Type: look at the bytes instead.
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    if (!isset(ALLOWED_MIME_TYPES[$mime])) {
        return [null, 'File must be JPG, PNG, GIF, WEBP or PDF.'];
    }

    return [['file' => $file, 'mime' => $mime, 'extension' => ALLOWED_MIME_TYPES[$mime]], null];
}

function max_upload_mb(): int
{
    return config_int('MAX_UPLOAD_MB', 5);
}
