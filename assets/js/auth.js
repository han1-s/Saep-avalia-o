(function () {
  const state = {
    user: null,
  };

  function showAuthMode(mode) {
    const loginMode = document.getElementById('authModeLogin');
    const registerMode = document.getElementById('authModeRegister');
    const isLogin = mode === 'login';

    if (loginMode) {
      loginMode.classList.toggle('hidden', !isLogin);
    }

    if (registerMode) {
      registerMode.classList.toggle('hidden', isLogin);
    }

    const modal = document.getElementById('loginModal');
    if (!modal) {
      return;
    }
    modal.classList.remove('hidden');
    modal.setAttribute('aria-hidden', 'false');
  }

  function showLoginModal() {
    showAuthMode('login');
  }

  function showRegisterModal() {
    showAuthMode('register');
  }

  function closeLoginModal() {
    const modal = document.getElementById('loginModal');
    if (!modal) {
      return;
    }
    modal.classList.add('hidden');
    modal.setAttribute('aria-hidden', 'true');

    const loginForm = document.getElementById('loginForm');
    if (loginForm) {
      loginForm.reset();
    }

    const registerForm = document.getElementById('registerForm');
    if (registerForm) {
      registerForm.reset();
    }

    const loginError = document.getElementById('loginErrorMessage');
    if (loginError) loginError.textContent = '';

    const registerError = document.getElementById('registerErrorMessage');
    if (registerError) registerError.textContent = '';

    [...document.querySelectorAll('.field-error')].forEach((error) => {
      error.textContent = '';
    });
  }

  function setUser(user) {
    state.user = user;
    updateAuthUi();
  }

  function getUser() {
    return state.user;
  }

  function updateAuthUi() {
    const user = state.user;
    const loginBtn = document.getElementById('loginBtn');
    const profileBtn = document.getElementById('profileBtn');
    const userName = document.getElementById('userName');
    const avatar = document.getElementById('userAvatar');
    const isLogged = !!user;

    if (loginBtn) {
      loginBtn.textContent = isLogged ? 'Logout' : 'Login';
    }

    if (userName) {
      userName.textContent = isLogged ? `@${user.nome_usuario || user.nome || 'SAEPVision'}` : '@SAEPVision';
    }

    if (avatar) {
      const fallback = '/backend/media.php?path=imagens_usuarios/saepVision.jpeg';
      const photo = user && user.imagem_usuario ? `/backend/media.php?path=${encodeURIComponent(user.imagem_usuario)}` : fallback;
      avatar.src = photo;
    }

    if (profileBtn) {
      profileBtn.disabled = !(isLogged && user.tipo === 'fotografo');
      profileBtn.classList.toggle('disabled', !(isLogged && user.tipo === 'fotografo'));
    }
  }

  function requireAuth() {
    if (!state.user) {
      showLoginModal();
      return false;
    }
    return true;
  }

  async function loginSubmit(event) {
    event.preventDefault();

    const email = document.getElementById('emailInput').value.trim();
    const password = document.getElementById('senhaInput').value;
    const emailError = document.querySelector('[data-error-for="email"]');
    const passwordError = document.querySelector('[data-error-for="password"]');
    const errorBox = document.getElementById('loginErrorMessage');
    emailError.textContent = '';
    passwordError.textContent = '';
    errorBox.textContent = '';

    let valid = true;
    if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
      emailError.textContent = 'E-mail inválido ou vazio';
      valid = false;
    }

    if (!password) {
      passwordError.textContent = 'A senha é obrigatória e não pode estar vazia';
      valid = false;
    }

    if (!valid) {
      return;
    }

    try {
      const response = await window.SAEPVisionAPI.login(email, password);
      setUser(response.user);
      closeLoginModal();
      if (window.SAEPVisionApp && typeof window.SAEPVisionApp.refreshPublicacoes === 'function') {
        window.SAEPVisionApp.refreshPublicacoes();
      }
    } catch (error) {
      errorBox.textContent = error.message || 'Usuário não encontrado ou senha incorreta';
    }
  }

  async function registerSubmit(event) {
    event.preventDefault();

    const nome = document.getElementById('nomeInput').value.trim();
    const nomeUsuario = document.getElementById('nomeUsuarioInput').value.trim();
    const email = document.getElementById('registerEmailInput').value.trim();
    const tipo = document.getElementById('tipoInput').value;
    const password = document.getElementById('registerSenhaInput').value;
    const confirmPassword = document.getElementById('confirmSenhaInput').value;
    const errorBox = document.getElementById('registerErrorMessage');
    const fieldErrors = [...document.querySelectorAll('.field-error')];
    fieldErrors.forEach((error) => {
      error.textContent = '';
    });
    errorBox.textContent = '';

    let valid = true;

    if (!nome) {
      document.querySelector('[data-error-for="nome"]').textContent = 'Informe seu nome completo';
      valid = false;
    }

    if (!nomeUsuario) {
      document.querySelector('[data-error-for="nome_usuario"]').textContent = 'Informe um nome de usuário';
      valid = false;
    } else if (!/^[a-zA-Z0-9_.-]+$/.test(nomeUsuario)) {
      document.querySelector('[data-error-for="nome_usuario"]').textContent = 'Use apenas letras, números e . _ -';
      valid = false;
    }

    if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
      document.querySelector('[data-error-for="email_registro"]').textContent = 'E-mail inválido';
      valid = false;
    }

    if (!password || password.length < 6) {
      document.querySelector('[data-error-for="password_registro"]').textContent = 'A senha deve ter pelo menos 6 caracteres';
      valid = false;
    }

    if (!confirmPassword || confirmPassword !== password) {
      document.querySelector('[data-error-for="confirm_password"]').textContent = 'As senhas não conferem';
      valid = false;
    }

    if (!valid) {
      return;
    }

    try {
      const response = await window.SAEPVisionAPI.registerUser({
        nome,
        nome_usuario: nomeUsuario,
        email,
        tipo,
        password,
        confirm_password: confirmPassword,
      });

      setUser(response.user);
      closeLoginModal();
      if (window.SAEPVisionApp && typeof window.SAEPVisionApp.refreshPublicacoes === 'function') {
        window.SAEPVisionApp.refreshPublicacoes();
      }
    } catch (error) {
      errorBox.textContent = error.message || 'Não foi possível concluir o cadastro';
    }
  }

  async function logoutUser() {
    try {
      await window.SAEPVisionAPI.logout();
    } catch (error) {
      console.warn(error);
    }
    setUser(null);
    if (window.SAEPVisionApp && typeof window.SAEPVisionApp.refreshPublicacoes === 'function') {
      window.SAEPVisionApp.refreshPublicacoes();
    }
  }

  function bindEvents() {
    const loginBtn = document.getElementById('loginBtn');
    if (loginBtn) {
      loginBtn.addEventListener('click', async () => {
        if (state.user) {
          await logoutUser();
          return;
        }
        showLoginModal();
      });
    }

    document.getElementById('closeModalBtn')?.addEventListener('click', closeLoginModal);
    document.getElementById('cancelLoginBtn')?.addEventListener('click', closeLoginModal);
    document.getElementById('cancelRegisterBtn')?.addEventListener('click', closeLoginModal);
    document.querySelector('[data-close-modal="true"]')?.addEventListener('click', closeLoginModal);
    document.getElementById('loginForm')?.addEventListener('submit', loginSubmit);
    document.getElementById('registerForm')?.addEventListener('submit', registerSubmit);
    document.getElementById('showRegisterBtn')?.addEventListener('click', showRegisterModal);
    document.getElementById('showLoginBtn')?.addEventListener('click', showLoginModal);
    document.addEventListener('keydown', (event) => {
      if (event.key === 'Escape') {
        closeLoginModal();
      }
    });
  }

  async function initializeSession() {
    try {
      const response = await window.SAEPVisionAPI.getSession();
      if (response && response.user) {
        setUser(response.user);
      }
    } catch (error) {
      console.warn('Sessão indisponível', error);
    }
  }

  document.addEventListener('DOMContentLoaded', () => {
    bindEvents();
    updateAuthUi();
    initializeSession();
  });

  window.SAEPVisionAuth = {
    setUser,
    getUser,
    showLoginModal,
    closeLoginModal,
    requireAuth,
    logoutUser,
  };
})();
