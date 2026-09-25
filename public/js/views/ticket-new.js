const TicketNewView = async (app) => {
    app.innerHTML = renderLayout(`
        <div class="top-header">
            <h1 class="page-title">Nuevo Ticket</h1>
            <button class="btn btn-outline" onclick="window.location.hash='#tickets'">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 8px"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
                Volver
            </button>
        </div>
        
        <div class="glass-panel" style="max-width: 800px; margin: 0 auto;">
            <form id="new-ticket-form">
                <div class="input-group">
                    <label for="title">Título del problema</label>
                    <input type="text" id="title" class="form-control" placeholder="Ej. El monitor no enciende" required>
                    <div id="title-error" class="error-text"></div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                    <div class="input-group">
                        <label for="category_id">Categoría</label>
                        <select id="category_id" class="form-control" required>
                            <option value="">Cargando...</option>
                        </select>
                        <div id="category_id-error" class="error-text"></div>
                    </div>

                    <div class="input-group">
                        <label for="priority_id">Prioridad</label>
                        <select id="priority_id" class="form-control" required>
                            <option value="">Cargando...</option>
                        </select>
                        <div id="priority_id-error" class="error-text"></div>
                    </div>
                </div>

                <div class="input-group">
                    <label for="description">Descripción detallada</label>
                    <textarea id="description" class="form-control" rows="5" placeholder="Describe el problema con la mayor cantidad de detalles posible..." required></textarea>
                    <div id="description-error" class="error-text"></div>
                </div>

                <div style="display: flex; justify-content: flex-end; margin-top: 2rem;">
                    <button type="submit" class="btn btn-primary" id="submit-btn">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 8px"><line x1="22" y1="2" x2="11" y2="13"></line><polygon points="22 2 15 22 11 13 2 9 22 2"></polygon></svg>
                        Crear Ticket
                    </button>
                </div>
            </form>
        </div>
    `);

    // Fetch catalogs
    try {
        const [categories, priorities] = await Promise.all([
            api.get('/catalogs/categories'),
            api.get('/catalogs/priorities')
        ]);

        const catSelect = document.getElementById('category_id');
        catSelect.innerHTML = '<option value="">Selecciona una categoría</option>' + 
            categories.data.map(c => `<option value="${c.id}">${c.name}</option>`).join('');

        const priSelect = document.getElementById('priority_id');
        priSelect.innerHTML = '<option value="">Selecciona la prioridad</option>' + 
            priorities.data.map(p => `<option value="${p.id}">${p.name}</option>`).join('');

    } catch (error) {
        window.showToast('Error cargando catálogos', 'error');
    }

    // Handle form submit
    const form = document.getElementById('new-ticket-form');
    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        // Reset errors
        document.querySelectorAll('.error-text').forEach(el => {
            el.style.display = 'none';
            el.textContent = '';
        });

        const btn = document.getElementById('submit-btn');
        const originalBtnHTML = btn.innerHTML;
        btn.innerHTML = 'Creando...';
        btn.disabled = true;

        const payload = {
            title: document.getElementById('title').value,
            category_id: document.getElementById('category_id').value,
            priority_id: document.getElementById('priority_id').value,
            description: document.getElementById('description').value,
        };

        try {
            const result = await api.post('/tickets', payload);
            window.showToast('Ticket creado exitosamente');
            window.location.hash = `#tickets/${result.data.id}`;
        } catch (error) {
            btn.innerHTML = originalBtnHTML;
            btn.disabled = false;
            
            if (error.status === 422 && error.data.errors) {
                for (const [field, messages] of Object.entries(error.data.errors)) {
                    const errorEl = document.getElementById(`${field}-error`);
                    if (errorEl) {
                        errorEl.textContent = messages[0];
                        errorEl.style.display = 'block';
                    }
                }
            } else {
                window.showToast(error.message || 'Error al crear el ticket', 'error');
            }
        }
    });

    return {};
};
