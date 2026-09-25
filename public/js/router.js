/**
 * Simple Hash Router
 */
const router = {
    routes: {},
    currentRoute: null,
    
    addRoute(path, viewFunction) {
        this.routes[path] = viewFunction;
    },
    
    async navigate(path) {
        // Remove trailing slash if any and hash
        let cleanPath = path.replace(/^#/, '');
        if (cleanPath === '') cleanPath = '/';
        
        // Handle auth redirects
        const isAuthenticated = !!api.getToken();
        
        if (!isAuthenticated && cleanPath !== 'login') {
            window.location.hash = '#login';
            return;
        }
        
        if (isAuthenticated && cleanPath === 'login') {
            window.location.hash = '#dashboard';
            return;
        }

        const appDiv = document.getElementById('app');
        
        // Clean up previous view if it had a cleanup function
        if (this.currentRoute && this.currentRoute.cleanup) {
            this.currentRoute.cleanup();
        }

        // Find matching route
        let routeView = this.routes[cleanPath];
        let params = [];
        
        // Handle dynamic routes (simple implementation)
        if (!routeView) {
            if (cleanPath === 'tickets/new') {
                routeView = this.routes['tickets/new'];
            } else if (cleanPath.startsWith('tickets/')) {
                const id = cleanPath.split('/')[1];
                if (id) {
                    routeView = this.routes['tickets/:id'];
                    params = [id];
                }
            }
        }
        
        // Very basic 404 fallback
        if (!routeView) {
            routeView = this.routes['dashboard'];
        }

        try {
            appDiv.innerHTML = '<div style="display:flex; justify-content:center; align-items:center; height:100vh;">Cargando...</div>';
            this.currentRoute = await routeView(appDiv, ...params);
            
            // Re-bind sidebar navigation if layout was rendered
            this.bindNavigation();
        } catch (error) {
            console.error("Router error:", error);
            appDiv.innerHTML = `<div style="padding: 2rem; color: #ef4444;">Error cargando la vista: ${error.message}</div>`;
        }
    },
    
    bindNavigation() {
        const links = document.querySelectorAll('.nav-link[data-route]');
        links.forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                const route = link.getAttribute('data-route');
                window.location.hash = `#${route}`;
            });
            
            // Set active class
            if (window.location.hash.includes(link.getAttribute('data-route'))) {
                link.classList.add('active');
            } else {
                link.classList.remove('active');
            }
        });
    },

    init() {
        window.addEventListener('hashchange', () => {
            this.navigate(window.location.hash);
        });
        
        // Initial load
        this.navigate(window.location.hash || '#dashboard');
    }
};

/**
 * Common Layout Renderer
 */
function renderLayout(contentHTML) {
    const user = api.getUser() || { name: 'Usuario', role: { name: 'Rol' } };
    
    return `
        <div class="app-layout">
            <aside class="sidebar animate-fade-in">
                <div class="sidebar-logo">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
                        <polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline>
                        <line x1="12" y1="22.08" x2="12" y2="12"></line>
                    </svg>
                    HelpDesk
                </div>
                
                <nav class="sidebar-nav">
                    <a href="#dashboard" class="nav-link" data-route="dashboard">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right:10px"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
                        Dashboard
                    </a>
                    <a href="#tickets" class="nav-link" data-route="tickets">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right:10px"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                        Tickets
                    </a>
                    <!-- Agrega más enlaces aquí -->
                </nav>
                
                <div style="margin-top: auto; padding-top: 1rem; border-top: 1px solid var(--border-color);">
                    <div class="user-profile">
                        <div class="avatar">${user.name.charAt(0)}</div>
                        <div style="flex:1; overflow:hidden;">
                            <div style="font-weight:600; font-size:0.875rem; white-space:nowrap; text-overflow:ellipsis;">${user.name}</div>
                            <div style="font-size:0.75rem; color:var(--text-secondary);">${user.role.name}</div>
                        </div>
                    </div>
                    <button id="logout-btn" class="btn btn-outline" style="width: 100%; margin-top: 1rem; padding: 0.5rem; font-size:0.75rem;">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
                        Cerrar Sesión
                    </button>
                </div>
            </aside>
            
            <main class="main-content animate-fade-in">
                ${contentHTML}
            </main>
        </div>
    `;
}

// Global logout handler setup
document.addEventListener('click', async (e) => {
    if (e.target.closest('#logout-btn')) {
        try {
            await api.post('/logout');
        } catch(e) {} // Ignore error if already logged out
        api.removeToken();
        window.location.hash = '#login';
    }
});
