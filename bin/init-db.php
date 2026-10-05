<?php
// Creates the database tables (and the guest user) from database/schema.sql.
// Safe to run many times: the schema only creates what is missing.
//
//   php bin/init-db.php

require dirname(__DIR__) . '/src/bootstrap.php';

try {
    $sql = file_get_contents(ROOT_DIR . '/database/schema.sql');

    // exec() without parameters can run a whole file with many statements at once.
    db()->exec($sql);

    echo 'Database is ready (' . config('DB_HOST', 'localhost') . ").\n";
    exit(0);
} catch (Throwable $e) {
    fwrite(STDERR, 'Could not initialise the database: ' . $e->getMessage() . "\n");
    exit(1);
}
