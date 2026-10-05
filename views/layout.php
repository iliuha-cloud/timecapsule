<?php
// Page layout: header, flash messages, page content ($content), footer.
// Variables: $title, $nav ('home' | 'wall' | 'notifications' | null), $content.

// If the database is down, still render the page (e.g. the 500 page) without a user.
try {
    $user = current_user();
} catch (Throwable $e) {
    $user = null;
}
$nav = $nav ?? null;
$showPrivateNav = !auth_enabled() || $user !== null;
$flashes = isset($_SESSION) ? take_flashes() : [];

$navLink = function (string $key, string $href, string $label) use ($nav): string {
    $current = $nav === $key ? ' aria-current="page"' : '';
    return '<a href="' . e($href) . '"' . $current . '>' . e($label) . '</a>';
};
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($title) ?> · TimeCapsule</title>
  <link rel="stylesheet" href="/vendor/flatpickr/flatpickr.min.css">
  <link rel="stylesheet" href="/css/app.css">
  <script src="/vendor/flatpickr/flatpickr.min.js" defer></script>
  <script src="/js/app.js" defer></script>
</head>
<body>

<header class="site-header">
  <div class="container">
    <a class="logo" href="/">
      <?= icon('hourglass') ?>
      TimeCapsule
    </a>
    <nav class="nav" aria-label="Main">
<?php if ($showPrivateNav): ?>
      <?= $navLink('home', '/', 'My capsules') ?>
<?php endif; ?>
      <?= $navLink('wall', '/wall', 'Public wall') ?>
<?php if ($showPrivateNav): ?>
      <?= $navLink('notifications', '/notifications', 'Notifications') ?>
<?php endif; ?>
    </nav>
    <div class="user-box">
<?php if (!auth_enabled()): ?>
      <span>Guest</span>
<?php elseif ($user !== null): ?>
      <span><?= e($user['email']) ?></span>
      <form method="post" action="/logout">
        <?= csrf_field() ?>
        <button type="submit" class="btn-link">
          <?= icon('log-out') ?>
          Log out
        </button>
      </form>
<?php else: ?>
      <a class="btn btn-secondary" href="/login">Log in</a>
      <a class="btn btn-primary" href="/register">Sign up</a>
<?php endif; ?>
    </div>
  </div>
</header>

<main>
  <div class="container">
<?php foreach ($flashes as $flash): ?>
<?php if ($flash['type'] === 'error'): ?>
    <div class="flash flash-error" role="alert"><?= e($flash['message']) ?></div>
<?php else: ?>
    <div class="flash flash-success" role="status"><?= e($flash['message']) ?></div>
<?php endif; ?>
<?php endforeach; ?>

<?= $content ?>
  </div>
</main>

<footer class="site-footer">
  <div class="container">
    <?php /* Shows which machine answered, which database host it uses and where files are stored. */ ?>
    Served by <code><?= e(gethostname()) ?></code> · DB: <code><?= e(config('DB_HOST', 'localhost')) ?></code> · Files: <code>local disk (<?= e(config('UPLOAD_DIR', 'storage/uploads')) ?>)</code>
  </div>
</footer>

</body>
</html>
