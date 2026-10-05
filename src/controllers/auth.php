<?php
// Register, log in, log out. These routes exist only when AUTH_ENABLED=true.

// GET /login
function auth_login_form(): void
{
    require_guest();
    view('login', ['title' => 'Log in', 'nav' => null, 'email' => '']);
}

// POST /login
function auth_login(): void
{
    require_guest();

    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    $password = (string) ($_POST['password'] ?? '');

    $user = $email === '' ? null : find_user_by_email($email);

    // password_verify() also returns false for the guest user (its hash is not a real hash).
    if ($user === null || !password_verify($password, $user['password_hash'])) {
        // Same message for "unknown email" and "wrong password": do not reveal which emails exist.
        flash('error', 'Invalid email or password.');
        view('login', ['title' => 'Log in', 'nav' => null, 'email' => $email], 422);
        return;
    }

    login_user((int) $user['id']);
    redirect('/');
}

// GET /register
function auth_register_form(): void
{
    require_guest();
    view('register', ['title' => 'Sign up', 'nav' => null, 'email' => '', 'errors' => []]);
}

// POST /register
function auth_register(): void
{
    require_guest();

    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    $password = (string) ($_POST['password'] ?? '');
    $confirmation = (string) ($_POST['password_confirmation'] ?? '');
    $errors = [];

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Enter a valid email address.';
    } elseif (find_user_by_email($email) !== null) {
        $errors['email'] = 'This email is already registered.';
    }

    if (strlen($password) < 8) {
        $errors['password'] = 'Password must be at least 8 characters.';
    }

    if ($password !== $confirmation) {
        $errors['password_confirmation'] = 'Passwords do not match.';
    }

    if ($errors) {
        view('register', ['title' => 'Sign up', 'nav' => null, 'email' => $email, 'errors' => $errors], 422);
        return;
    }

    login_user(create_user($email, $password));
    flash('success', 'Welcome! Your account is ready.');
    redirect('/');
}

// POST /logout
function auth_logout(): void
{
    require_user();
    logout_user();
    redirect('/login');
}
