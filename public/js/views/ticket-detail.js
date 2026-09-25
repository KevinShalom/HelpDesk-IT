const TicketDetailView = async (app, ticketId) => {
    // Basic Skeleton
    app.innerHTML = renderLayout(`
        <div class="top-header">
            <h1 class="page-title">Detalle del Ticket</h1>
            <button class="btn btn-outline" onclick="window.location.hash='#tickets'">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 8px"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
                Volver
            </button>
        </div>
        <div id="ticket-detail-content">
            <div style="text-align:center; padding: 3rem;">
                <svg class="animate-spin" style="width:40px;height:40px;color:var(--accent-primary);" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
            </div>
        </div>
    `);

    try {
        const response = await api.get(`/tickets/${ticketId}`);
        const t = response.data;
        const user = api.getUser();
        const role = user.role.slug;

        // Date formatting
        const dateOpt = { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute:'2-digit' };
        const createdAt = new Date(t.created_at).toLocaleDateString('es-ES', dateOpt);
        
        // SLA Display
        let slaDisplay = '';
        if (t.sla_due_at) {
            const slaDate = new Date(t.sla_due_at).toLocaleDateString('es-ES', dateOpt);
            let slaColor = 'var(--text-secondary)';
            if (response.sla_status === 'breached') slaColor = 'var(--danger)';
            else if (response.sla_status === 'warning') slaColor = 'var(--warning)';
            else if (response.sla_status === 'fulfilled') slaColor = 'var(--success)';
            
            slaDisplay = `
                <div style="margin-top: 1rem; padding: 0.75rem; background: rgba(0,0,0,0.2); border-radius: var(--radius-sm); border-left: 4px solid ${slaColor}">
                    <div style="font-size: 0.75rem; color: var(--text-secondary); text-transform: uppercase;">Vencimiento SLA</div>
                    <div style="font-weight: 600; color: ${slaColor}">${slaDate}</div>
                </div>
            `;
        }

        // Action Forms (Assign / Status)
        let actionForms = '';
        if (['admin', 'supervisor'].includes(role)) {
            actionForms += `
                <div style="margin-bottom: 1.5rem;">
                    <label style="display:block; font-size:0.75rem; color:var(--text-secondary); margin-bottom:0.5rem;">Asignar a Técnico</label>
                    <div style="display:flex; gap:0.5rem;">
                        <select id="assign-select" class="form-control" style="flex:1;">
                            <option value="">Cargando técnicos...</option>
                        </select>
                        <button id="btn-assign" class="btn btn-outline" style="padding: 0 1rem;">Asignar</button>
                    </div>
                </div>
            `;
        }

        if (['admin', 'supervisor', 'technician'].includes(role) || (role === 'employee' && ['resolved', 'closed'].includes(t.status))) {
            const statuses = [
                { val: 'new', label: 'Nuevo' },
                { val: 'assigned', label: 'Asignado' },
                { val: 'in_progress', label: 'En Progreso' },
                { val: 'waiting_user', label: 'Esperando al Usuario' },
                { val: 'resolved', label: 'Resuelto' },
                { val: 'closed', label: 'Cerrado' }
            ];
            
            actionForms += `
                <div>
                    <label style="display:block; font-size:0.75rem; color:var(--text-secondary); margin-bottom:0.5rem;">Cambiar Estado</label>
                    <div style="display:flex; gap:0.5rem;">
                        <select id="status-select" class="form-control" style="flex:1;">
                            ${statuses.map(s => `<option value="${s.val}" ${t.status === s.val ? 'selected' : ''}>${s.label}</option>`).join('')}
                        </select>
                        <button id="btn-status" class="btn btn-outline" style="padding: 0 1rem;">Cambiar</button>
                    </div>
                </div>
            `;
        }

        // Render content
        document.getElementById('ticket-detail-content').innerHTML = `
            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 2rem;">
                
                <!-- Columna Izquierda: Info y Comentarios -->
                <div style="display: flex; flex-direction: column; gap: 1.5rem;">
                    
                    <div class="glass-panel">
                        <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:1.5rem;">
                            <div>
                                <div style="font-size:0.875rem; color:var(--text-secondary); margin-bottom:0.25rem;">${t.ticket_number}</div>
                                <h2 style="font-size:1.5rem; font-weight:600; line-height:1.2;">${t.title}</h2>
                            </div>
                            <span class="badge badge-${t.status.replace('_', '-')}">${t.status.replace('_', ' ')}</span>
                        </div>
                        
                        <div style="background: rgba(0,0,0,0.15); padding: 1.5rem; border-radius: var(--radius-md); font-size:0.95rem; line-height:1.6; white-space: pre-wrap;">${t.description}</div>
                    </div>

                    <!-- Comentarios -->
                    <div class="glass-panel">
                        <h3 style="font-size: 1.125rem; font-weight: 600; margin-bottom: 1.5rem;">Comentarios</h3>
                        
                        <div style="display: flex; flex-direction: column; gap: 1rem; margin-bottom: 2rem;">
                            ${t.comments.length === 0 ? '<div style="color:var(--text-secondary); text-align:center;">No hay comentarios aún.</div>' : ''}
                            ${t.comments.map(c => `
                                <div style="background: ${c.is_internal ? 'rgba(245, 158, 11, 0.1)' : 'rgba(0,0,0,0.2)'}; border: 1px solid ${c.is_internal ? 'rgba(245, 158, 11, 0.2)' : 'var(--border-color)'}; padding: 1rem; border-radius: var(--radius-md);">
                                    <div style="display:flex; justify-content:space-between; margin-bottom:0.5rem; font-size:0.75rem;">
                                        <div style="font-weight:600; color:var(--text-primary);">
                                            ${c.user.name} 
                                            ${c.is_internal ? '<span style="color:var(--warning); margin-left:0.5rem;">(Nota Interna)</span>' : ''}
                                        </div>
                                        <div style="color:var(--text-secondary);">${new Date(c.created_at).toLocaleDateString('es-ES', dateOpt)}</div>
                                    </div>
                                    <div style="font-size:0.875rem; white-space:pre-wrap;">${c.comment}</div>
                                </div>
                            `).join('')}
                        </div>

                        <!-- Add Comment -->
                        <div style="border-top: 1px solid var(--border-color); padding-top: 1.5rem;">
                            <textarea id="comment-text" class="form-control" rows="3" placeholder="Escribe un comentario..." style="margin-bottom: 1rem;"></textarea>
                            
                            <div style="display:flex; justify-content:space-between; align-items:center;">
                                <div>
                                    ${role !== 'employee' ? `
                                        <label style="display:flex; align-items:center; gap:0.5rem; font-size:0.875rem; cursor:pointer;">
                                            <input type="checkbox" id="comment-internal"> Es nota interna
                                        </label>
                                    ` : ''}
                                </div>
                                <button id="btn-add-comment" class="btn btn-primary">
                                    Enviar Comentario
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Columna Derecha: Sidebar info -->
                <div style="display: flex; flex-direction: column; gap: 1.5rem;">
                    
                    ${actionForms ? `
                        <div class="glass-panel" style="padding: 1.5rem;">
                            <h3 style="font-size: 1rem; font-weight: 600; margin-bottom: 1rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.5rem;">Acciones</h3>
                            ${actionForms}
                        </div>
                    ` : ''}

                    <div class="glass-panel" style="padding: 1.5rem;">
                        <h3 style="font-size: 1rem; font-weight: 600; margin-bottom: 1rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.5rem;">Detalles</h3>
                        
                        <div style="display:flex; flex-direction:column; gap:1rem; font-size:0.875rem;">
                            <div>
                                <div style="color:var(--text-secondary); font-size:0.75rem;">Solicitante</div>
                                <div style="font-weight:500;">${t.user.name}</div>
                            </div>
                            
                            <div>
                                <div style="color:var(--text-secondary); font-size:0.75rem;">Asignado a</div>
                                <div style="font-weight:500;">${t.technician ? t.technician.name : '<span style="color:var(--warning)">Sin asignar</span>'}</div>
                            </div>
                            
                            <div>
                                <div style="color:var(--text-secondary); font-size:0.75rem;">Categoría</div>
                                <div>${t.category.name}</div>
                            </div>
                            
                            <div>
                                <div style="color:var(--text-secondary); font-size:0.75rem;">Prioridad</div>
                                <div style="display:flex; align-items:center; gap:0.5rem;">
                                    <div style="width:8px; height:8px; border-radius:50%; background:${t.priority.color};"></div>
                                    ${t.priority.name}
                                </div>
                            </div>

                            <div>
                                <div style="color:var(--text-secondary); font-size:0.75rem;">Creado el</div>
                                <div>${createdAt}</div>
                            </div>
                        </div>
                        
                        ${slaDisplay}
                    </div>
                </div>
            </div>
        `;

        // Bind events
        
        // Load technicians if needed
        if (['admin', 'supervisor'].includes(role)) {
            api.get('/catalogs/technicians').then(techRes => {
                const select = document.getElementById('assign-select');
                if (select) {
                    select.innerHTML = '<option value="">Selecciona técnico...</option>' + 
                        techRes.data.map(u => `<option value="${u.id}">${u.name}</option>`).join('');
                }
            });

            const btnAssign = document.getElementById('btn-assign');
            if (btnAssign) {
                btnAssign.addEventListener('click', async () => {
                    const techId = document.getElementById('assign-select').value;
                    if (!techId) {
                        window.showToast('Selecciona un técnico', 'error');
                        return;
                    }
                    try {
                        btnAssign.disabled = true;
                        btnAssign.innerText = '...';
                        await api.patch(`/tickets/${ticketId}/assign`, { technician_id: techId });
                        window.showToast('Ticket asignado');
                        router.navigate(`tickets/${ticketId}`); // reload
                    } catch (e) {
                        window.showToast(e.message, 'error');
                        btnAssign.disabled = false;
                        btnAssign.innerText = 'Asignar';
                    }
                });
            }
        }

        const btnStatus = document.getElementById('btn-status');
        if (btnStatus) {
            btnStatus.addEventListener('click', async () => {
                const status = document.getElementById('status-select').value;
                try {
                    btnStatus.disabled = true;
                    btnStatus.innerText = '...';
                    await api.patch(`/tickets/${ticketId}/status`, { status });
                    window.showToast('Estado actualizado');
                    router.navigate(`tickets/${ticketId}`); // reload
                } catch (e) {
                    window.showToast(e.message || 'Transición de estado no válida', 'error');
                    btnStatus.disabled = false;
                    btnStatus.innerText = 'Cambiar';
                }
            });
        }

        const btnAddComment = document.getElementById('btn-add-comment');
        if (btnAddComment) {
            btnAddComment.addEventListener('click', async () => {
                const text = document.getElementById('comment-text').value;
                if (!text.trim()) return;
                
                const isInternalCheckbox = document.getElementById('comment-internal');
                const isInternal = isInternalCheckbox ? isInternalCheckbox.checked : false;

                try {
                    btnAddComment.disabled = true;
                    btnAddComment.innerText = 'Enviando...';
                    await api.post(`/tickets/${ticketId}/comments`, {
                        comment: text,
                        is_internal: isInternal
                    });
                    window.showToast('Comentario agregado');
                    router.navigate(`tickets/${ticketId}`); // reload
                } catch (e) {
                    window.showToast(e.message, 'error');
                    btnAddComment.disabled = false;
                    btnAddComment.innerText = 'Enviar Comentario';
                }
            });
        }

    } catch (error) {
        document.getElementById('ticket-detail-content').innerHTML = `
            <div style="padding: 2rem; color: var(--danger); text-align: center;">Error: ${error.message}</div>
        `;
    }

    return {};
};
