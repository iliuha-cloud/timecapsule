<?php // Log in. Variables: $email ?>
<div class="auth-card">
  <div class="page-head">
    <div>
      <h1>Welcome back</h1>
      <p class="subtitle">Log in to open your capsules.</p>
    </div>
  </div>
  <form class="card" method="post" action="/login" novalidate>
    <?= csrf_field() ?>
    <div class="field">
      <label for="email">Email</label>
      <input type="email" id="email" name="email" autocomplete="email" required value="<?= e($email) ?>">
    </div>
    <div class="field">
      <label for="password">Password</label>
      <input type="password" id="password" name="password" autocomplete="current-password" required>
    </div>
    <button type="submit" class="btn btn-primary btn-block">Log in</button>
    <p class="form-footer">No account? <a href="/register">Sign up</a></p>
  </form>
</div>
