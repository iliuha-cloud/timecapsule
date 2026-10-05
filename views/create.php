<?php // New capsule form. Variables: $old, $errors, $maxUploadMb ?>
<div class="narrow">
  <a class="back-link" href="/">
    <?= icon('arrow-left') ?>
    Back to my capsules
  </a>
  <div class="page-head">
    <h1>New capsule</h1>
  </div>
  <form class="card" method="post" action="/capsules" enctype="multipart/form-data" novalidate>
    <?= csrf_field() ?>

    <div class="<?= field_class($errors, 'title') ?>">
      <label for="title">Title</label>
      <input type="text" id="title" name="title" maxlength="120" required value="<?= e($old['title']) ?>"<?= field_aria($errors, 'title') ?>>
      <?= field_error($errors, 'title') ?>
    </div>

    <div class="<?= field_class($errors, 'message') ?>">
      <label for="message">Message</label>
      <textarea id="message" name="message" maxlength="5000" required<?= field_aria($errors, 'message') ?>><?= e($old['message']) ?></textarea>
      <?= field_error($errors, 'message') ?>
    </div>

    <?php /* "open_at" = local time, e.g. 2027-01-01T13:30 (flatpickr or the browser's own field).
             "open_at_utc" is filled by public/js/app.js on submit (the same moment in UTC). */ ?>
    <div class="<?= field_class($errors, 'open_at') ?>">
      <label for="open_at">Open at</label>
      <input type="datetime-local" id="open_at" name="open_at" required value="<?= e($old['open_at']) ?>" data-datetime-picker<?= field_aria($errors, 'open_at', true) ?>>
      <input type="hidden" name="open_at_utc" value="">
      <span class="hint" id="open_at-hint">Date and time in your time zone.</span>
      <?= field_error($errors, 'open_at') ?>
    </div>

    <div class="<?= field_class($errors, 'recipient_email') ?>">
      <label for="recipient_email">Recipient email <span class="optional">(optional)</span></label>
      <input type="email" id="recipient_email" name="recipient_email" value="<?= e($old['recipient_email']) ?>"<?= field_aria($errors, 'recipient_email', true) ?>>
      <span class="hint" id="recipient_email-hint">We will notify this address when the capsule opens. Leave empty to notify yourself.</span>
      <?= field_error($errors, 'recipient_email') ?>
    </div>

    <div class="<?= field_class($errors, 'attachment') ?>">
      <label for="attachment">Attachment <span class="optional">(optional)</span></label>
      <input type="file" id="attachment" name="attachment" accept=".jpg,.jpeg,.png,.gif,.webp,.pdf"<?= field_aria($errors, 'attachment', true) ?>>
      <span class="hint" id="attachment-hint">JPG, PNG, GIF, WEBP or PDF, up to <?= (int) $maxUploadMb ?> MB.</span>
      <?= field_error($errors, 'attachment') ?>
    </div>

    <div class="field">
      <label class="checkbox">
        <input type="checkbox" name="is_public" value="1"<?= $old['is_public'] ? ' checked' : '' ?>>
        Show on the public wall after opening
      </label>
    </div>

    <button type="submit" class="btn btn-primary btn-block">
      <?= icon('lock') ?>
      Seal capsule
    </button>
  </form>
</div>
