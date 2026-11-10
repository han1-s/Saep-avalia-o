(function () {
  const state = {
    publicacoes: [],
    filtro: '',
    somenteMeu: false,
  };

  function escapeHtml(value) {
    return String(value ?? '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  function getHeartSvg(liked) {
    return `
      <svg viewBox="0 0 25 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
        <path fill-rule="evenodd" clip-rule="evenodd" d="M12.4997 2.05321C19.434 -5.07492 36.7715 7.39852 12.4997 23.4376C-11.7722 7.40008 5.56529 -5.07492 12.4997 2.05321Z" fill="${liked ? '#FF0000' : '#000000'}"/>
      </svg>
    `;
  }

  function renderGallery() {
    const gallery = document.getElementById('gallery');
    if (!gallery) {
      return;
    }

    if (!state.publicacoes.length) {
      gallery.innerHTML = '<div class="empty-state">Nenhuma publicação encontrada.</div>';
      return;
    }

    const user = window.SAEPVisionAuth.getUser();

    gallery.innerHTML = state.publicacoes.map((photo) => {
      const heart = getHeartSvg(Boolean(photo.liked_by_current_user));
      const tooltip = `Foto tirada por: @${escapeHtml(photo.fotografo_usuario || 'fotografo')}`;
      const deleteButton = user && photo.is_owner
        ? `<button type="button" class="delete-btn" data-delete-id="${photo.id}" aria-label="Excluir publicação"><img src="assets/icons/lixeira.svg" alt="Excluir" /></button>`
        : '';

      return `
        <article class="photo-card" data-id="${photo.id}">
          ${deleteButton}
          <div class="photo-image-wrap">
            <div class="tooltip">${tooltip}</div>
            <img src="${photo.imagem_url}" alt="${escapeHtml(photo.titulo)}" />
          </div>
          <div class="card-body">
            <div class="card-header">
              <h3 class="photo-title">${escapeHtml(photo.titulo)}</h3>
              <button type="button" class="like-button ${photo.liked_by_current_user ? 'active' : ''}" data-like-id="${photo.id}">
                ${heart}
                <span>${photo.total_curtidas}</span>
              </button>
            </div>
            <div class="photo-meta">
              <div><strong>Foto tirada por:</strong> @${escapeHtml(photo.fotografo_usuario || 'fotografo')}</div>
              <div><strong>Local:</strong> ${escapeHtml(photo.local_origem || 'Não informado')}</div>
            </div>
          </div>
        </article>
      `;
    }).join('');

    bindLikeButtons();
    bindDeleteButtons();
  }

  function bindLikeButtons() {
    document.querySelectorAll('[data-like-id]').forEach((button) => {
      button.addEventListener('click', async () => {
        if (!window.SAEPVisionAuth.requireAuth()) {
          return;
        }

        const publicacaoId = Number(button.dataset.likeId);
        try {
          const response = await window.SAEPVisionAPI.toggleLike(publicacaoId);
          const index = state.publicacoes.findIndex((item) => item.id === publicacaoId);
          if (index >= 0) {
            state.publicacoes[index].liked_by_current_user = response.liked;
            state.publicacoes[index].total_curtidas = Number(response.total || 0);
          }
          renderGallery();
        } catch (error) {
          window.alert(error.message || 'Erro ao curtir');
        }
      });
    });
  }

  function bindDeleteButtons() {
    document.querySelectorAll('[data-delete-id]').forEach((button) => {
      button.addEventListener('click', async () => {
        const id = Number(button.dataset.deleteId);
        if (!confirm('Deseja realmente excluir esta publicação?')) {
          return;
        }

        try {
          await window.SAEPVisionAPI.deletePhoto(id);
          state.publicacoes = state.publicacoes.filter((item) => item.id !== id);
          renderGallery();
          updateStats();
        } catch (error) {
          window.alert(error.message || 'Erro ao excluir a publicação');
        }
      });
    });
  }

  function updateStats() {
    const user = window.SAEPVisionAuth.getUser();
    const totalLikes = state.publicacoes.reduce((sum, item) => sum + Number(item.total_curtidas || 0), 0);
    const totalPublicacoes = user && user.tipo === 'fotografo'
      ? state.publicacoes.filter((item) => item.is_owner).length
      : 0;

    const likesElement = document.getElementById('totalLikes');
    const publicacoesElement = document.getElementById('totalPublicacoes');
    if (likesElement) likesElement.textContent = totalLikes;
    if (publicacoesElement) publicacoesElement.textContent = totalPublicacoes;
  }

  async function loadPublicacoes() {
    const params = [];
    if (state.filtro) {
      params.push(`fotografo=${encodeURIComponent(state.filtro)}`);
    }
    if (state.somenteMeu) {
      params.push('somente_meu=1');
    }

    try {
      const response = await window.SAEPVisionAPI.getPublicacoes(params.join('&'));
      state.publicacoes = response.publicacoes || [];
      renderGallery();
      updateStats();

      const message = document.getElementById('searchMessage');
      if (message) {
        if (state.filtro && !state.publicacoes.length) {
          message.textContent = 'Fotógrafo não encontrado';
        } else {
          message.textContent = '';
        }
      }
    } catch (error) {
      console.error(error);
    }
  }

  function bindSearch() {
    const input = document.getElementById('searchInput');
    const button = document.getElementById('searchBtn');

    const runSearch = () => {
      state.filtro = (input.value || '').trim().replace(/^@/, '');
      state.somenteMeu = false;
      loadPublicacoes();
    };

    button?.addEventListener('click', runSearch);
    input?.addEventListener('keydown', (event) => {
      if (event.key === 'Enter') {
        event.preventDefault();
        runSearch();
      }
    });
  }

  function bindProfileActions() {
    const profileBtn = document.getElementById('profileBtn');
    const sidebar = document.getElementById('profileSidebar');
    const closeSidebarBtn = document.getElementById('closeSidebarBtn');
    const myPostsBtn = document.getElementById('myPostsBtn');
    const fileInput = document.getElementById('imagemUpload');
    const photoForm = document.getElementById('photoForm');

    profileBtn?.addEventListener('click', () => {
      if (!window.SAEPVisionAuth.requireAuth()) {
        return;
      }
      if (window.SAEPVisionAuth.getUser()?.tipo !== 'fotografo') {
        return;
      }
      sidebar?.classList.remove('closed');
    });

    closeSidebarBtn?.addEventListener('click', () => {
      sidebar?.classList.add('closed');
    });

    myPostsBtn?.addEventListener('click', () => {
      const user = window.SAEPVisionAuth.getUser();
      if (!user || user.tipo !== 'fotografo') {
        window.SAEPVisionAuth.showLoginModal();
        return;
      }
      state.somenteMeu = true;
      state.filtro = '';
      document.getElementById('searchInput').value = '';
      loadPublicacoes();
    });

    fileInput?.addEventListener('change', () => {
      const file = fileInput.files && fileInput.files[0];
      const display = document.getElementById('fileNameDisplay');
      display.textContent = file ? file.name : 'Nenhum arquivo escolhido';
    });

    photoForm?.addEventListener('submit', async (event) => {
      event.preventDefault();

      if (!window.SAEPVisionAuth.requireAuth()) {
        return;
      }

      const formData = new FormData(photoForm);
      const titulo = (formData.get('titulo') || '').toString().trim();
      const local = (formData.get('local_origem') || '').toString().trim();
      const file = formData.get('imagem');

      const errors = document.querySelectorAll('.field-error');
      errors.forEach((error) => {
        error.textContent = '';
      });

      if (!titulo) {
        document.querySelector('[data-error-for="titulo"]').textContent = 'Título da imagem obrigatório';
        return;
      }
      if (!local) {
        document.querySelector('[data-error-for="local_origem"]').textContent = 'Fonte de origem obrigatória';
        return;
      }
      if (!file || !file.name) {
        document.querySelector('[data-error-for="imagem"]').textContent = 'Nenhum arquivo escolhido';
        return;
      }

      const allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
      const ext = (file.name.split('.').pop() || '').toLowerCase();
      if (!allowed.includes(ext)) {
        document.querySelector('[data-error-for="imagem"]').textContent = 'Formato de imagem não permitido';
        return;
      }

      try {
        await window.SAEPVisionAPI.createPhoto(formData);
        photoForm.reset();
        document.getElementById('fileNameDisplay').textContent = 'Nenhum arquivo escolhido';
        state.somenteMeu = true;
        await loadPublicacoes();
      } catch (error) {
        window.alert(error.message || 'Erro ao cadastrar a foto');
      }
    });
  }

  function refreshPublicacoes() {
    state.somenteMeu = false;
    state.filtro = '';
    const input = document.getElementById('searchInput');
    if (input) input.value = '';
    loadPublicacoes();
  }

  document.addEventListener('DOMContentLoaded', () => {
    bindSearch();
    bindProfileActions();
    refreshPublicacoes();
  });

  window.SAEPVisionApp = {
    refreshPublicacoes,
    loadPublicacoes,
  };
})();
