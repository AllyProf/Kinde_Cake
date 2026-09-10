(function () {
  'use strict';

  document.addEventListener('DOMContentLoaded', function () {
    var modal = document.getElementById('resetPasswordModal');
    var form = document.getElementById('resetPasswordForm');
    var staffName = document.getElementById('resetPasswordStaffName');
    var staffIdInput = document.getElementById('resetStaffId');
    if (!modal || !form || !staffName) return;

    function openModal(btn) {
      form.action = btn.getAttribute('data-action') || '';
      staffName.textContent = btn.getAttribute('data-staff-name') || 'this staff member';
      if (staffIdInput) {
        staffIdInput.value = btn.getAttribute('data-staff-id') || '';
      }

      var password = document.getElementById('staffPassword');
      var confirm = document.getElementById('staffPasswordConfirm');
      if (password && !password.classList.contains('is-invalid')) password.value = '';
      if (confirm && !confirm.classList.contains('is-invalid')) confirm.value = '';

      if (window.jQuery) {
        window.jQuery(modal).modal('show');
      }
    }

    document.querySelectorAll('.js-reset-password').forEach(function (btn) {
      btn.addEventListener('click', function () {
        openModal(btn);
      });
    });

    if (staffIdInput && staffIdInput.value) {
      var reopenBtn = document.querySelector('.js-reset-password[data-staff-id="' + staffIdInput.value + '"]');
      if (reopenBtn) {
        openModal(reopenBtn);
      }
    }
  });
})();
