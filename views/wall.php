<?php // Public wall. Variables: $capsules (each row has author_email) ?>
<div class="page-head">
  <h1>Public wall</h1>
</div>

<?php if ($capsules): ?>
<div class="grid">
<?php foreach ($capsules as $capsule): ?>
<?= render_template('partials/capsule-card', ['capsule' => $capsule, 'author' => author_name($capsule['author_email'])]) ?>
<?php endforeach; ?>
</div>
<?php else: ?>
<div class="card empty">
  <?= icon('hourglass') ?>
  <h2>The wall is empty</h2>
  <p>Public capsules appear here once they open.</p>
</div>
<?php endif; ?>
