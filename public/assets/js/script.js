/**
 * This is the main script for the application.
 */
'use strict';

class LoginManager {
    constructor() {
        this.initializeElements();
        this.bindEvents();
        this.setupValidation();
        this.inFlight = false;
    }

    initializeElements() {
        // Form elements
        this.form = document.getElementById('login-form');

        // Input Elements
        this.emailInput = document.getElementById('email');
        this.passwordInput = document.getElementById('password');
        this._token = document.querySelector('input[name="_token"]');

        // Submit Button
        this.submitButton = this.form ? this.form.querySelector('button[type="submit"]') : null;

        // Error Message
        this.errorMessage = document.getElementById('login-error') || document.querySelector('.field__hint');

        console.log(this.form, this.emailInput, this.passwordInput, this._token, this.submitButton);
    }

    bindEvents() {
        // Form Submission Event
        if (this.form) {
            this.form.addEventListener('submit', (e) => this.handleSubmit(e));
        }

        // Input Validation Events
        if (this.emailInput) {
            this.emailInput.addEventListener('input', (e) => this.validateEmail(e.target.value));
        }
    }

    setupValidation() {
        // Email validation pattern
        this.emailPattern = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
    }

    validateEmail(email) {
        const isValid = this.emailPattern.test(email);
        this.updateInputValidation(this.emailInput, isValid);
        return isValid;
    }

    updateInputValidation(input, isValid) {
        if (!input) return;

        if (isValid) {
            input.classList.remove('field__hint--error');
            this.showError('');
        } else {
            input.classList.add('field__hint--error');
            this.showError('Invalid email address');
        }
    }

    showError(message) {
        if (!this.errorMessage) return;
        this.errorMessage.hidden = !message;
        this.errorMessage.textContent = message || '';
    }

    async handleSubmit(event) {
        event.preventDefault();
        if (this.inFlight) return;
        
        const email = (this.emailInput?.value || '').trim();
        const password = this.passwordInput?.value || '';

        if (!this.validateEmail(email) || password.length < 8) {
            this.showError('Please enter a valid email and password');
            return;
        }

        await this.requestLogin();
    }

    async requestLogin() {
        this.inFlight = true;
        if (this.submitButton) this.submitButton.disabled = true;
        this.showError('');

        try {
            const response = await fetch(this.form.action, {
                method: 'POST',
                body: new FormData(this.form),
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                redirect: 'manual'
            });

            const data = await this.parseJsonSafe(response);

            if (response.ok && data && data.ok === true && typeof data.redirect === 'string') {
                if (!data.redirect.startsWith('/')) {
                    this.showError('Login failed. Please try again.');
                    return;
                }

                window.location.assign(data.redirect);
                return;
            }

            this.showError(
                data && data.message
                    ? data.message
                    : 'Those credentials do not match our records.'
            );
        } catch (error) {
            this.showError('Login failed. Please try again.');
        } finally {
            this.inFlight = false;
            if (this.submitButton) this.submitButton.disabled = false;
            if (this.passwordInput) this.passwordInput.value = '';
        }
    }

    async parseJsonSafe(response) {
        const text = await response.text();
        if (!text) return null;
        try {
            return JSON.parse(text);
        } catch {
            return null;
        }
    }
}

document.addEventListener('DOMContentLoaded', () => {
    // Check if we're on the login page
    const loginPage = document.querySelector('.login-container');

    if (loginPage) {
        const loginManager = new LoginManager();
    }
});