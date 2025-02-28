// Add this to your main JavaScript file or create a new one: assets/js/auth-check.js

document.addEventListener('DOMContentLoaded', function() {
    // Check if user is logged in (this assumes you add a global JS variable from Twig)
    if (!window.isUserLoggedIn) {
        // List of paths that don't require authentication
        const publicPaths = ['/login', '/user/sign-up', '/home', '/access-denied'];
        
        // Current path
        const currentPath = window.location.pathname;
        
        // If not on a public path, redirect to access denied
        if (!publicPaths.some(path => currentPath.startsWith(path))) {
            window.location.href = '/access-denied';
        }
    }
});

// Then in your base template:
// <script>
//     window.isUserLoggedIn = {% if app.user %}true{% else %}false{% endif %};
// </script>
// <script src="{{ asset('build/js/auth-check.js') }}"></script>