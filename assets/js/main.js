/**
 * SURAS — shared front-end behaviour
 * Mobile nav toggle, password visibility toggle, and light client-side
 * validation for the login form. No dependencies.
 */
(function () {
  'use strict';

  document.addEventListener('DOMContentLoaded', function () {
    initMobileNav();
    initPasswordToggle();
    initLoginValidation();
    initRegisterValidation();
  });

  /* ---------------------------------------------------------------
     Mobile navigation
     --------------------------------------------------------------- */
  function initMobileNav() {
    var toggle = document.querySelector('.nav-toggle');
    var links = document.querySelector('.nav-links');
    if (!toggle || !links) return;

    toggle.addEventListener('click', function () {
      var isOpen = links.classList.toggle('is-open');
      toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    });

    // Close the panel after a nav link is tapped.
    links.addEventListener('click', function (e) {
      if (e.target.tagName === 'A') {
        links.classList.remove('is-open');
        toggle.setAttribute('aria-expanded', 'false');
      }
    });
  }

  /* ---------------------------------------------------------------
     Password show/hide
     --------------------------------------------------------------- */
  function initPasswordToggle() {
    var toggles = document.querySelectorAll('[data-toggle-password]');
    toggles.forEach(function (toggle) {
      var targetId = toggle.getAttribute('data-toggle-password');
      var input = document.getElementById(targetId);
      if (!input) return;

      toggle.addEventListener('click', function () {
        var isHidden = input.type === 'password';
        input.type = isHidden ? 'text' : 'password';
        toggle.textContent = isHidden ? 'Hide' : 'Show';
        toggle.setAttribute('aria-pressed', isHidden ? 'true' : 'false');
      });
    });
  }

  /* ---------------------------------------------------------------
     Login form — minimal client-side validation.
     The server (login.php) is the source of truth; this only saves
     the user a round trip for obviously-empty or malformed input.
     --------------------------------------------------------------- */
  function initLoginValidation() {
    var form = document.getElementById('login-form');
    if (!form) return;

    var email = document.getElementById('email');
    var password = document.getElementById('password');
    var errorBox = document.getElementById('login-client-error');

    form.addEventListener('submit', function (e) {
      var message = '';

      if (!email.value.trim()) {
        message = 'Please enter your university email address.';
      } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value.trim())) {
        message = 'That email address doesn\'t look quite right.';
      } else if (!password.value) {
        message = 'Please enter your password.';
      }

      if (message) {
        e.preventDefault();
        if (errorBox) {
          errorBox.textContent = message;
          errorBox.hidden = false;
        }
        return;
      }

      if (errorBox) errorBox.hidden = true;

      var submitBtn = form.querySelector('button[type="submit"]');
      if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.textContent = 'Signing in…';
      }
    });
  }

  /* ---------------------------------------------------------------
     Register form — confirm the two submit buttons disable, and
     catch an obvious password mismatch before it round-trips.
     --------------------------------------------------------------- */
  function initRegisterValidation() {
    var form = document.getElementById('register-form');
    if (!form) return;

    var password = document.getElementById('password');
    var confirm = document.getElementById('confirm_password');

    form.addEventListener('submit', function (e) {
      if (password.value.length < 8) {
        e.preventDefault();
        password.focus();
        return;
      }
      if (password.value !== confirm.value) {
        e.preventDefault();
        confirm.setCustomValidity('Passwords do not match');
        confirm.reportValidity();
        confirm.focus();
        return;
      }
      confirm.setCustomValidity('');

      var submitBtn = form.querySelector('button[type="submit"]');
      if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.textContent = 'Creating account…';
      }
    });

    confirm.addEventListener('input', function () {
      confirm.setCustomValidity('');
    });
  }
})();
