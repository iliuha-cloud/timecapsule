<?php // Delete button for the capsule owner. Variables: $id ?>
<form method="post" action="/capsules/<?= (int) $id ?>/delete" onsubmit="return confirm('Delete this capsule forever?')">
  <?= csrf_field() ?>
  <button type="submit" class="btn btn-danger">
    <?= icon('trash') ?>
    Delete
  </button>
</form>
