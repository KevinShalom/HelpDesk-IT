/**
 * API Client Utility
 * Handles token attachment, common headers, and error parsing.
 */
const api = {
    getToken() {
        return localStorage.getItem('auth_token');
    },
    
    setToken(token) {
        localStorage.setItem('auth_token', token);
    },
    
    removeToken() {
        localStorage.removeItem('auth_token');
        localStorage.removeItem('user_data');
    },
    
    getUser() {
        const user = localStorage.getItem('user_data');
        return user ? JSON.parse(user) : null;
    },
    
    setUser(user) {
        localStorage.setItem('user_data', JSON.stringify(user));
    },

    async request(endpoint, options = {}) {
        const url = `/api${endpoint}`;
        const headers = {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            ...options.headers
        };

        const token = this.getToken();
        if (token) {
            headers['Authorization'] = `Bearer ${token}`;
        }

        const config = {
            ...options,
            headers
        };

        try {
            const response = await fetch(url, config);
            const data = await response.json().catch(() => ({}));

            if (!response.ok) {
                // Handle unauthorized globally
                if (response.status === 401) {
                    this.removeToken();
                    window.location.hash = '#login';
                }
                
                throw {
                    status: response.status,
                    data,
                    message: data.message || 'Error en la solicitud'
                };
            }

            return data;
        } catch (error) {
            console.error('API Error:', error);
            throw error;
        }
    },

    get(endpoint) {
        return this.request(endpoint, { method: 'GET' });
    },

    post(endpoint, body) {
        return this.request(endpoint, {
            method: 'POST',
            body: JSON.stringify(body)
        });
    },

    patch(endpoint, body) {
        return this.request(endpoint, {
            method: 'PATCH',
            body: JSON.stringify(body)
        });
    }
};

// Global Toast utility
window.showToast = (message, type = 'success') => {
    const container = document.getElementById('toast-container');
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    toast.textContent = message;
    
    container.appendChild(toast);
    
    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateX(100%)';
        toast.style.transition = 'all 0.3s ease';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
};
