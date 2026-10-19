'use strict';
document.querySelectorAll('[data-password]').forEach(function(button) {
  button.addEventListener('click', function() {
    const input = document.getElementById(button.dataset.password);
    const visible = input.type === 'password';
    input.type = visible ? 'text' : 'password';
    button.textContent = visible ? 'Hide' : 'Show';
    button.setAttribute('aria-label', visible ? 'Sembunyikan password' : 'Tampilkan password');
    button.setAttribute('aria-pressed', visible ? 'true' : 'false');
  });
});
