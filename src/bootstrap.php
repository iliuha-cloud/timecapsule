<?php
// Loaded first by every entry point (public/index.php and bin/init-db.php).

define('ROOT_DIR', dirname(__DIR__));

// 1. Composer packages (vendor/ is created by "composer install").
if (!is_file(ROOT_DIR . '/vendor/autoload.php')) {
    $message = 'vendor/ is missing. Run "composer install" in ' . ROOT_DIR . "\n";
    if (PHP_SAPI === 'cli') {
        fwrite(STDERR, $message);
    } else {
        http_response_code(500);
        echo $message;
    }
    exit(1);
}
require ROOT_DIR . '/vendor/autoload.php';

// 2. Settings: load .env if it exists (phpdotenv). "Immutable" means that variables
//    which are already set in the real environment are never overwritten by the file.
//    safeLoad() does nothing when there is no .env file (e.g. in Docker).
Dotenv\Dotenv::createImmutable(ROOT_DIR)->safeLoad();

// All dates in the app and in the database are UTC.
date_default_timezone_set('UTC');

// 3. Our own code: plain files full of functions, loaded in this order.
require __DIR__ . '/helpers.php';
require __DIR__ . '/db.php';
require __DIR__ . '/csrf.php';
require __DIR__ . '/auth.php';
require __DIR__ . '/storage.php';
require __DIR__ . '/mailer.php';
require __DIR__ . '/capsules.php';
require ROOT_DIR . '/views/partials/icons.php';

if (PHP_SAPI !== 'cli') {
    // Any uncaught error in a web request shows our 500 page instead of a PHP stack trace.
    set_exception_handler(function (Throwable $e): void {
        error_log((string) $e);
        abort(500, db_problem_hint($e), config_bool('APP_DEBUG') ? $e->getMessage() : null);
    });
}

// Sessions are plain files in storage/sessions/.
function start_session(): void
{
    session_save_path(ROOT_DIR . '/storage/sessions');
    session_name('timecapsule_session');
    session_set_cookie_params([
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}
