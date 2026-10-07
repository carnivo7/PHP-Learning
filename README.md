# Dayfold — Daily Notes (Core PHP)

Core PHP **MVC** app for daily “what to do” notes: timed tasks and reminders (email, WhatsApp, SMS).

Part of **[PHP Learning](https://github.com/carnivo7/PHP-Learning)** — branch `daily-notes`.

**Local URL:** `http://localhost:8080/daily-notes/`

---

## Features (current)

- Sign-in page (Dayfold UI)
- Guest visits to `/` redirect to `/login`
- Session auth (HttpOnly cookie, regenerate on login)
- CSRF protection on login POST
- AJAX login via `fetch` + JSON responses
- Recursive input sanitizer (`App\Support\Input`)
- Shared Docker + nginx (no Compose file inside this app)

## Not built yet

- Database users + `password_verify`
- Registration persistence
- Task CRUD, alarms, email / WhatsApp / SMS send

---

## Stack

| Layer | Choice |
| --- | --- |
| Language | PHP 8+ |
| Style | MVC (Controllers, Views, Support, Middleware) |
| Web server | nginx + PHP-FPM (shared Docker on port **8080**) |
| Front controller | `public/index.php` |
| Auth (lab) | Demo credentials until DB is wired |

---

## Project layout

```text
daily-notes/
├── public/                 # nginx document root only
│   ├── index.php
│   ├── favicon.svg
│   └── assets/
│       ├── css/app.css
│       └── js/script.js
├── app/
│   ├── Controllers/        # AuthController, HomeController
│   ├── Middleware/         # AuthMiddleware
│   ├── Support/            # Session, Csrf, Input, View
│   └── Views/              # login, notes, …
├── config/
│   ├── app.php
│   └── routes.php
├── storage/                # not web-reachable
├── .gitignore
└── README.md
```

---

## Run locally

1. Shared Docker stack running from `D:\Docker\PHP` (`docker compose up -d`).
2. nginx locations:
   - `/daily-notes/assets/` → `…/daily-notes/public/assets/`
   - `/daily-notes/` → `…/daily-notes/public/` → front controller
3. Open:
   - `http://localhost:8080/daily-notes/` → redirects to login if guest  
   - `http://localhost:8080/daily-notes/login`

### Demo login (temporary — replace with DB)

| Field | Value |
| --- | --- |
| Email | `demo@dayfold.test` |
| Password | `ChangeMe123!` |

Do **not** use this password in production. Remove the demo compare when PDO + `password_hash` / `password_verify` are added.

---

## Security notes

- Web root is `public/` only — keep `app/`, `config/`, `storage/` outside the document root.
- Escape output with `View::e()`; sanitize request fields with `Input` (not passwords).
- CSRF on state-changing POSTs; session cookie: HttpOnly, Path `/daily-notes`, SameSite=Lax, Secure on HTTPS.
- Prefer prepared statements for any SQL (next step).

---

## Git

```bash
git checkout daily-notes
git add .
git commit -m "Your message"
git push origin daily-notes
```

Ignore secrets, `vendor/`, logs, and `dayfold-ui.zip` (see `.gitignore`).

---

## License / learning

Personal PHP learning project (Core PHP, MVC). Not a production release.