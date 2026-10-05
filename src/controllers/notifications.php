<?php
// GET /notifications — the "e-mails" the app would have sent (rows written by mailer.php
// and open_due_capsules()).
function notifications_index(): void
{
    $user = require_user();

    $notifications = db_query(
        'SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC, id DESC LIMIT 100',
        [(int) $user['id']]
    )->fetchAll();

    view('notifications', [
        'title' => 'Notifications',
        'nav' => 'notifications',
        'notifications' => $notifications,
    ]);
}
