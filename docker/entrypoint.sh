#!/bin/sh
# Runs every time the container starts, before Apache.
set -e
cd /var/www/html

# 1. Wait until PostgreSQL accepts connections (it may still be starting).
echo "Waiting for the database at ${DB_HOST:-localhost}..."
tries=0
until php -r 'require "src/bootstrap.php"; db();' >/dev/null 2>&1; do
    tries=$((tries + 1))
    if [ "$tries" -ge 30 ]; then
        echo "Database is not reachable, giving up."
        exit 1
    fi
    sleep 2
done

# 2. Create the tables (safe to run on every start).
php bin/init-db.php

# 3. Apache runs as www-data and must be able to write uploads and sessions.
mkdir -p storage/uploads storage/sessions
chown -R www-data:www-data storage

# 4. Start the web server (the CMD from the Dockerfile).
exec "$@"
