// ReTool Campus — small client-side enhancements.
// The server always re-validates everything below; this is UX sugar only.

document.addEventListener('DOMContentLoaded', () => {

    // Keep the "end date" min in sync with the chosen "start date"
    // on the borrow-request form (item.php).
    const startInput = document.getElementById('start_date');
    const endInput = document.getElementById('end_date');
    if (startInput && endInput) {
        const syncMin = () => {
            endInput.min = startInput.value || new Date().toISOString().slice(0, 10);
            if (endInput.value && endInput.value < endInput.min) {
                endInput.value = endInput.min;
            }
        };
        startInput.addEventListener('change', syncMin);
        syncMin();
    }

    // Live password-match hint on register/profile forms.
    const pw = document.getElementById('password') || document.getElementById('new_password');
    const pw2 = document.getElementById('password_confirm') || document.getElementById('new_password_confirm');
    if (pw && pw2) {
        const check = () => {
            pw2.style.borderColor = (pw2.value && pw2.value !== pw.value)
                ? 'var(--rust)'
                : '';
        };
        pw.addEventListener('input', check);
        pw2.addEventListener('input', check);
    }

    // Auto-dismiss flash messages after a few seconds.
    document.querySelectorAll('.alert').forEach((box) => {
        setTimeout(() => {
            box.style.transition = 'opacity .4s ease';
            box.style.opacity = '0';
            setTimeout(() => box.remove(), 400);
        }, 5000);
    });
});
