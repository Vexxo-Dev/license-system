/**
 * login.js – LicensePro Enterprise Login Page
 */

(function () {
  'use strict';

  const loginForm      = document.getElementById('loginForm');
  const workEmail      = document.getElementById('workEmail');
  const password       = document.getElementById('password');
  const emailError     = document.getElementById('emailError');
  const passwordError  = document.getElementById('passwordError');
  const loginAlert     = document.getElementById('loginAlert');
  const loginAlertMsg  = document.getElementById('loginAlertMsg');
  const signInBtn      = document.getElementById('signInBtn');
  const signInSpinner  = document.getElementById('signInSpinner');
  const togglePassword = document.getElementById('togglePassword');
  const toggleIcon     = document.getElementById('togglePasswordIcon');
  const rememberMe     = document.getElementById('rememberMe');

  // ── Prefill email from localStorage if "remember me" was checked
  const savedEmail = localStorage.getItem('lp_remembered_email');
  if (savedEmail) {
    workEmail.value = savedEmail;
    rememberMe.checked = true;
  }

  // ── Toggle password visibility
  togglePassword.addEventListener('click', function () {
    const isPassword = password.type === 'password';
    password.type = isPassword ? 'text' : 'password';
    toggleIcon.className = isPassword ? 'bi bi-eye-slash' : 'bi bi-eye';
  });

  // ── Clear errors on input
  workEmail.addEventListener('input', function () {
    clearFieldError(workEmail, emailError);
    hideAlert();
  });

  password.addEventListener('input', function () {
    clearFieldError(password, passwordError);
    hideAlert();
  });

  // ── Form submission
  loginForm.addEventListener('submit', function (e) {
    e.preventDefault();
    hideAlert();

    const emailVal    = workEmail.value.trim();
    const passwordVal = password.value;
    let hasError      = false;

    // Validate email
    if (!emailVal || !isValidEmail(emailVal)) {
      showFieldError(workEmail, emailError);
      hasError = true;
    } else {
      clearFieldError(workEmail, emailError);
    }

    // Validate password
    if (!passwordVal) {
      showFieldError(password, passwordError);
      hasError = true;
    } else {
      clearFieldError(password, passwordError);
    }

    if (hasError) return;

    // Handle "remember me"
    if (rememberMe.checked) {
      localStorage.setItem('lp_remembered_email', emailVal);
    } else {
      localStorage.removeItem('lp_remembered_email');
    }

    // Simulate authentication
    setLoadingState(true);

    setTimeout(function () {
      setLoadingState(false);

      // Demo credentials check (replace with real API call)
      if (emailVal === 'admin@organization.com' && passwordVal === 'password') {
        window.location.href = 'dashboard.html';
      } else {
        showAlert('Invalid email or password. Please try again.');
      }
    }, 1200);
  });

  // ── Helpers

  function isValidEmail(val) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(val);
  }

  function showFieldError(inputEl, errorEl) {
    inputEl.classList.add('input-error');
    errorEl.classList.remove('d-none');
  }

  function clearFieldError(inputEl, errorEl) {
    inputEl.classList.remove('input-error');
    errorEl.classList.add('d-none');
  }

  function showAlert(msg) {
    loginAlertMsg.textContent = msg;
    loginAlert.classList.remove('d-none');
  }

  function hideAlert() {
    loginAlert.classList.add('d-none');
  }

  function setLoadingState(loading) {
    signInBtn.disabled = loading;
    signInSpinner.classList.toggle('d-none', !loading);
  }
})();
