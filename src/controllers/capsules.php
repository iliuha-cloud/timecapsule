<?php
// Pages for creating, viewing, downloading and deleting capsules.

// GET /
function capsules_index(): void
{
    $user = require_user();

    view('index', [
        'title' => 'My capsules',
        'nav' => 'home',
        'capsules' => capsules_for_user((int) $user['id']),
    ]);
}

// GET /capsules/new
function capsules_create(): void
{
    require_user();

    render_capsule_form([
        'title' => '',
        'message' => '',
        'open_at' => '',
        'recipient_email' => '',
        'is_public' => false,
    ], []);
}

// POST /capsules
function capsules_store(): void
{
    $user = require_user();

    [$data, $errors, $upload] = capsule_validate($_POST, $_FILES['attachment'] ?? null);
    if ($errors) {
        render_capsule_form($data, $errors, 422);
        return;
    }

    // 1. Save the attachment (if any).
    $data['file_path'] = null;
    $data['file_name'] = null;
    $data['file_mime'] = null;
    if ($upload !== null) {
        $data['file_path'] = storage_save($upload['file'], $upload['extension']);
        $data['file_name'] = mb_substr(basename($upload['file']['name']), 0, 255);
        $data['file_mime'] = $upload['mime'];
    }

    // 2. Insert the row.
    $data['user_id'] = (int) $user['id'];
    $capsule = capsule_create($data);

    // 3. "Send" the confirmation e-mail. This is the slow part of the request (see mailer.php).
    $recipient = $capsule['recipient_email'] ?? $user['email'];
    $text = sprintf('Capsule "%s" was sealed for you until %s.', $capsule['title'], format_utc($capsule['open_at']));
    mail_send($capsule, $recipient, $text);

    flash('success', 'Capsule sealed.');
    redirect('/capsules/' . $capsule['id']);
}

function render_capsule_form(array $old, array $errors, int $status = 200): void
{
    view('create', [
        'title' => 'New capsule',
        'nav' => 'home',
        'old' => $old,
        'errors' => $errors,
        'maxUploadMb' => max_upload_mb(),
    ], $status);
}

// GET /capsules/{id}
function capsules_show(string $id): void
{
    $user = current_user();
    $capsule = find_visible_capsule($id, $user);
    $isOwner = capsule_is_owner($capsule, $user);

    view('show', [
        'title' => $capsule['title'],
        'nav' => $isOwner ? 'home' : 'wall',
        'capsule' => $capsule,
        'isOwner' => $isOwner,
    ]);
}

// GET /capsules/{id}/file
// Files are never served directly by the web server: every download goes through this
// function, so the access rules below always apply.
function capsules_file(string $id): void
{
    $capsule = find_visible_capsule($id, current_user());

    if (!capsule_is_opened($capsule)) {
        abort(403); // only the owner gets here, and the capsule is still sealed
    }
    if ($capsule['file_path'] === null) {
        abort(404);
    }

    // Images are shown in the page; PDFs and ?download=1 are downloaded.
    $download = ($_GET['download'] ?? '') === '1' || !capsule_is_image($capsule);

    storage_stream(
        $capsule['file_path'],
        $capsule['file_mime'],
        $capsule['file_name'],
        $download ? 'attachment' : 'inline'
    );
}

// POST /capsules/{id}/delete
function capsules_delete(string $id): void
{
    $capsule = capsule_find((int) $id);
    if ($capsule === null || !capsule_is_owner($capsule, current_user())) {
        abort(404);
    }

    storage_delete($capsule['file_path']);
    capsule_delete((int) $capsule['id']); // notifications are deleted by ON DELETE CASCADE

    flash('success', 'Capsule deleted.');
    redirect('/');
}

// Load a capsule the current visitor may see. Otherwise 404, so that we do not even
// reveal that the capsule exists.
function find_visible_capsule(string $id, ?array $user): array
{
    $capsule = capsule_find((int) $id);
    if ($capsule === null || !capsule_can_view($capsule, $user)) {
        abort(404);
    }
    return $capsule;
}
