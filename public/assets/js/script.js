/**
 * Dayfold form client.
 *
 * Login, registration, and later note forms use this for the browser half of a POST.
 * It does not replace the PHP checks. A visitor can edit or skip this file.
 */
'use strict';

class FormClient {
    /**
     * @param {string} formId   id of the <form>
     * @param {string} errorId  id of the alert element
     */
    constructor(formId, errorId) {
        this.form = document.getElementById(formId);
        this.errorMessage = document.getElementById(errorId);
        this.submitButton = this.form ? this.form.querySelector('button[type="submit"]') : null;
        this.tokenInput = this.form ? this.form.querySelector('input[name="_token"]') : null;
        this.inFlight = false;

        if (this.form) {
            this.form.addEventListener('submit', (event) => this.handleSubmit(event));
        }
    }

    /** Subclasses return an error string, or '' when the fields are acceptable. */
    validate() {
        return '';
    }

    /** Subclasses handle a successful JSON body that does not redirect. */
    onSuccess(data) {
        if (this.isAppRedirect(data.redirect)) {
            window.location.assign(data.redirect);
        }
    }

    async handleSubmit(event) {
        event.preventDefault();
        if (this.inFlight || !this.form) return;

        const error = this.validate();
        if (error) {
            this.showError(error);
            return;
        }

        // bin2hex(random_bytes(32)) is 64 hex characters. Anything else was not minted here.
        if (!this.tokenInput || !/^[a-f0-9]{64}$/i.test(this.tokenInput.value)) {
            this.showError('This page expired. Refresh it and try again.');
            return;
        }

        await this.send();
    }

    async send() {
        this.inFlight = true;
        if (this.submitButton) this.submitButton.disabled = true;
        this.showError('');

        try {
            // POST is fixed here so a changed form method cannot put the fields in the URL.
            // FormData carries the fields and _token. The browser sets the multipart boundary.
            const response = await fetch(this.form.action, {
                method: 'POST',
                body: new FormData(this.form),
                credentials: 'same-origin',
                redirect: 'manual',
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            const data = await this.readJson(response);
            this.applyCsrf(data);

            if (response.ok && data && data.ok === true) {
                this.onSuccess(data);
                return;
            }

            this.showError(this.readMessage(data) || 'The request could not be completed. Please try again.');
        } catch {
            this.showError('The request could not be completed. Please try again.');
        } finally {
            this.inFlight = false;
            if (this.submitButton) this.submitButton.disabled = false;
            this.clearPasswords();
        }
    }

    /**
     * Accept only a small JSON object. An HTML error page is not shown,
     * because its text can contain paths and queries.
     */
    async readJson(response) {
        const type = response.headers.get('content-type') || '';
        if (!type.includes('application/json')) return null;

        const text = await response.text();
        if (!text || text.length > 10000) return null;

        try {
            const data = JSON.parse(text);
            if (!data || typeof data !== 'object' || Array.isArray(data)) return null;
            return data;
        } catch {
            return null;
        }
    }

    /** Save the rotated token. The next POST must send this value, not the spent one. */
    applyCsrf(data) {
        if (!this.tokenInput || !data || typeof data.csrf !== 'string') return;
        if (!/^[a-f0-9]{64}$/i.test(data.csrf)) return;
        this.tokenInput.value = data.csrf;
    }

    /**
     * A redirect may only be a same-origin path.
     * "//evil.example" and "/\evil.example" are rejected.
     */
    isAppRedirect(url) {
        if (typeof url !== 'string' || url.length === 0 || url.length > 2048) return false;
        if (!url.startsWith('/') || url.startsWith('//') || url.startsWith('/\\')) return false;
        return !/[\u0000-\u001F\u007F\\]/.test(url);
    }

    readMessage(data) {
        if (!data || typeof data.message !== 'string') return '';
        const message = data.message.replace(/[\u0000-\u001F\u007F]/g, '').trim();
        return message.slice(0, 300);
    }

    showError(message) {
        if (!this.errorMessage) return;
        this.errorMessage.hidden = message === '';
        this.errorMessage.textContent = message;
    }

    clearPasswords() {
        if (!this.form) return;
        this.form.querySelectorAll('input[type="password"]').forEach((field) => {
            field.value = '';
        });
    }

    assignTrimmed(input) {
        if (input) input.value = input.value.trim();
    }

    hasControlCharacter(value) {
        return /[\u0000-\u001F\u007F]/.test(value);
    }

    mark(input, ok) {
        if (!input) return;
        input.setAttribute('aria-invalid', ok ? 'false' : 'true');
        input.classList.toggle('field__hint--error', !ok);
    }
}

class LoginManager extends FormClient {
    constructor() {
        super('login-form', 'login-error');
        this.emailInput = document.getElementById('email');
        this.passwordInput = document.getElementById('password');
        this.emailPattern = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;

        if (this.emailInput) {
            this.emailInput.addEventListener('input', () => this.validate());
        }
    }

    validate() {
        const email = (this.emailInput?.value || '').trim();
        const password = this.passwordInput?.value || '';
        const emailOk = email.length > 0 && email.length <= 254 && this.emailPattern.test(email);
        const passwordOk = password.length >= 8 && password.length <= 128;

        this.mark(this.emailInput, emailOk);
        if (!emailOk || !passwordOk) return 'Enter a valid email and a password of 8 to 128 characters.';
        return '';
    }
}

class RegisterManager extends FormClient {
    constructor() {
        super('register-form', 'register-error');
        this.firstNameInput = document.getElementById('first-name');
        this.lastNameInput = document.getElementById('last-name');
        this.emailInput = document.getElementById('email');
        this.phoneInput = document.getElementById('phone');
        this.passwordInput = document.getElementById('password');

        this.emailPattern = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
        this.namePattern = /^[\p{L}\p{M}][\p{L}\p{M}' -]{0,79}$/u;
        this.phonePattern = /^\+?[0-9]{8,15}$/;

        this.bindLive(this.firstNameInput);
        this.bindLive(this.lastNameInput);
        this.bindLive(this.emailInput);
        this.bindLive(this.phoneInput);
        this.bindLive(this.passwordInput);
    }

    bindLive(input) {
        if (!input) return;
        input.addEventListener('input', () => this.validateField(input));
    }

    validate() {
        this.assignTrimmed(this.firstNameInput);
        this.assignTrimmed(this.lastNameInput);
        this.assignTrimmed(this.emailInput);
        this.assignTrimmed(this.phoneInput);

        const checks = [
            this.validateField(this.firstNameInput),
            this.validateField(this.lastNameInput),
            this.validateField(this.emailInput),
            this.validateField(this.phoneInput),
            this.validateField(this.passwordInput),
        ];
        const failed = checks.find((result) => !result.ok);
        return failed ? failed.message : '';
    }

    validateField(input) {
        if (input === this.firstNameInput) return this.validateName(input, 'First name');
        if (input === this.lastNameInput) return this.validateName(input, 'Last name');
        if (input === this.emailInput) return this.validateEmail(input);
        if (input === this.phoneInput) return this.validatePhone(input);
        return this.validatePassword(input);
    }

    validateName(input, label) {
        const value = (input?.value || '').trim();
        const ok = value.length > 0 && value.length <= 80 && this.namePattern.test(value) && !this.hasControlCharacter(value);
        this.mark(input, ok);
        return { ok, message: ok ? '' : label + ' can use letters, spaces, apostrophes, and hyphens only.' };
    }

    validateEmail(input) {
        const value = (input?.value || '').trim();
        const ok = value.length > 0 && value.length <= 254 && this.emailPattern.test(value) && !this.hasControlCharacter(value);
        this.mark(input, ok);
        return { ok, message: ok ? '' : 'Enter a valid email address.' };
    }

    validatePhone(input) {
        const value = (input?.value || '').trim();
        if (value === '') {
            this.mark(input, true);
            return { ok: true, message: '' };
        }
        const compact = value.replace(/[\s().-]/g, '');
        const ok = value.length <= 20 && this.phonePattern.test(compact);
        this.mark(input, ok);
        return { ok, message: ok ? '' : 'Enter a mobile number with 8 to 15 digits, or leave it blank.' };
    }

    validatePassword(input) {
        const value = input?.value || '';
        const email = (this.emailInput?.value || '').trim().toLowerCase();
        const ok = value.length >= 8 && value.length <= 128 && value.trim() !== '' && value.toLowerCase() !== email && !this.hasControlCharacter(value);
        this.mark(input, ok);
        return { ok, message: ok ? '' : 'Use 8 to 128 characters, and do not use your email as the password.' };
    }
}

document.addEventListener('DOMContentLoaded', () => {
    if (document.querySelector('.login-container')) new LoginManager();
    else if (document.querySelector('.register-container')) new RegisterManager();
});