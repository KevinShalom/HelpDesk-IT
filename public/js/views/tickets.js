const TicketsView = async (app) => {
    app.innerHTML = renderLayout(`
        <div class="top-header">
            <h1 class="page-title">Tickets</h1>
            <button class="btn btn-primary" onclick="window.location.hash='#tickets/new'">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                Nuevo Ticket
            </button>
        </div>
        
        <div class="glass-panel" style="padding: 0; overflow: hidden;">
            <div style="padding: 1.5rem; border-bottom: 1px solid var(--border-color); display: flex; gap: 1rem; flex-wrap: wrap;">
                <!-- Basic filters placeholder -->
                <input type="text" class="form-control" placeholder="Buscar ticket..." style="max-width: 300px;">
                <select class="form-control" style="max-width: 200px;">
                    <option value="">Todos los estados</option>
                    <option value="new">Nuevos</option>
                    <option value="in_progress">En Progreso</option>
                </select>
            </div>
            
            <div id="tickets-table-container">
                <div style="padding: 3rem; text-align: center; color: var(--text-secondary);">
                    Cargando tickets...
                </div>
            </div>
        </div>
    `);

    // Load actual tickets
    try {
        const response = await api.get('/tickets');
        const container = document.getElementById('tickets-table-container');
        
        if (response.data.length === 0) {
            container.innerHTML = `
                <div style="padding: 3rem; text-align: center; color: var(--text-secondary);">
                    No hay tickets para mostrar.
                </div>
            `;
            return {};
        }

        let rowsHTML = '';
        response.data.forEach(ticket => {
            const date = new Date(ticket.created_at).toLocaleDateString('es-ES');
            rowsHTML += `
                <tr style="border-bottom: 1px solid var(--border-color); cursor: pointer; transition: background 0.2s;" class="ticket-row" onclick="window.location.hash='#tickets/${ticket.id}'">
                    <td style="padding: 1rem 1.5rem; font-weight: 500;">${ticket.ticket_number}</td>
                    <td style="padding: 1rem 1.5rem;">${ticket.title}</td>
                    <td style="padding: 1rem 1.5rem;">
                        <span class="badge badge-${ticket.status.replace('_', '-')}">${ticket.status.replace('_', ' ')}</span>
                    </td>
                    <td style="padding: 1rem 1.5rem; color: var(--text-secondary);">${date}</td>
                </tr>
            `;
        });

        container.innerHTML = `
            <table style="width: 100%; border-collapse: collapse; text-align: left;">
                <thead>
                    <tr style="background: rgba(0,0,0,0.2); border-bottom: 1px solid var(--border-color);">
                        <th style="padding: 1rem 1.5rem; font-weight: 500; color: var(--text-secondary);">ID</th>
                        <th style="padding: 1rem 1.5rem; font-weight: 500; color: var(--text-secondary);">Título</th>
                        <th style="padding: 1rem 1.5rem; font-weight: 500; color: var(--text-secondary);">Estado</th>
                        <th style="padding: 1rem 1.5rem; font-weight: 500; color: var(--text-secondary);">Fecha</th>
                    </tr>
                </thead>
                <tbody>
                    ${rowsHTML}
                </tbody>
            </table>
        `;
        
        // Add hover effect via JS since it's inline logic
        document.querySelectorAll('.ticket-row').forEach(row => {
            row.addEventListener('mouseenter', () => row.style.background = 'rgba(99, 102, 241, 0.05)');
            row.addEventListener('mouseleave', () => row.style.background = 'transparent');
        });

    } catch (error) {
        document.getElementById('tickets-table-container').innerHTML = `
            <div style="padding: 2rem; color: var(--danger); text-align: center;">Error: ${error.message}</div>
        `;
    }

    return {};
};
