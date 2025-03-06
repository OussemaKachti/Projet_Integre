// Improved auth-check.js

document.addEventListener('DOMContentLoaded', function() {
    // Only run this check if the user is not logged in
    if (!window.isUserLoggedIn) {
        // List of paths that don't require authentication (keep in sync with server-side)
        const publicPaths = [
            '/login', 
            '/user/sign-up', 
            '/home', 
            '/access-denied', 
            '/confirm-email',
            '/assets/'
        ];
        
        // Current path
        const currentPath = window.location.pathname;
        
        // If not on a public path, redirect to access denied
        const isPublicPath = publicPaths.some(path => currentPath.startsWith(path));
        
        if (!isPublicPath) {
            console.log('Unauthorized access detected, redirecting...');
            window.location.href = '/access-denied';
        }
    }
});

// Make sure to include this in your base template:
// <script>
//     window.isUserLoggedIn = {% if app.user %}true{% else %}false{% endif %};
// </script>
// <script src="{{ asset('js/auth-check.js') }}"></script>