<?php

/** @var string $basePath */
/** @var string|null $error */
/** @var string $oldFirstName */
/** @var string $oldLastName */
/** @var string $oldEmail */
/** @var string $oldPhone */
/** @var string $csrfField */

use App\Support\View;

$asset = $basePath . '/public/assets';

?>
<!doctype html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <link rel="icon" type="image/svg+xml" href="<?= View::e($asset) ?>/images/favicon.svg" />
  <link rel="stylesheet" href="<?= View::e($asset) ?>/css/app.css">
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Create account — Dayfold</title>
  <meta name="description" content="Register for Dayfold to keep daily notes and timed reminders across email, WhatsApp, and SMS." />
</head>

<body>
  <main class="shell auth">
    <div class="auth__stage">
      <section class="auth__hero rise" aria-labelledby="brand-heading">
        <a class="brand" href="<?= View::e($basePath); ?>" id="brand-heading">
          <span class="brand__mark" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M5 8h14v2H5V8Zm0 5h10v2H5v-2Z" fill="#e8f3ef" />
              <circle cx="18" cy="16" r="2.2" fill="#d97a4a" />
            </svg>
          </span>
          <span class="brand__word">Dayfold</span>
        </a>
        <p class="lede">
          One account for today’s tasks, alarm times, and the channels that nudge you when it matters.
        </p>
        <div class="auth__hero-visual rise-delay-1" aria-hidden="true">
          <p>Set quiet hours later — we will not ping you while you sleep or deep-focus.</p>
        </div>
      </section>

      <section class="panel rise rise-delay-2" aria-labelledby="register-title">
        <h1 class="panel__title" id="register-title">Create your account</h1>
        <p class="panel__sub">Takes a minute. Then you can plan the rest of the day.</p>

        <p id="register-error" class="field__hint" role="alert" style="color:#b42318;margin-bottom:1rem;" <?= empty($error) ? 'hidden' : '' ?>>
          <?= View::e($error ?? '') ?>
        </p>

        <form class="form" action="<?=  View::e($basePath) ?>/register" method="POST" autocomplete="on" id="register-form">
          <div class="form__row">
            <div class="field">
              <label for="first-name">First name</label>
              <input id="first-name" name="first_name" type="text" autocomplete="given-name" placeholder="Maya" value="<?= View::e($oldFirstName); ?>" required />
            </div>
            <div class="field">
              <label for="last-name">Last name</label>
              <input id="last-name" name="last_name" type="text" autocomplete="family-name" placeholder="Chen" value="<?= View::e($oldLastName); ?>" required />
            </div>
          </div>
          <div class="field">
            <label for="email">Email</label>
            <input id="email" name="email" type="email" autocomplete="email" placeholder="maya@example.com" value="<?= View::e($oldEmail); ?>" required />
          </div>
          <div class="field">
            <label for="phone">Mobile (for SMS / WhatsApp)</label>
            <input id="phone" name="phone" type="tel" autocomplete="tel" placeholder="+1 555 010 2244" value="<?= View::e($oldPhone); ?>" />
            <p class="field__hint">Optional now — add later before enabling message reminders.</p>
          </div>
          <div class="field">
            <label for="password">Password</label>
            <input id="password" name="password" type="password" minlength="8" maxlength="128" autocomplete="new-password" placeholder="At least 8 characters" required />
          </div>
          <div class="form__actions">
            <button class="btn btn--primary btn--block" type="submit">Start my day list</button>
          </div>
        </form>

        <p class="form__footer">
          Already have an account?
          <a href="<?= View::e($basePath) ?>/login">Sign in</a>
        </p>
      </section>
    </div>
  </main>
  <script type="module" src="/src/main.js"></script>
</body>

</html>