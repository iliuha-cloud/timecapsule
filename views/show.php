<?php
// One capsule. Variables: $capsule, $isOwner
$id = (int) $capsule['id'];
$hasFile = $capsule['file_path'] !== null;
?>
<div class="narrow">
<?php if ($isOwner): ?>
  <a class="back-link" href="/">
    <?= icon('arrow-left') ?>
    Back to my capsules
  </a>
<?php else: ?>
  <a class="back-link" href="/wall">
    <?= icon('arrow-left') ?>
    Back to the wall
  </a>
<?php endif; ?>

<?php if (!capsule_is_opened($capsule)): ?>
  <div class="card sealed-view">
    <div class="seal">
      <?= icon('lock') ?>
    </div>
    <h1><?= e($capsule['title']) ?></h1>
    <p class="subtitle">This capsule is sealed until <?= time_tag($capsule['open_at']) ?>.</p>
    <p class="countdown"><?= e(countdown($capsule['open_at'])) ?></p>
<?php if ($isOwner): ?>
    <div class="actions center">
      <?= render_template('partials/delete-form', ['id' => $id]) ?>
    </div>
<?php endif; ?>
  </div>
<?php else: ?>
  <article class="card">
    <span class="badge badge-opened">
      <?= icon('lock-open') ?>
      Opened
    </span>
    <h1 style="margin-top:8px"><?= e($capsule['title']) ?></h1>
    <p class="subtitle">Sealed on <?= time_tag($capsule['created_at']) ?> · opened on <?= time_tag($capsule['opened_at']) ?></p>
    <div class="letter"><?= e($capsule['message']) ?></div>
<?php if ($hasFile && capsule_is_image($capsule)): ?>

    <img class="attachment-image" src="/capsules/<?= $id ?>/file" alt="Attachment: <?= e($capsule['file_name']) ?>">
<?php endif; ?>
<?php if ($hasFile || $isOwner): ?>

    <div class="actions">
<?php if ($hasFile): ?>
      <a class="btn btn-secondary" href="/capsules/<?= $id ?>/file?download=1">
        <?= icon('download') ?>
        Download <?= e($capsule['file_name']) ?>
      </a>
<?php endif; ?>
<?php if ($isOwner): ?>
      <?= render_template('partials/delete-form', ['id' => $id]) ?>
<?php endif; ?>
    </div>
<?php endif; ?>
  </article>
<?php endif; ?>
</div>
