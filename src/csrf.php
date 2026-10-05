<?php
// CSRF protection: every POST form contains a hidden "_token" field with a random value
// stored in the session. Another website cannot know this value, so it cannot submit
// forms on behalf of our logged-in users.

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Hidden input to put inside every <form method="post">.
function csrf_field(): string
{
    return '<input type="hidden" name="_token" value="' . e(csrf_token()) . '">';
}

// Called by the router before any POST handler runs.
function csrf_check(): void
{
    $sent = $_POST['_token'] ?? '';
    $expected = $_SESSION['csrf_token'] ?? '';

    // hash_equals compares in constant time (no timing attacks).
    if (!is_string($sent) || $expected === '' || !hash_equals($expected, $sent)) {
        abort(403, 'Your session expired. Please reload the page.');
    }
}
