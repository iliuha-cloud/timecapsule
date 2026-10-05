<?php
// One capsule card (used on "My capsules" and on the public wall).
// Variables: $capsule, $author (email local part, only on the wall).
$opened = capsule_is_opened($capsule);
?>
<a class="card capsule-card<?= $opened ? ' is-opened' : '' ?>" href="/capsules/<?= (int) $capsule['id'] ?>">
<?php if ($opened): ?>
  <span class="badge badge-opened">
    <?= icon('lock-open') ?>
    Opened
  </span>
<?php else: ?>
  <span class="badge badge-sealed">
    <?= icon('lock') ?>
    Sealed
  </span>
<?php endif; ?>
  <h2><?= e($capsule['title']) ?></h2>
<?php if ($opened): ?>
  <p class="excerpt"><?= e(excerpt($capsule['message'])) ?></p>
<?php endif; ?>
  <div class="capsule-meta">
    <span>
      <?= icon('calendar') ?>
      <?= time_tag($capsule['open_at']) ?>
    </span>
<?php if (!$opened): ?>
    <span><?= e(countdown($capsule['open_at'])) ?></span>
<?php endif; ?>
<?php if ($capsule['file_path'] !== null): ?>
    <span>
      <?= icon('paperclip') ?>
      1 file
    </span>
<?php endif; ?>
<?php if (isset($author)): ?>
    <span>by <?= e($author) ?></span>
<?php endif; ?>
  </div>
</a>
