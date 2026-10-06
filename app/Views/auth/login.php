<?php
/** @var string $basePath */
/** @var string|null $error */
/** @var string $oldEmail */
/** @var string $csrfField */

use App\Support\View;

$asset = $basePath . '/public/assets';
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <link rel="icon" type="image/svg+xml" href="/favicon.svg" />
    <link rel="stylesheet" href="<?= View::e($asset) ?>/css/app.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Sign in — Dayfold</title>
    <meta name="description"
        content="Dayfold helps you plan what to do today and remind yourself by email, WhatsApp, or SMS." />
</head>

<body>
    <main class="shell auth login-container">
        <div class="auth__stage">
            <section class="auth__hero rise" aria-labelledby="brand-heading">
                <a class="brand" href="/" id="brand-heading">
                    <span class="brand__mark" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M5 8h14v2H5V8Zm0 5h10v2H5v-2Z" fill="#e8f3ef" />
                            <circle cx="18" cy="16" r="2.2" fill="#d97a4a" />
                        </svg>
                    </span>
                    <span class="brand__word">Dayfold</span>
                </a>
                <p class="lede">
                    Fold your day into clear next steps — timed “what to do” notes with reminders that actually reach you.
                </p>
                <div class="auth__hero-visual rise-delay-1" aria-hidden="true">
                    <p>Morning light, one list, alarms on your terms — email, WhatsApp, or a short SMS nudge.</p>
                </div>
            </section>

            <section class="panel rise rise-delay-2" aria-labelledby="login-title">
                <h1 class="panel__title" id="login-title">Sign in</h1>
                <p class="panel__sub">Pick up today’s list where you left it.</p>

                <p class="field__hint" role="alert" style="color:#b42318;margin-bottom:1rem;">
                <?php if (!empty($error)): ?>
                    <?= View::e($error) ?>
                    <?php endif; ?>
                </p>

                <form class="form" action="<?= View::e($basePath) ?>/login" method="POST" autocomplete="on" id="login-form">
                    <?= $csrfField ?>
                    <div class="field">
                        <label for="email">Email</label>
                        <input id="email" name="email" type="email" autocomplete="email" placeholder="you@example.com" required />
                    </div>
                    <div class="field">
                        <label for="password">Password</label>
                        <input id="password" name="password" type="password" autocomplete="current-password" placeholder="••••••••" required />
                    </div>
                    <div class="form__actions">
                        <button class="btn btn--primary btn--block" type="submit">Continue to today</button>
                    </div>
                </form>

                <p class="form__footer">
                    New here?
                    <a href="/register.html">Create a Dayfold account</a>
                </p>
            </section>
        </div>
    </main>
    <script type="module" src="<?= View::e($asset) ?>/js/script.js"></script>
</body>

</html>
