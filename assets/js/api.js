(function () {
  const API = {
    request: async function request(url, options = {}) {
      const config = {
        headers: {
          Accept: 'application/json',
          ...(options.headers || {}),
        },
        ...options,
      };

      if (!(config.body instanceof FormData) && !(config.headers && config.headers['Content-Type'])) {
        if (config.body && typeof config.body === 'string') {
          config.headers['Content-Type'] = 'application/json';
        }
      }

      const response = await fetch(url, config);
      let payload = null;
      try {
        payload = await response.json();
      } catch (error) {
        payload = null;
      }

      if (!response.ok) {
        throw new Error(payload && payload.message ? payload.message : 'Erro na solicitação');
      }

      return payload;
    },

    getSession: function getSession() {
      return this.request('/backend/api/session.php');
    },

    login: function login(email, password) {
      return this.request('/backend/auth/login.php', {
        method: 'POST',
        body: JSON.stringify({ email, password }),
      });
    },

    registerUser: function registerUser(payload) {
      return this.request('/backend/api/cadastrar_usuario.php', {
        method: 'POST',
        body: JSON.stringify(payload),
      });
    },

    logout: function logout() {
      return this.request('/backend/auth/logout.php', {
        method: 'POST',
      });
    },

    getPublicacoes: function getPublicacoes(filter = '') {
      const qs = filter ? `?${filter}` : '';
      return this.request(`/backend/api/get_publicacoes.php${qs}`);
    },

    toggleLike: function toggleLike(publicacaoId) {
      return this.request('/backend/api/curtir.php', {
        method: 'POST',
        body: JSON.stringify({ publicacao_id: publicacaoId }),
      });
    },

    createPhoto: function createPhoto(formData) {
      return this.request('/backend/api/cadastrar_foto.php', {
        method: 'POST',
        body: formData,
      });
    },

    deletePhoto: function deletePhoto(id) {
      return this.request('/backend/api/deletar_foto.php', {
        method: 'POST',
        body: JSON.stringify({ id }),
      });
    },
  };

  window.SAEPVisionAPI = API;
})();
