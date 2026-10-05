<?php // My capsules. Variables: $capsules ?>
<div class="page-head">
  <h1>My capsules</h1>
  <a class="btn btn-primary" href="/capsules/new">
    <?= icon('plus') ?>
    New capsule
  </a>
</div>

<?php if ($capsules): ?>
<div class="grid">
<?php foreach ($capsules as $capsule): ?>
<?= render_template('partials/capsule-card', ['capsule' => $capsule]) ?>
<?php endforeach; ?>
</div>
<?php else: ?>
<div class="card empty">
  <?= icon('hourglass') ?>
  <h2>No capsules yet</h2>
  <p>Write a message to your future self and seal it until the right day.</p>
  <a class="btn btn-primary" href="/capsules/new">Create your first capsule</a>
</div>
<?php endif; ?>
