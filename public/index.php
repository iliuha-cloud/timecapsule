<?php
// Front controller: EVERY request (except static files in public/: /css, /js, /vendor) ends up here.
// Apache: "FallbackResource /index.php"; nginx: "try_files $uri /index.php";
// PHP built-in server: serves existing files itself and sends everything else here.

// When this file is used as a router script ("php -S localhost:8080 -t public public/index.php"),
// let the built-in server send existing files such as /js/app.js itself.
if (PHP_SAPI === 'cli-server') {
    $file = __DIR__ . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    if ($file !== __DIR__ . '/' && is_file($file)) {
        return false;
    }
}

require dirname(__DIR__) . '/src/bootstrap.php';
require ROOT_DIR . '/src/controllers/capsules.php';
require ROOT_DIR . '/src/controllers/auth.php';
require ROOT_DIR . '/src/controllers/wall.php';
require ROOT_DIR . '/src/controllers/notifications.php';
require ROOT_DIR . '/src/controllers/health.php';

// Route table: [HTTP method, URL pattern (regex), handler function].
// Values captured by (\d+) are passed to the handler as arguments.
$routes = [
    ['GET',  '#^/$#',                      'capsules_index'],
    ['GET',  '#^/capsules/new$#',          'capsules_create'],
    ['POST', '#^/capsules$#',              'capsules_store'],
    ['GET',  '#^/capsules/(\d+)$#',        'capsules_show'],
    ['GET',  '#^/capsules/(\d+)/file$#',   'capsules_file'],
    ['POST', '#^/capsules/(\d+)/delete$#', 'capsules_delete'],
    ['GET',  '#^/wall$#',                  'wall_index'],
    ['GET',  '#^/notifications$#',         'notifications_index'],
    ['GET',  '#^/health$#',                'health_check'],
];

// Login pages exist only when authentication is on (otherwise they return 404).
if (auth_enabled()) {
    array_push(
        $routes,
        ['GET',  '#^/login$#',    'auth_login_form'],
        ['POST', '#^/login$#',    'auth_login'],
        ['GET',  '#^/register$#', 'auth_register_form'],
        ['POST', '#^/register$#', 'auth_register'],
        ['POST', '#^/logout$#',   'auth_logout'],
    );
}

$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/';

// The health check is called often by monitoring tools:
// do not create a session file or open capsules for each of those calls.
if ($path !== '/health') {
    start_session();

    // Open due capsules before any page is rendered, so pages always show the current state.
    open_due_capsules();
}

foreach ($routes as [$routeMethod, $pattern, $handler]) {
    if ($routeMethod === $method && preg_match($pattern, $path, $matches)) {
        if ($method === 'POST') {
            csrf_check(); // every form must send the CSRF token
        }
        $handler(...array_slice($matches, 1));
        exit;
    }
}

abort(404);
