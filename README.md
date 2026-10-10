# Dayfold — Daily Notes

Core PHP MVC app for a daily “what to do” list: timed tasks and reminders (email, WhatsApp, SMS).

It lives in the shared PHP Docker stack at `D:\Docker\PHP` and is served at `http://localhost:8080/daily-notes/`. There is no Compose file inside this project. Part of [PHP Learning](https://github.com/carnivo7/PHP-Learning), branch `daily-notes`.

---

## What works today

- Sign-in page and a registration page (the form is rendered; saving an account is not wired yet).
- Guest visits to `/` redirect to `/login`. A signed-in visit to `/login` or `/register` redirects to `/`.
- Session auth. The cookie is named `dayfold_sess`, path `/daily-notes`, HttpOnly, SameSite=Lax, and Secure only when the request is HTTPS.
- The session id is regenerated after a successful login.
- CSRF token on the login form. Failed AJAX logins return a fresh token so the next try can succeed.
- Login is submitted with `fetch` and expects JSON (`Accept: application/json`).
- Request fields other than the password go through `App\Support\Input`.
- Every request loads `.env` and opens one MySQL connection before routing.
- The home page renders the Dayfold “today” layout. The tasks on that page are static HTML, not rows from the database.

## Prepared, not used by a request yet

- `App\Support\Database` can run prepared statements, transactions, and identifier checks. No controller calls it yet.
- `dayfold.sql` creates the `dayfold` database and the `users` table (soft-delete, unique email and phone among active rows, lockout columns).
- `AuthController::handleLogin()` still accepts one hardcoded demo account. It does not call `password_verify` or read `users`.
- `GET /register` renders the form. There is no `POST /register` route, so submitting it does not create a user.
- `GET /logout` is the route in `config/routes.php`. `AuthController::logout()` expects a POST body with a valid `_token`. The “Sign out” link on the notes page is a normal GET link, so it does not send that token.

Task create/update/delete, alarms, and email / WhatsApp / SMS sending are not in the code.

---

## Stack

| Piece | What the code uses |
| --- | --- |
| PHP | 8.5.11 in the shared image `php-dev:8.5.11` (`pdo_mysql` is installed there) |
| Style | Controllers, views, support classes, middleware. No framework |
| Autoload | A small PSR-4-style loader in `public/index.php` for the `App\` prefix. Composer is not used |
| HTTP | nginx in the shared stack, port **8080** on the host |
| Front controller | `public/index.php` |
| Database | MySQL on the Windows host. PHP reaches it via `DB_HOST` |
| Config | `config/app.php` for the URL prefix and debug flag. Secrets stay in `.env` |

---

## Project layout

```text
daily-notes/
├── public/                      # only this tree is the nginx document root
│   ├── index.php                # bootstrap, route match, middleware, dispatch
│   └── assets/
│       ├── css/app.css
│       ├── js/script.js         # login form AJAX
│       └── images/favicon.svg
├── app/
│   ├── Controllers/
│   │   ├── AuthController.php   # login, logout, register page
│   │   └── HomeController.php   # renders the notes view
│   ├── Middleware/
│   │   └── AuthMiddleware.php   # auth and guest gates
│   ├── Support/
│   │   ├── Session.php
│   │   ├── Csrf.php
│   │   ├── Input.php
│   │   ├── View.php
│   │   ├── Env.php              # reads the project .env into the process
│   │   └── Database.php         # one PDO connection per request
│   └── Views/
│       ├── auth/login.php
│       ├── auth/register.php
│       └── notes.php
├── config/
│   ├── app.php                  # base_path, base_path_fs, debug
│   └── routes.php
├── .env                         # local only; gitignored
├── dayfold.sql                  # local schema; gitignored (*.sql)
└── README.md
```

`View::render('auth.login')` maps dots to folders, so that name loads `app/Views/auth/login.php`.

---

## Request path

nginx sends `/daily-notes/...` to `public/index.php`. The front controller then:

1. Registers the autoloader (`App\Support\Env` → `app/Support/Env.php`).
2. Loads `config/app.php`.
3. Loads `.env` with `Env::load()`. Existing process variables are left as they are.
4. Calls `Database::boot()`. A failed connection stops the request before a page is rendered.
5. Sends `X-Content-Type-Options: nosniff`, `X-Frame-Options: DENY`, and `Referrer-Policy: no-referrer`.
6. Starts the session and strips the `/daily-notes` prefix from the path.
7. Finds a route with the same method and path, or returns plain text `404 Not Found`.
8. Runs `auth` or `guest` middleware, then calls the controller action.

`config/app.php` sets `debug` to `true`. While that flag is on, PHP errors are displayed and a PDO failure is rethrown with the original exception attached. `Database::prepare()` always logs PDO errors and throws a generic `RuntimeException` for queries.

---

## Routes

Paths below are after the `/daily-notes` prefix. The browser URL includes the prefix.

| Method | Path | Middleware | Handler |
| --- | --- | --- | --- |
| GET | `/` | auth | `HomeController::index` |
| GET | `/login` | guest | `AuthController::showLogin` |
| POST | `/login` | guest | `AuthController::handleLogin` |
| GET | `/logout` | auth | `AuthController::logout` |
| GET | `/register` | guest | `AuthController::showRegister` |

Login checks, in order: POST only, body at most 8192 bytes, form content type, CSRF token, then email format and a password length of 1–128 characters. The password is read from raw `$_POST` and is not passed through `Input`. A mismatch returns the same message as a bad email, with HTTP 401 for JSON clients.

JSON clients are requests whose `Accept` header contains `application/json`, or whose `X-Requested-With` header is `XMLHttpRequest`. Other clients get a redirect and a flash message in the session.

---

## Run locally

1. Start the shared stack from `D:\Docker\PHP`:

   ```powershell
   docker compose up -d
   ```

2. nginx (`nginx/conf.d/default.conf`) already maps this app:

   - `/daily-notes/public/assets/` → `Projects/daily-notes/public/assets/`
   - `/daily-notes/` → `Projects/daily-notes/public/`
   - PHP is executed as `public/index.php`, with `SCRIPT_NAME` set to `/daily-notes/index.php`

   Styles and scripts in the views use `/daily-notes/public/assets/...`, which matches that first location.

3. Create the database from `dayfold.sql` on the MySQL server you want the app to use. The script creates database `dayfold` and table `dayfold.users`.

4. Put a `.env` file in this project directory (it is gitignored). `Database::boot()` reads these names:

   ```env
   DB_HOST=host.docker.internal
   DB_PORT=3306
   DB_DATABASE=dayfold
   DB_USER=dayfold_app
   DB_PASSWORD=your-password
   ```

   `DB_HOST=host.docker.internal` is the Windows host as seen from the PHP container. Use a different host only if MySQL is not on that machine. `DB_USER` and `DB_PASSWORD` must both be non-empty. The database name may contain only letters, numbers, and underscores.

5. Open:

   - `http://localhost:8080/daily-notes/` — guests are sent to login
   - `http://localhost:8080/daily-notes/login`
   - `http://localhost:8080/daily-notes/register`

### Demo login

Until `handleLogin()` reads `users`, these values are the only ones that start a session:

| Field | Value |
| --- | --- |
| Email | `demo@dayfold.test` |
| Password | `ChangeMe123!` |

The session then stores `user_id` = `1` and `user_email`. This password is for the local lab only.

The browser script also requires a password of at least 8 characters before it sends the request. `ChangeMe123!` meets that check.

---

## Security notes

- nginx roots this app at `public/`. `app/`, `config/`, `.env`, and `dayfold.sql` are not the document root.
- A location in the shared nginx config denies dotfiles such as `.env`.
- Escape HTML with `View::e()`. Sanitize non-password input with `Input`.
- CSRF compares tokens with `hash_equals`. Login rotates the token when the check fails.
- `Database` requires native prepares, disables multi-statements and `LOCAL INFILE`, and binds named placeholders only. Table and column names that cannot be bound go through `Database::ident()`, which allows letters, numbers, and underscores.
- PDO error text is written to the error log. Query failures shown to callers use the message `Database request failed.`

---

## Git

```bash
git checkout daily-notes
git add .
git commit -m "Your message"
git push origin daily-notes
```

`.gitignore` excludes `.env`, `vendor/`, logs, `*.sql`, and `dayfold-ui.zip`. Do not commit the database password.

---

## License

Personal PHP learning project (Core PHP, MVC). Not a production release.
