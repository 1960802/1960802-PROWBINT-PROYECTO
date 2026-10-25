/**
 * Cliente para consumir la API propia con fetch + promesas.
 * Sin recargar la pagina y con manejo visible de errores.
 */
const ApiClient = (function () {
    const baseUrl = window.API_BASE_URL || '/whiteart/public/api';

    async function request(endpoint, options = {}) {
        const config = {
            credentials: 'include',
            headers: {
                'Accept': 'application/json',
                ...(options.headers || {}),
            },
            ...options,
        };

        // Si es JSON y no es FormData
        if (config.body && typeof config.body === 'object' && !(config.body instanceof FormData)) {
            config.headers['Content-Type'] = 'application/json';
            config.body = JSON.stringify(config.body);
        }

        try {
            const res = await fetch(baseUrl + endpoint, config);
            const data = await res.json().catch(() => ({}));

            if (!res.ok || data.ok === false) {
                const mensaje = data.mensaje || 'Error en la solicitud';
                const err = new Error(mensaje);
                err.codigo = data.codigo;
                err.detalles = data.detalles;
                err.status = res.status;
                throw err;
            }

            return data;
        } catch (e) {
            // Mostrar error visible en UI si existe un contenedor
            const contenedor = document.getElementById('api-error');
            if (contenedor) {
                contenedor.innerText = e.mensaje || e.message;
                contenedor.style.display = 'block';
                setTimeout(() => { contenedor.style.display = 'none'; }, 4000);
            }
            throw e;
        }
    }

    return {
        registro: (datos) => request('/auth/registro', { method: 'POST', body: datos }),
        login: (datos) => request('/auth/login', { method: 'POST', body: datos }),
        logout: () => request('/auth/logout', { method: 'POST' }),
        yo: () => request('/auth/yo'),

        perfil: () => request('/perfil'),
        actualizarPerfil: (datos) => {
            if (datos instanceof FormData) {
                return request('/perfil/actualizar', { method: 'POST', body: datos });
            }
            return request('/perfil/actualizar', { method: 'POST', body: datos });
        },

        feed: (params = {}) => {
            const qs = new URLSearchParams(params).toString();
            return request('/publicaciones/feed' + (qs ? '?' + qs : ''));
        },
        misPublicaciones: () => request('/publicaciones/mis'),
        crearPublicacion: (formData) => request('/publicaciones/crear', { method: 'POST', body: formData }),
        buscarPublicaciones: (params = {}) => {
            const qs = new URLSearchParams(params).toString();
            return request('/publicaciones/buscar' + (qs ? '?' + qs : ''));
        },

        categorias: () => request('/categorias'),

        paypalCrearOrden: (datos) => request('/paypal/crear-orden', { method: 'POST', body: datos }),
        paypalCapturar: (datos) => request('/paypal/capturar', { method: 'POST', body: datos }),

        adminUsuarios: () => request('/admin/usuarios'),
        adminBloquear: (datos) => request('/admin/usuarios/bloquear', { method: 'POST', body: datos }),
        adminCrearCategoria: (datos) => request('/admin/categorias', { method: 'POST', body: datos }),
    };
})();
