/**
 * Custom Confirm Dialog Helper
 * 
 * Replace native browser confirm() with a beautiful custom dialog
 * Usage: const confirmed = await customConfirm('Your message here');
 */

// Global custom confirm function
window.customConfirm = function(message) {
    return new Promise((resolve) => {
        // Dispatch event to open the confirm dialog
        window.dispatchEvent(new CustomEvent('open-confirm', {
            detail: {
                message: message,
                callback: () => resolve(true)
            }
        }));
        
        // Handle cancel - resolve to false when cancelled
        const handleCancel = () => {
            resolve(false);
        };
        
        // Listen for one-time cancel event
        window.addEventListener('confirm-cancelled', handleCancel, { once: true });
        
        // Fallback timeout to prevent hanging promises (30 seconds)
        setTimeout(() => {
            resolve(false);
        }, 30000);
    });
};

/**
 * Helper function to convert native confirm in form submissions
 * 
 * Usage in Blade:
 * <form method="POST" action="..." x-data @submit.prevent="handleConfirmSubmit">
 *   ...
 * </form>
 */
window.handleConfirmSubmit = async function(event, message) {
    const confirmed = await customConfirm(message);
    if (confirmed) {
        event.target.submit();
    }
};

console.log('Custom confirm dialog helper loaded');
