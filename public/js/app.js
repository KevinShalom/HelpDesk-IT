// Register routes
router.addRoute('login', LoginView);
router.addRoute('dashboard', DashboardView);
router.addRoute('tickets', TicketsView);
router.addRoute('tickets/new', TicketNewView);
router.addRoute('tickets/:id', TicketDetailView);

// Wait for DOM to load before initializing
document.addEventListener('DOMContentLoaded', () => {
    // Check if valid token exists by verifying with /me endpoint
    const token = api.getToken();
    
    if (token) {
        api.get('/me')
            .then(data => {
                // Update stored user just in case roles changed
                api.setUser(data.user);
                router.init();
            })
            .catch(() => {
                // Token invalid
                api.removeToken();
                router.init();
            });
    } else {
        router.init();
    }
});
