// Balanced auth-check.js with reset password exception
document.addEventListener('DOMContentLoaded', function() {
    // Only run this check if the user is not logged in
    if (!window.isUserLoggedIn) {
        // Current path
        const currentPath = window.location.pathname;
        
        // IMPORTANT: Allow reset password paths unconditionally
        if (currentPath.startsWith('/reset-password')) {
            console.log('Reset password path detected, allowing access');
            return; // Skip all checks for reset password paths
        }
        
        // List of paths that don't require authentication (keep in sync with server-side)
        const publicPaths = [
            '/login', 
            '/logout',
            '/user/sign-up', 
            '/home', 
            '/',                 // Root path
            '/access-denied', 
            '/confirm-email',
            '/assets/'
            // DON'T add /reset-password here, we're handling it with the special check above
        ];
        
        // If not on a public path, redirect to access denied
        const isPublicPath = publicPaths.some(path => currentPath.startsWith(path));
        
        if (!isPublicPath) {
            console.log('Unauthorized access detected, redirecting...');
            window.location.href = '/access-denied';
        }
    }
});