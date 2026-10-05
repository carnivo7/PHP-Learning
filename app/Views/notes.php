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
    <title>Today — Dayfold</title>
    <meta
        name="description"
        content="Plan today’s what-to-do tasks with alarm times and reminders by email, WhatsApp, or SMS." />
</head>

<body>
    <div class="shell shell--board">
        <header class="app-top rise">
            <div class="app-top__brand">
                <a class="brand brand--compact" href="<?= View::e($basePath) ?>/">
                    <span class="brand__mark" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M5 8h14v2H5V8Zm0 5h10v2H5v-2Z" fill="#e8f3ef" />
                            <circle cx="18" cy="16" r="2.2" fill="#d97a4a" />
                        </svg>
                    </span>
                    <span class="brand__word">Dayfold</span>
                </a>
                <nav class="app-nav" aria-label="Account">
                    <a class="btn btn--ghost" href="<?= View::e($basePath) ?>/logout">Sign out</a>
                </nav>
            </div>
            <div class="date-block">
                <p class="date-block__label">Today</p>
                <p class="date-block__day">Sunday, September 27</p>
                <p class="date-block__meta">3 pending · 1 done</p>
            </div>
        </header>

        <aside class="hint-bar" aria-label="Quiet hours">
            <strong>Quiet hours</strong>
            <p>Reminders pause from 10:00 PM – 7:00 AM. Alarms still show in your list.</p>
        </aside>

        <input class="view-switch sr-only" type="checkbox" id="empty-demo" />

        <div class="view-filled">
            <section class="section rise rise-delay-1" aria-labelledby="list-title">
                <div class="section__head">
                    <h2 class="section__title" id="list-title">Today’s tasks</h2>
                    <p class="section__count">3 pending</p>
                </div>
                <ul class="task-list task-list--tiles">
                    <li class="task">
                        <input class="task__check" type="checkbox" aria-label="Mark water plants as done" />
                        <div class="task__body">
                            <div class="task__top">
                                <h3 class="task__title">Water the balcony plants</h3>
                                <time class="task__time" datetime="2026-09-27T08:30">8:30 AM</time>
                            </div>
                            <p class="task__note">Skip the fern — it still looks damp from yesterday.</p>
                            <div class="task__meta">
                                <span class="tag tag--low">Low</span>
                                <span class="tag tag--channel">Email</span>
                            </div>
                        </div>
                    </li>
                    <li class="task">
                        <input class="task__check" type="checkbox" aria-label="Mark draft proposal as done" />
                        <div class="task__body">
                            <div class="task__top">
                                <h3 class="task__title">Send the draft proposal to Jordan</h3>
                                <time class="task__time" datetime="2026-09-27T11:00">11:00 AM</time>
                            </div>
                            <p class="task__note">Attach the revised timeline before the noon sync.</p>
                            <div class="task__meta">
                                <span class="tag tag--high">High</span>
                                <span class="tag tag--channel">Email</span>
                                <span class="tag tag--channel">WhatsApp</span>
                            </div>
                        </div>
                    </li>
                    <li class="task">
                        <input class="task__check" type="checkbox" aria-label="Mark pharmacy pickup as done" />
                        <div class="task__body">
                            <div class="task__top">
                                <h3 class="task__title">Pick up pharmacy refill</h3>
                                <time class="task__time" datetime="2026-09-27T16:15">4:15 PM</time>
                            </div>
                            <p class="task__note">Counter closes early on Sundays.</p>
                            <div class="task__meta">
                                <span class="tag tag--medium">Medium</span>
                                <span class="tag tag--channel">Message (SMS)</span>
                            </div>
                        </div>
                    </li>
                </ul>
            </section>

            <section class="section rise rise-delay-2" aria-labelledby="done-title">
                <div class="section__head">
                    <h2 class="section__title section__title--sub" id="done-title">Completed</h2>
                    <p class="section__count">1 today</p>
                </div>
                <ul class="task-list task-list--tiles">
                    <li class="task task--done">
                        <input
                            class="task__check"
                            type="checkbox"
                            checked
                            aria-label="Morning stretch marked done" />
                        <div class="task__body">
                            <div class="task__top">
                                <h3 class="task__title">Ten-minute morning stretch</h3>
                                <time class="task__time" datetime="2026-09-27T07:15">7:15 AM</time>
                            </div>
                            <div class="task__meta">
                                <span class="tag tag--low">Low</span>
                                <span class="tag tag--channel">Email</span>
                            </div>
                        </div>
                    </li>
                </ul>
            </section>
        </div>

        <div class="view-empty">
            <div class="empty">
                <div class="empty__glyph" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path
                            d="M5 7.5h14M5 12h9M5 16.5h11"
                            stroke="currentColor"
                            stroke-width="1.8"
                            stroke-linecap="square" />
                    </svg>
                </div>
                <h3>Nothing on the list yet</h3>
                <p>Use the plus button to add a what-to-do. Set a time and a reminder channel when you need a nudge.</p>
            </div>
        </div>

        <p class="demo-toggle">
            <label for="empty-demo">Show empty-day state</label>
        </p>

        <footer class="footer-note">
            Dayfold UI prototype for PHP Learning — HTML &amp; CSS only. Forms navigate for demo; no backend yet.
        </footer>
    </div>

    <button class="fab" type="button" id="add-task" aria-haspopup="dialog" aria-controls="task-dialog">
        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" />
        </svg>
        <span class="sr-only">Add a what-to-do</span>
    </button>

    <dialog class="composer-dialog" id="task-dialog" aria-labelledby="compose-title">
        <div class="composer">
            <div class="composer__head">
                <div>
                    <h2 class="composer__title" id="compose-title">Add a what-to-do</h2>
                    <p class="panel__sub">Set a time, pick priority, and choose how you want to be nudged.</p>
                </div>
                <button class="btn btn--ghost" type="button" id="close-task">Close</button>
            </div>

            <form class="form" action="/home.html" method="get">
                <div class="field">
                    <label for="task-title">Task</label>
                    <input
                        id="task-title"
                        name="title"
                        type="text"
                        placeholder="Call the clinic about Thursday’s appointment"
                        required />
                </div>
                <div class="field">
                    <label for="task-note">Note (optional)</label>
                    <textarea
                        id="task-note"
                        name="note"
                        rows="2"
                        placeholder="Ask about fasting instructions before the bloodwork."></textarea>
                </div>
                <div class="form__row">
                    <div class="field">
                        <label for="alarm-time">Alarm / time</label>
                        <input id="alarm-time" name="alarm" type="time" value="14:30" required />
                    </div>
                    <div class="field">
                        <span id="priority-label">Priority</span>
                        <div class="priority" role="radiogroup" aria-labelledby="priority-label">
                            <label>
                                <input type="radio" name="priority" value="high" />
                                <span data-level="high">High</span>
                            </label>
                            <label>
                                <input type="radio" name="priority" value="medium" checked />
                                <span data-level="medium">Medium</span>
                            </label>
                            <label>
                                <input type="radio" name="priority" value="low" />
                                <span data-level="low">Low</span>
                            </label>
                        </div>
                    </div>
                </div>
                <div class="field">
                    <span id="channels-label">Reminder channels</span>
                    <div class="channels" role="group" aria-labelledby="channels-label">
                        <label class="channel channel--email">
                            <input type="checkbox" name="channel" value="email" checked />
                            <span>Email</span>
                        </label>
                        <label class="channel channel--whatsapp">
                            <input type="checkbox" name="channel" value="whatsapp" />
                            <span>WhatsApp</span>
                        </label>
                        <label class="channel channel--sms">
                            <input type="checkbox" name="channel" value="sms" />
                            <span>Message (SMS)</span>
                        </label>
                    </div>
                    <p class="field__hint">Choose one or more. Channels use the contact details on your account.</p>
                </div>
                <div class="form__actions">
                    <button class="btn btn--primary" type="submit">Save to today</button>
                </div>
            </form>
        </div>
    </dialog>

    <script>
        const taskDialog = document.getElementById('task-dialog');
        const addTask = document.getElementById('add-task');
        const closeTask = document.getElementById('close-task');
        const taskTitle = document.getElementById('task-title');

        addTask.addEventListener('click', () => {
            taskDialog.showModal();
            taskTitle.focus();
        });

        closeTask.addEventListener('click', () => taskDialog.close());

        taskDialog.addEventListener('click', (event) => {
            if (event.target === taskDialog) {
                taskDialog.close();
            }
        });
    </script>
</body>

</html>
