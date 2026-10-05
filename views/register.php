<?php // Sign up. Variables: $email, $errors ?>
<div class="auth-card">
  <div class="page-head">
    <div>
      <h1>Create an account</h1>
      <p class="subtitle">Start sending messages to the future.</p>
    </div>
  </div>
  <form class="card" method="post" action="/register" novalidate>
    <?= csrf_field() ?>
    <div class="<?= field_class($errors, 'email') ?>">
      <label for="email">Email</label>
      <input type="email" id="email" name="email" autocomplete="email" required value="<?= e($email) ?>"<?= field_aria($errors, 'email') ?>>
      <?= field_error($errors, 'email') ?>
    </div>
    <div class="<?= field_class($errors, 'password') ?>">
      <label for="password">Password</label>
      <input type="password" id="password" name="password" autocomplete="new-password" required<?= field_aria($errors, 'password', true) ?>>
      <span class="hint" id="password-hint">At least 8 characters.</span>
      <?= field_error($errors, 'password') ?>
    </div>
    <div class="<?= field_class($errors, 'password_confirmation') ?>">
      <label for="password_confirmation">Confirm password</label>
      <input type="password" id="password_confirmation" name="password_confirmation" autocomplete="new-password" required<?= field_aria($errors, 'password_confirmation') ?>>
      <?= field_error($errors, 'password_confirmation') ?>
    </div>
    <button type="submit" class="btn btn-primary btn-block">Sign up</button>
    <p class="form-footer">Already have an account? <a href="/login">Log in</a></p>
  </form>
</div>
