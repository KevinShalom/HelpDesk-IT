const DashboardView = async (app) => {
    // Render skeleton layout first
    app.innerHTML = renderLayout(`
        <div class="top-header">
            <h1 class="page-title">Dashboard</h1>
            <div id="date-display" style="color: var(--text-secondary);"></div>
        </div>
        
        <div id="dashboard-content">
            <div style="text-align:center; padding: 3rem;">
                <svg class="animate-spin" style="width:40px;height:40px;color:var(--accent-primary);" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
            </div>
        </div>
    `);

    // Add some simple CSS for the spinner
    if (!document.getElementById('dash-style')) {
        const style = document.createElement('style');
        style.id = 'dash-style';
        style.innerHTML = `@keyframes spin { to { transform: rotate(360deg); } } .animate-spin { animation: spin 1s linear infinite; } .opacity-25 { opacity: 0.25; } .opacity-75 { opacity: 0.75; }`;
        document.head.appendChild(style);
    }

    const contentDiv = document.getElementById('dashboard-content');
    const dateDisplay = document.getElementById('date-display');
    
    // Set date
    const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
    dateDisplay.textContent = new Date().toLocaleDateString('es-ES', options);

    try {
        const response = await api.get('/dashboard/stats');
        const stats = response.data;
        
        const user = api.getUser();
        let slaSection = '';
        
        // SLA is more relevant for technicians and supervisors
        if (user.role.slug !== 'employee') {
            slaSection = `
                <div style="margin-top: 2rem;">
                    <h2 style="font-size: 1.25rem; font-weight: 600; margin-bottom: 1rem;">Acuerdos de Nivel de Servicio (SLA)</h2>
                    <div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));">
                        <div class="stat-card danger">
                            <div class="stat-label">SLA Vencido</div>
                            <div class="stat-value">${stats.sla.breached}</div>
                        </div>
                        <div class="stat-card warning">
                            <div class="stat-label">SLA en Riesgo (< 2h)</div>
                            <div class="stat-value">${stats.sla.warning}</div>
                        </div>
                    </div>
                </div>
            `;
        }

        contentDiv.innerHTML = `
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-label">Total Tickets</div>
                    <div class="stat-value">${stats.overview.total}</div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Nuevos</div>
                    <div class="stat-value" style="color: var(--status-new);">${stats.overview.new}</div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">En Progreso</div>
                    <div class="stat-value" style="color: var(--status-in-progress);">${stats.overview.in_progress}</div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Resueltos</div>
                    <div class="stat-value" style="color: var(--status-resolved);">${stats.overview.resolved}</div>
                </div>
            </div>
            
            ${slaSection}
            
            <div style="margin-top: 2rem;">
                <h2 style="font-size: 1.25rem; font-weight: 600; margin-bottom: 1rem;">Acciones Rápidas</h2>
                <div style="display: flex; gap: 1rem;">
                    <button class="btn btn-primary" onclick="window.location.hash='#tickets/new'">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                        Nuevo Ticket
                    </button>
                    <button class="btn btn-outline" onclick="window.location.hash='#tickets'">
                        Ver Todos los Tickets
                    </button>
                </div>
            </div>
        `;
        
    } catch (error) {
        contentDiv.innerHTML = `
            <div class="glass-panel" style="border-color: var(--danger); text-align: center;">
                <h3 style="color: var(--danger); margin-bottom: 0.5rem;">Error al cargar el dashboard</h3>
                <p>${error.message}</p>
                <button class="btn btn-outline" style="margin-top: 1rem;" onclick="router.navigate('dashboard')">Reintentar</button>
            </div>
        `;
    }

    return {};
};
