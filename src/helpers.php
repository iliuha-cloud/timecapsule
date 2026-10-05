<?php
// Small helper functions used everywhere.

// ---------- configuration ----------

// Read a setting (see .env.example for the list).
//
// Values can come from two places:
//   - real environment variables (docker-compose, the shell, ...), read with getenv();
//   - the optional .env file, which phpdotenv loads into $_ENV / $_SERVER.
// getenv() is checked first, so a real environment variable always wins over .env.
// An empty value ("DB_HOST=") means "use the default".
function config(string $key, ?string $default = null): ?string
{
    $value = getenv($key);
    if ($value === false) {
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? null;
    }
    return (is_string($value) && $value !== '') ? $value : $default;
}

function config_bool(string $key, bool $default = false): bool
{
    $value = config($key);
    return $value === null ? $default : filter_var($value, FILTER_VALIDATE_BOOLEAN);
}

function config_int(string $key, int $default): int
{
    $value = config($key);
    return is_numeric($value) ? (int) $value : $default;
}

// ---------- output ----------

// Escape text for HTML. Use it for EVERY value printed in a view.
function e(?string $text): string
{
    return htmlspecialchars((string) $text, ENT_QUOTES, 'UTF-8');
}

function redirect(string $path): never
{
    header('Location: ' . $path, true, 302);
    exit;
}

// Render views/{template}.php inside views/layout.php.
// $data keys become variables in the template, e.g. ['capsule' => ...] gives $capsule.
function view(string $template, array $data = [], int $status = 200): void
{
    http_response_code($status);
    $content = render_template($template, $data);
    echo render_template('layout', $data + ['content' => $content]);
}

function render_template(string $template, array $data = []): string
{
    extract($data);
    ob_start();
    require ROOT_DIR . '/views/' . $template . '.php';
    return ob_get_clean();
}

// Stop the request and show an error page (403, 404 or 500).
function abort(int $code, ?string $text = null, ?string $details = null): never
{
    // Throw away anything a half-rendered template already produced.
    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    $pages = [
        403 => ['Access denied', 'This capsule is still sealed.'],
        404 => ['Page not found', 'The page or capsule does not exist.'],
        500 => ['Something went wrong', 'Please try again later.'],
    ];
    [$heading, $defaultText] = $pages[$code] ?? $pages[500];

    view('error', [
        'title' => $heading,
        'code' => $code,
        'heading' => $heading,
        'text' => $text ?? $defaultText,
        'details' => $details, // only filled when APP_DEBUG=true
    ], $code);
    exit;
}

// ---------- flash messages (shown once, on the next rendered page) ----------

function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message]; // type: success | error
}

function take_flashes(): array
{
    $messages = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $messages;
}

// ---------- formatting ----------

// A moment from the database ("2027-01-01 10:00:00", UTC) or a DateTime object as HTML:
//   <time data-local datetime="2027-01-01T10:00:00Z">1 Jan 2027, 10:00 UTC</time>
// The text is UTC; public/js/app.js replaces it with the visitor's local time.
function time_tag(string|DateTimeInterface $utc): string
{
    $moment = to_utc($utc);
    return '<time data-local datetime="' . e($moment->format('Y-m-d\\TH:i:s\\Z')) . '">'
        . e(format_utc($moment)) . '</time>';
}

// Plain text for e-mails and notifications: "1 Jan 2027, 10:00 UTC"
function format_utc(string|DateTimeInterface $utc): string
{
    return to_utc($utc)->format('j M Y, H:i') . ' UTC';
}

function to_utc(string|DateTimeInterface $utc): DateTimeImmutable
{
    $timezone = new DateTimeZone('UTC');
    if ($utc instanceof DateTimeInterface) {
        return DateTimeImmutable::createFromInterface($utc)->setTimezone($timezone);
    }
    return new DateTimeImmutable($utc, $timezone);
}

// Text shown for a capsule that is not opened yet.
function countdown(string|DateTimeInterface $openAt): string
{
    $diff = to_utc($openAt)->getTimestamp() - time();
    if ($diff <= 0) {
        // Fallback: the time has passed, but no request has opened the capsule yet.
        return 'Opening soon…';
    }

    if ($diff < 3600) {
        [$count, $unit] = [(int) ceil($diff / 60), 'minute'];
    } elseif ($diff < 86400) {
        [$count, $unit] = [intdiv($diff, 3600), 'hour'];
    } else {
        [$count, $unit] = [intdiv($diff, 86400), 'day'];
    }
    return "Opens in $count $unit" . ($count === 1 ? '' : 's');
}

// First 120 characters of a message. mb_* functions count characters, not bytes.
function excerpt(string $message, int $length = 120): string
{
    return mb_strlen($message) > $length ? mb_substr($message, 0, $length) . '…' : $message;
}

// "anna@example.com" -> "anna"
function author_name(string $email): string
{
    return explode('@', $email, 2)[0];
}

// ---------- form field helpers (errors = ['field' => 'message']) ----------

function field_class(array $errors, string $name): string
{
    return isset($errors[$name]) ? 'field has-error' : 'field';
}

// Extra attributes for an <input>: marks it invalid and links the error/hint text to it.
function field_aria(array $errors, string $name, bool $hasHint = false): string
{
    $describedBy = [];
    if ($hasHint) {
        $describedBy[] = $name . '-hint';
    }
    if (isset($errors[$name])) {
        $describedBy[] = $name . '-error';
    }

    $html = isset($errors[$name]) ? ' aria-invalid="true"' : '';
    if ($describedBy) {
        $html .= ' aria-describedby="' . e(implode(' ', $describedBy)) . '"';
    }
    return $html;
}

function field_error(array $errors, string $name): string
{
    if (!isset($errors[$name])) {
        return '';
    }
    return '<span class="error" id="' . e($name) . '-error">' . e($errors[$name]) . '</span>';
}
