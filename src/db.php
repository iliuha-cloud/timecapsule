<?php
// Database connection. The connection settings (DB_HOST, DB_PORT, DB_NAME, DB_USER,
// DB_PASSWORD) come from .env or the environment.

function db(): PDO
{
    static $pdo = null; // one connection per request, created on first use

    if ($pdo === null) {
        $dsn = sprintf(
            'pgsql:host=%s;port=%s;dbname=%s',
            config('DB_HOST', 'localhost'),
            config('DB_PORT', '5432'),
            config('DB_NAME', 'timecapsule')
        );

        $pdo = new PDO($dsn, config('DB_USER', 'timecapsule'), config('DB_PASSWORD', 'secret'), [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,        // SQL errors throw exceptions
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,   // rows as ['column' => value]
            PDO::ATTR_TIMEOUT => 5,                             // fail fast if the DB is down
        ]);

        // Columns are TIMESTAMP without time zone and always hold UTC values.
        $pdo->exec("SET TIME ZONE 'UTC'");
    }

    return $pdo;
}

// Run a query with parameters and return the statement.
function db_query(string $sql, array $params = []): PDOStatement
{
    $statement = db()->prepare($sql);
    $statement->execute($params);
    return $statement;
}

// Turn common database errors into a short hint for the person running the app.
// Returns null for any other error.
function db_problem_hint(Throwable $e): ?string
{
    if (!$e instanceof PDOException) {
        return null;
    }

    $message = $e->getMessage();
    if (str_contains($message, 'SQLSTATE[42P01]')) {       // undefined table
        return 'The database has no tables yet. Run: php bin/init-db.php';
    }
    if (str_contains($message, 'SQLSTATE[08')) {           // connection errors (08xxx)
        return 'Cannot connect to the database. Check DB_HOST, DB_PORT, DB_NAME, DB_USER and DB_PASSWORD.';
    }
    return null;
}

// Current time as a string for SQL, e.g. "2026-09-17 14:05:00" (UTC).
function now_utc(): string
{
    return gmdate('Y-m-d H:i:s');
}
