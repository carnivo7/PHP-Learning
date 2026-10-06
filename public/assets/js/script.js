/**
 * This is the main script for the application.
 */

class LoginManager {
    constructor() {
        this.initializeElements();
        this.bindEvents();
        this.setupValidation();
    }

    initializeElements() {
        // Form elements
        this.form = document.getElementById('login-form');

        // Input Elements
        this.emailInput = document.getElementById('email');
        this.passwordInput = document.getElementById('password');
        this._token = document.querySelector('input[name="_token"]');

        // Submit Button
        this.submitButton = document.querySelector('button[type="submit"]');

        // Error Message
        this.errorMessage = document.querySelector('.field__hint');

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
        console.log(email);
        const isValid = this.emailPattern.test(email);
        this.updateInputValidation(this.emailInput, isValid);
        return isValid;
    }

    updateInputValidation(input, isValid) {
        if (!input) return;

        if (isValid) {
            input.classList.remove('field__hint--error');
            this.errorMessage.textContent = '';
        } else {
            input.classList.add('field__hint--error');
            this.errorMessage.textContent = 'Invalid email address';   
        }
    }

    async handleSubmit(event) {
        event.preventDefault();
        
        const email = this.emailInput.value.trim();

        if (!this.validateEmail(email)) {
            return;
        }

        await this.requestLogin();
    }
}

document.addEventListener('DOMContentLoaded', () => {
    // Check if we're on the login page
    const loginPage = document.querySelector('.login-container');

    if (loginPage) {
        const loginManager = new LoginManager();
    }
});