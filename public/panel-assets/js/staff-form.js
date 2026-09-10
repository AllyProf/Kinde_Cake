(function () {
  'use strict';

  function syncPhoneFull() {
    var localInput = document.getElementById('staffPhoneLocal');
    var fullInput = document.getElementById('staffPhoneFull');
    if (!localInput || !fullInput) return;

    var digits = (localInput.value || '').replace(/\D/g, '').slice(0, 9);
    localInput.value = digits;
    fullInput.value = digits ? '+255' + digits : '';
  }

  function generatePassword(length) {
    var chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789!@#$%';
    var password = '';
    var array = new Uint32Array(length);
    window.crypto.getRandomValues(array);
    for (var i = 0; i < length; i++) {
      password += chars[array[i] % chars.length];
    }
    return password;
  }

  function scorePassword(value) {
    var password = value || '';
    if (!password.length) {
      return { level: '', label: 'Enter a password to check strength', score: 0 };
    }

    var score = 0;
    if (password.length >= 8) score++;
    if (password.length >= 12) score++;
    if (/[a-z]/.test(password)) score++;
    if (/[A-Z]/.test(password)) score++;
    if (/[0-9]/.test(password)) score++;
    if (/[^A-Za-z0-9]/.test(password)) score++;

    if (score <= 2) return { level: 'weak', label: 'Weak password', score: score };
    if (score <= 3) return { level: 'fair', label: 'Fair password', score: score };
    if (score <= 4) return { level: 'good', label: 'Good password', score: score };
    return { level: 'strong', label: 'Strong password', score: score };
  }

  function updatePasswordStrength() {
    var password = document.getElementById('staffPassword');
    var fill = document.getElementById('passwordStrengthFill');
    var label = document.getElementById('passwordStrengthLabel');
    if (!password || !fill || !label) return;

    var result = scorePassword(password.value);
    fill.className = 'password-strength__fill' + (result.level ? ' is-' + result.level : '');
    label.className = 'password-strength__label text-muted' + (result.level ? ' is-' + result.level : '');
    label.textContent = result.label;
  }

  function setPasswordFields(value) {
    var password = document.getElementById('staffPassword');
    var confirm = document.getElementById('staffPasswordConfirm');
    if (password) {
      password.value = value;
      password.type = 'text';
      updateToggleIcon(password);
    }
    if (confirm) {
      confirm.value = value;
      confirm.type = 'text';
      updateToggleIcon(confirm);
    }
    updatePasswordStrength();
  }

  function updateToggleIcon(input) {
    if (!input || !input.id) return;
    var btn = document.querySelector('.js-toggle-password[data-target="#' + input.id + '"]');
    if (!btn) return;
    var icon = btn.querySelector('i');
    if (!icon) return;
    icon.className = input.type === 'password' ? 'fa fa-eye' : 'fa fa-eye-slash';
  }

  document.addEventListener('DOMContentLoaded', function () {
    var localInput = document.getElementById('staffPhoneLocal');
    if (localInput) {
      syncPhoneFull();
      localInput.addEventListener('input', syncPhoneFull);
      localInput.addEventListener('blur', syncPhoneFull);
    }

    var form = localInput ? localInput.closest('form') : null;
    if (form) {
      form.addEventListener('submit', syncPhoneFull);
    }

    var passwordInput = document.getElementById('staffPassword');
    if (passwordInput) {
      passwordInput.addEventListener('input', updatePasswordStrength);
      updatePasswordStrength();
    }

    document.querySelectorAll('.js-toggle-password').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var target = document.querySelector(btn.getAttribute('data-target'));
        if (!target) return;
        target.type = target.type === 'password' ? 'text' : 'password';
        updateToggleIcon(target);
      });
    });

    document.querySelectorAll('.js-generate-password').forEach(function (btn) {
      btn.addEventListener('click', function () {
        setPasswordFields(generatePassword(12));
      });
    });
  });
})();
