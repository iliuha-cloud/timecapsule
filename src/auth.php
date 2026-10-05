<?php
// Who is using the app?
//
// AUTH_ENABLED=true  -> users register and log in; the user id is kept in the session.
// AUTH_ENABLED=false -> no login at all; everybody is the built-in guest user (id 1).

const GUEST_USER_ID = 1;

function auth_enabled(): bool
{
    return config_bool('AUTH_ENABLED', true);
}

// The current user as ['id' => ..., 'email' => ...], or null when nobody is logged in.
function current_user(): ?array
{
    static $user = false; // false = "not loaded yet" (null is a valid answer)

    if ($user === false) {
        $id = auth_enabled() ? ($_SESSION['user_id'] ?? null) : GUEST_USER_ID;
        $user = $id === null ? null : find_user_by_id((int) $id);
    }

    return $user;
}

// Pages that need a user: send anonymous visitors to the login page.
function require_user(): array
{
    $user = current_user();
    if ($user === null) {
        redirect('/login');
    }
    return $user;
}

// Login/register pages: a logged-in user has nothing to do there.
function require_guest(): void
{
    if (current_user() !== null) {
        redirect('/');
    }
}

function login_user(int $userId): void
{
    // A new session id after login prevents "session fixation" attacks.
    session_regenerate_id(true);
    $_SESSION['user_id'] = $userId;
}

function logout_user(): void
{
    unset($_SESSION['user_id']);
    session_regenerate_id(true);
}

function find_user_by_id(int $id): ?array
{
    $user = db_query('SELECT id, email, password_hash FROM users WHERE id = ?', [$id])->fetch();
    return $user ?: null;
}

function find_user_by_email(string $email): ?array
{
    $user = db_query('SELECT id, email, password_hash FROM users WHERE email = ?', [$email])->fetch();
    return $user ?: null;
}

function create_user(string $email, string $password): int
{
    // password_hash() uses bcrypt and adds a random salt.
    return (int) db_query(
        'INSERT INTO users (email, password_hash) VALUES (?, ?) RETURNING id',
        [$email, password_hash($password, PASSWORD_BCRYPT)]
    )->fetchColumn();
}
