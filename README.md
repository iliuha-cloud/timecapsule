# TimeCapsule: pure PHP (functional style)

This is the TimeCapsule app written in plain PHP (8.2+) without a framework, in a procedural
("functional") style: the code is plain functions in a few files, with no classes of our own.
It uses PDO for PostgreSQL and plain PHP templates. The only PHP library is
[vlucas/phpdotenv](https://github.com/vlucas/phpdotenv), which reads the `.env` file; it is
installed with [Composer](https://getcomposer.org). In the browser, the date and time picker is
[flatpickr](https://flatpickr.js.org) (MIT license), vendored in `public/vendor/flatpickr`. The [root README](../README.md) explains
what the app does. [`../open_capsules_php_oop`](../open_capsules_php_oop) is the same app written with classes.

```
public/index.php                 front controller + route table (every request starts here)
public/css/app.css               the only stylesheet
public/js/app.js                 date/time picker, sends the open time as UTC, shows local times
public/vendor/flatpickr/         flatpickr date/time picker (MIT, see LICENSE.md there)
src/bootstrap.php                loads Composer + .env, sets UTC, error handler, sessions
src/db.php                       PDO connection
src/helpers.php                  config(), e(), view(), redirect(), flash(), time_tag(), countdown()
src/csrf.php                     CSRF token for all forms
src/auth.php                     current user, login/logout, AUTH_ENABLED switch
src/storage.php                  save / delete / stream uploaded files (local disk)
src/mailer.php                   simulated e-mail -> notifications table
src/capsules.php                 capsule queries, open_due_capsules(), access rules, validation
src/controllers/                 one file per page group
views/                           HTML templates (layout.php + one file per page)
bin/init-db.php                  creates the tables from database/schema.sql
storage/uploads/                 uploaded files
storage/sessions/                PHP session files
deploy/nginx.conf                sample nginx site for an Ubuntu server
composer.json / composer.lock    Composer packages (vendor/ is not in git)
```

How the code is loaded: every entry point (`public/index.php`, `bin/init-db.php`) starts with
`require 'src/bootstrap.php'`. The bootstrap file first loads `vendor/autoload.php`, which
Composer generates and which makes the phpdotenv classes available. Then it loads our own
function files with plain `require` statements, one per line, in a fixed order.

Composer could also load these files for us (`"autoload": {"files": [...]}` in
`composer.json`). We use explicit `require` lines instead: you can see in one place exactly
which files are loaded, and when you add a new file you only add one line to
`src/bootstrap.php`, without running `composer dump-autoload`. Composer's autoloader is really
made for classes, and this version of the app has none of its own.

## Requirements

- **With Docker:** Docker Desktop (or Docker Engine) with Docker Compose v2. Nothing else.
- **Without Docker:** PHP 8.2+ ([XAMPP](https://www.apachefriends.org) works) with the extensions
  `pdo_pgsql`, `mbstring`, `fileinfo`, [Composer](https://getcomposer.org/download/) and a
  PostgreSQL database you can connect to.

Windows: see [Setting up a Windows computer](../README.md#setting-up-a-windows-computer).

## Run without Docker

1. Install the Composer packages and create the settings file:

   ```bash
   composer install
   cp .env.example .env
   ```

2. Open `.env` and enter your database: `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASSWORD`.

3. Create the tables and start the app:

   ```bash
   php bin/init-db.php
   php -S localhost:8080 -t public
   ```

4. Open http://localhost:8080 and create an account.

The built-in server (`php -S`) is for development only. On a real server, use nginx + PHP-FPM
(see below).

## Run with Docker

Docker Desktop must be running.

```bash
docker compose up --build
```

Open http://localhost:8080. The image runs `composer install --no-dev` while it is built, so
you do not need Composer on your machine. On startup the container waits for PostgreSQL, runs
`php bin/init-db.php`, and then starts Apache.

**Adminer** (a web UI for the database) starts together with the app at http://localhost:8081.
Log in with System `PostgreSQL`, Server `db`, Username `timecapsule`, Password `secret`,
Database `timecapsule`. Change its port with `ADMINER_HOST_PORT`.

Useful commands:

```bash
docker compose exec db psql -U timecapsule             # SQL shell
docker compose logs -f app                             # Apache logs
docker compose down -v                                 # stop and delete all data
```

Guest mode (no login):

```
AUTH_ENABLED=false docker compose up -d            # macOS / Linux
$env:AUTH_ENABLED="false"; docker compose up -d    # Windows PowerShell
```

Other host ports:

```
APP_HOST_PORT=9080 DB_HOST_PORT=5433 docker compose up -d                     # macOS / Linux
$env:APP_HOST_PORT="9080"; $env:DB_HOST_PORT="5433"; docker compose up -d     # Windows PowerShell
```

In PowerShell these variables stay set until you close the window.

Uploaded files are stored in the `uploads` volume, mounted at `/var/www/html/storage/uploads`.
Database data is stored in the `db-data` volume.

## Configuration

All settings are environment variables. Without Docker they come from `.env`, which is
optional and is loaded by phpdotenv (`Dotenv\Dotenv::createImmutable(ROOT_DIR)->safeLoad()` in
`src/bootstrap.php`). **Real environment variables always win over `.env`.** In Docker they are
set in `docker-compose.yml`. The code reads every setting with `config()` in
`src/helpers.php`: it checks `getenv()` first and then the values phpdotenv loaded into
`$_ENV` / `$_SERVER`.

| Variable | Default | Meaning |
|---|---|---|
| `APP_PORT` | `8080` | Documentation only. PHP does not listen on a port itself. |
| `APP_URL` | `http://localhost:8080` | Public URL of the app |
| `APP_DEBUG` | `false` | `true` shows the error message on the 500 page |
| `DB_HOST` | `localhost` | PostgreSQL host |
| `DB_PORT` | `5432` | |
| `DB_NAME` | `timecapsule` | |
| `DB_USER` | `timecapsule` | |
| `DB_PASSWORD` | `secret` | |
| `UPLOAD_DIR` | `storage/uploads` | Upload folder. A relative path starts at the project root. |
| `MAX_UPLOAD_MB` | `5` | Maximum attachment size, in MB |
| `MAIL_DELAY_SECONDS` | `3` | How long the simulated "send e-mail" step takes |
| `AUTH_ENABLED` | `true` | `false`: no login, everybody is the built-in **Guest** user |

PHP's own upload limits must be a bit larger than `MAX_UPLOAD_MB`, so that the app can show its
own error message. Use `upload_max_filesize = 6M` and `post_max_size = 8M` (on Windows in
`C:\php\php.ini`). The Docker image already sets these values in `docker/php.ini`.

## Dates and time zones

The "Open at" field on the New capsule form takes a date **and** a time. The browser sends your
local time; the app stores UTC and shows times in the visitor's time zone.

- The form has a visible `open_at` field (local time, e.g. `2027-01-01T13:30`) and a hidden
  `open_at_utc` field. On submit, `public/js/app.js` fills `open_at_utc` with the same moment in
  UTC (e.g. `2027-01-01T12:30:00.000Z`). The server uses that value.
- Without JavaScript, only `open_at` arrives (from the browser's own date-time field). The server
  cannot know the visitor's time zone then, so it reads the value as UTC.
- Seconds are dropped. The open time must be in the future.
- `capsules.open_at` and all other timestamps are stored in UTC.
- Pages print every moment as `<time data-local datetime="2027-01-01T12:30:00Z">1 Jan 2027, 12:30 UTC</time>`
  (`time_tag()` in `src/helpers.php`). `public/js/app.js` rewrites the text in the visitor's
  local time; without JavaScript the UTC text stays.
- Sealed capsules show a countdown: "Opens in N days", "Opens in N hours", "Opens in N minutes",
  or "Opening soon…" when the time has passed but no request has opened the capsule yet.
- The calendar and time picker is [flatpickr](https://flatpickr.js.org) (MIT license). Its
  files are vendored in `public/vendor/flatpickr` (no build step, no CDN). If they fail to load,
  the browser's own date-time field is used.

## How capsules open

When a capsule's open time has passed, the next page request opens it and adds an "is now open"
notification. No cron job or background worker is needed.

`public/index.php` calls `open_due_capsules()` (in `src/capsules.php`) at the start of every
request except `GET /health` and static files, before any page is rendered. It runs one
`UPDATE ... RETURNING` statement, which only returns the capsules it changed, so two requests at
the same moment never open the same capsule twice.

Quick test: make a capsule due in the database, then reload the page. With Docker:

```bash
docker compose exec db psql -U timecapsule -c "UPDATE capsules SET open_at = NOW() - interval '1 minute'"
```

Or run this SQL in any database client:

```sql
UPDATE capsules SET open_at = NOW() - interval '1 minute';
```

## Deploying to an Ubuntu server

These steps are for Ubuntu 24.04. They put nginx, PHP-FPM and PostgreSQL on one server.
All commands in this section run on the Linux server (for example over SSH from PowerShell:
`ssh ubuntu@<server-ip>`), not on your Windows computer.

**1. Install packages**

```bash
sudo apt update
sudo apt install -y nginx composer php8.3-cli php8.3-fpm php8.3-pgsql php8.3-mbstring php8.3-xml unzip postgresql git
```

(`fileinfo` is already included in the PHP 8.3 packages. `unzip` lets Composer unpack the
packages it downloads.)

**2. Create the database and user**

```bash
sudo -u postgres psql -c "CREATE USER timecapsule WITH PASSWORD 'secret';"
sudo -u postgres psql -c "CREATE DATABASE timecapsule OWNER timecapsule;"
```

**3. Copy the app and configure it**

```bash
sudo mkdir -p /var/www/timecapsule
sudo chown ubuntu:ubuntu /var/www/timecapsule
git clone <your-repo-url> /tmp/timecapsule-src
cp -r /tmp/timecapsule-src/open_capsules_php_functional/. /var/www/timecapsule/
cd /var/www/timecapsule
composer install --no-dev --optimize-autoloader
cp .env.example .env
nano .env                    # set DB_PASSWORD, APP_URL, ...
php bin/init-db.php
```

PHP-FPM does not pass system environment variables to PHP (`clear_env = yes`), so on the server
the settings come from `.env`.

**4. File permissions**

PHP-FPM runs as `www-data` and must be able to write uploads and sessions:

```bash
sudo chown -R www-data:www-data /var/www/timecapsule/storage
sudo chmod -R 775 /var/www/timecapsule/storage
```

**5. PHP upload limits**

In `/etc/php/8.3/fpm/php.ini`, set:

```ini
upload_max_filesize = 6M
post_max_size = 8M
```

Then restart PHP-FPM: `sudo systemctl restart php8.3-fpm`.

**6. nginx**

```bash
sudo cp deploy/nginx.conf /etc/nginx/sites-available/timecapsule
sudo ln -s /etc/nginx/sites-available/timecapsule /etc/nginx/sites-enabled/timecapsule
sudo rm -f /etc/nginx/sites-enabled/default
sudo nginx -t && sudo systemctl reload nginx
```

The site's document root is `/var/www/timecapsule/public`, so `src/`, `vendor/`, `storage/` and
`.env` are not reachable from the web.

**7. Firewall and check**

Allow HTTP (port 80) from everywhere and SSH (port 22) only from your own IP, for example
with `ufw`:

```bash
sudo ufw allow from <your-ip> to any port 22
sudo ufw allow 80/tcp
sudo ufw enable
```

Then open `http://<server-ip>/` and `http://<server-ip>/health`.

**Updating the app later:** copy the new files, run `composer install --no-dev
--optimize-autoloader` again (in case `composer.lock` changed), and restart PHP-FPM:
`sudo systemctl restart php8.3-fpm`.
