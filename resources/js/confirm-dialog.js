/**
 * Custom Confirm Dialog Helper (SweetAlert2)
 *
 * Replace native browser confirm() with a SweetAlert2 dialog.
 * Usage: const confirmed = await customConfirm('Your message here');
 */

import Swal from 'sweetalert2';

const colorMap = {
    red: '#dc2626',
    green: '#16a34a',
    blue: '#2563eb',
};

const escapeHtml = (value) => String(value)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');

// Global custom confirm function
window.customConfirm = async function (message, options = {}) {
    const result = await Swal.fire({
        title: 'Konfirmasi',
        html: escapeHtml(message).replace(/\n/g, '<br>'),
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: options.confirmLabel || 'Hapus',
        cancelButtonText: 'Batal',
        confirmButtonColor: colorMap[options.confirmColor] || colorMap.red,
        focusCancel: true,
    });

    return result.isConfirmed;
};

/**
 * Helper function to convert native confirm in form submissions
 *
 * Usage in Blade:
 * <form method="POST" action="..." x-data @submit.prevent="handleConfirmSubmit">
 *   ...
 * </form>
 */
window.handleConfirmSubmit = async function (event, message) {
    const confirmed = await customConfirm(message);
    if (confirmed) {
        event.target.submit();
    }
};