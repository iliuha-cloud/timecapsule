<?php
// GET /health
//
// A quick status check for monitoring tools: 200 = the app and the database work,
// 500 = the database is not reachable. "hostname" shows which machine answered.
function health_check(): void
{
    try {
        // Reading a real table also catches a database that has no tables yet.
        db()->query('SELECT COUNT(*) FROM users');
        $status = 200;
        $body = ['status' => 'ok', 'db' => 'ok'];
    } catch (Throwable $e) {
        error_log('Health check failed: ' . $e->getMessage());
        $status = 500;
        $body = ['status' => 'error', 'db' => 'error', 'hint' => db_problem_hint($e)];
    }

    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($body + ['hostname' => gethostname()], JSON_UNESCAPED_SLASHES);
}
