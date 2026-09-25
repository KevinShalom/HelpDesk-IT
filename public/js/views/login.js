const LoginView = async (app) => {
    app.innerHTML = `
        <div class="login-wrapper">
            <div class="login-card glass-panel animate-fade-in">
                <div class="login-header">
                    <div style="display:flex; justify-content:center; margin-bottom:1rem; color:var(--accent-primary);">
                        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
                            <polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline>
                            <line x1="12" y1="22.08" x2="12" y2="12"></line>
                        </svg>
                    </div>
                    <h1>IT HelpDesk</h1>
                    <p>Accede al portal de soporte empresarial</p>
                </div>
                
                <form id="login-form">
                    <div class="input-group">
                        <label for="email">Correo Electrónico</label>
                        <input type="email" id="email" class="form-control" placeholder="usuario@empresa.com" required autocomplete="username">
                        <div id="email-error" class="error-text"></div>
                    </div>
                    
                    <div class="input-group">
                        <label for="password">Contraseña</label>
                        <input type="password" id="password" class="form-control" placeholder="••••••••" required autocomplete="current-password">
                        <div id="password-error" class="error-text"></div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 1rem;">
                        <span id="btn-text">Iniciar Sesión</span>
                        <svg id="btn-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
                    </button>
                </form>
            </div>
        </div>
    `;

    const form = document.getElementById('login-form');
    
    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        // Reset errors
        document.querySelectorAll('.error-text').forEach(el => {
            el.style.display = 'none';
            el.textContent = '';
        });

        const email = document.getElementById('email').value;
        const password = document.getElementById('password').value;
        const btnText = document.getElementById('btn-text');
        
        btnText.textContent = 'Autenticando...';
        
        try {
            const data = await api.post('/login', { email, password });
            
            // Save token and user data
            api.setToken(data.token);
            api.setUser(data.user);
            
            window.showToast('Inicio de sesión exitoso');
            window.location.hash = '#dashboard';
            
        } catch (error) {
            btnText.textContent = 'Iniciar Sesión';
            
            if (error.status === 422 && error.data.errors) {
                // Validation errors
                for (const [field, messages] of Object.entries(error.data.errors)) {
                    const errorEl = document.getElementById(`${field}-error`);
                    if (errorEl) {
                        errorEl.textContent = messages[0];
                        errorEl.style.display = 'block';
                    }
                }
            } else {
                window.showToast(error.message || 'Error al iniciar sesión', 'error');
            }
        }
    });

    return {
        // No cleanup needed
    };
};
