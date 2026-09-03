/**
 * ADMIN INLINE EDITOR — Traiteur EL MOUSSAOUI
 * Chargé automatiquement sur toutes les pages si l'admin est connecté.
 * Permet de cliquer sur n'importe quelle image pour la remplacer instantanément.
 */

(function () {
  'use strict';

  const UPLOAD_URL = window.INLINE_UPLOAD_URL || '/api/inline_upload.php';
  const ZONE       = window.INLINE_ZONE || 'galerie';

  // Auto-activer si ?edit=1 dans l'URL
  const urlParams = new URLSearchParams(window.location.search);
  if (urlParams.get('edit') === '1') {
    localStorage.setItem('admin_edit_mode', 'on');
  }

  let editMode = localStorage.getItem('admin_edit_mode') !== 'off';

  // ── Décalage de la navbar du site sous la barre admin ──────
  // Fonctionne avec n'importe quelle barre (celle créée ici, ou
  // une barre déjà présente sur la page comme sur galerie.php).
  function syncOffsetFor(barEl) {
    const siteHeader = document.getElementById('header');
    function sync() {
      const h = barEl.offsetHeight;
      document.body.style.paddingTop = h + 'px';
      if (siteHeader) siteHeader.style.top = h + 'px';
    }
    sync();
    window.addEventListener('resize', sync);
  }

  // ── Barre admin flottante ──────────────────────────────────
  function createAdminBar() {
    // ── Styles partagés (survol photos, overlay, notifications) ──
    // Toujours injectés, même si cette page a déjà sa propre barre
    // (ex: galerie.php), car scanPage()/toast() en ont besoin partout.
    const style = document.createElement('style');
    style.textContent = `
      #adminBarInner{display:flex;align-items:center;justify-content:space-between;width:100%}
      .admin-toggle{position:relative;width:36px;height:20px;flex-shrink:0}
      .admin-toggle input{opacity:0;width:0;height:0;position:absolute}
      .admin-toggle-slider{position:absolute;inset:0;background:#333;border-radius:20px;cursor:pointer;transition:.2s}
      .admin-toggle-slider::before{content:'';position:absolute;height:14px;width:14px;left:3px;bottom:3px;background:#888;border-radius:50%;transition:.2s}
      .admin-toggle input:checked + .admin-toggle-slider{background:rgba(212,175,55,.3);border:1px solid #D4AF37}
      .admin-toggle input:checked + .admin-toggle-slider::before{transform:translateX(16px);background:#D4AF37}

      /* Zones éditables */
      .editable-zone{position:relative;cursor:pointer!important}
      .editable-zone::after{
        content:'✏️ Cliquer pour modifier';position:absolute;inset:0;
        background:rgba(212,175,55,.12);border:2px dashed rgba(212,175,55,.5);
        border-radius:8px;display:flex;align-items:center;justify-content:center;
        font-family:'Jost',sans-serif;font-size:.8rem;font-weight:600;
        color:#D4AF37;opacity:0;transition:.2s;pointer-events:none;
      }
      .editable-zone:hover::after{opacity:1}

      /* Zone vide */
      .editable-empty{
        background:linear-gradient(135deg,rgba(212,175,55,.05),rgba(212,175,55,.02))!important;
        border:2px dashed rgba(212,175,55,.3)!important;
        border-radius:12px;cursor:pointer;
        display:flex!important;align-items:center;justify-content:center;
        flex-direction:column;gap:8px;min-height:160px;transition:.2s;
      }
      .editable-empty:hover{border-color:#D4AF37!important;background:rgba(212,175,55,.1)!important}
      .editable-empty-icon{font-size:2rem;color:rgba(212,175,55,.4)}
      .editable-empty-text{font-size:.78rem;color:rgba(212,175,55,.6);font-family:'Jost',sans-serif}

      /* Overlay image existante */
      .img-edit-overlay{
        position:absolute;inset:0;background:rgba(0,0,0,.65);
        display:flex;align-items:center;justify-content:center;gap:10px;
        opacity:0;transition:.2s;border-radius:inherit;pointer-events:none;
      }
      .editable-zone:hover .img-edit-overlay{opacity:1;pointer-events:auto}
      .img-edit-btn{
        padding:8px 16px;border-radius:8px;font-size:.78rem;font-weight:700;
        cursor:pointer;border:none;font-family:'Jost',sans-serif;transition:.2s;
      }
      .img-edit-btn.replace{background:#D4AF37;color:#0D0D0D}
      .img-edit-btn.replace:hover{background:#B8860B}
      .img-edit-btn.delete{background:rgba(239,68,68,.2);color:#EF5350;border:1px solid rgba(239,68,68,.4)}
      .img-edit-btn.delete:hover{background:rgba(239,68,68,.4)}

      /* Toast notification */
      .admin-toast{
        position:fixed;bottom:30px;right:30px;z-index:999999;
        padding:12px 20px;border-radius:10px;font-family:'Jost',sans-serif;
        font-size:.85rem;font-weight:600;display:flex;align-items:center;gap:10px;
        transform:translateY(80px);opacity:0;transition:.3s;
        box-shadow:0 8px 32px rgba(0,0,0,.5);
      }
      .admin-toast.show{transform:translateY(0);opacity:1}
      .admin-toast.success{background:#1A2E1A;border:1px solid rgba(102,187,106,.4);color:#66BB6A}
      .admin-toast.error{background:#2E1A1A;border:1px solid rgba(239,68,68,.4);color:#EF5350}
      .admin-toast.loading{background:#1A1A2E;border:1px solid rgba(212,175,55,.4);color:#D4AF37}

      /* ── Barre admin : version compacte sur mobile ──────────
         On cache les libellés décoratifs et on réduit les
         boutons pour que tout tienne sur une seule ligne. */
      @media (max-width: 640px) {
        #adminBar { padding: 0 10px !important; height: auto !important; min-height: 40px; }
        #adminBarInner { flex-wrap: nowrap; gap: 6px; }
        #adminBarInner > div { gap: 6px !important; }
        #adminBarLabels { display: none !important; }
        #adminBarInner span:not(#editModeLabel) { display: none; }
        .admin-toggle { flex-shrink: 0; }
        #adminBarInner a { padding: 5px 8px !important; font-size: 0 !important; }
        #adminBarInner a i { font-size: .85rem !important; }
      }
    `;
    document.head.appendChild(style);

    // Certaines pages (ex: galerie.php en mode édition) ont déjà
    // leur propre barre dédiée — on évite d'en créer une deuxième
    // par-dessus, mais on garde le décalage de la navbar cohérent.
    const existingBar = document.getElementById('adminEditBar');
    if (existingBar) {
      syncOffsetFor(existingBar);
      return;
    }

    const bar = document.createElement('div');
    bar.id = 'adminBar';
    bar.innerHTML = `
      <div id="adminBarInner">
        <div id="adminBarLabels" style="display:flex;align-items:center;gap:10px">
          <span style="font-size:.7rem;letter-spacing:2px;color:#888">ADMIN</span>
          <span style="font-family:'Cormorant Garamond',serif;font-size:1rem;font-weight:700;color:#D4AF37">EL MOUSSAOUI</span>
        </div>
        <div style="display:flex;align-items:center;gap:10px">
          <span id="editModeLabel" style="font-size:.78rem"></span>
          <label class="admin-toggle">
            <input type="checkbox" id="editModeToggle" ${editMode ? 'checked' : ''}>
            <span class="admin-toggle-slider"></span>
          </label>
          <span style="font-size:.78rem;color:#888">Mode Édition</span>
          <a href="${window.ADMIN_DASHBOARD_URL || '/admin/dashboard.php'}"
             style="background:rgba(212,175,55,.15);border:1px solid rgba(212,175,55,.3);color:#D4AF37;padding:5px 14px;border-radius:6px;font-size:.75rem;text-decoration:none;font-weight:600">
            <i class="fas fa-tachometer-alt"></i> Dashboard
          </a>
          <a href="${window.ADMIN_LOGOUT_URL || '/admin/logout.php'}"
             style="background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.2);color:#EF5350;padding:5px 14px;border-radius:6px;font-size:.75rem;text-decoration:none;font-weight:600">
            <i class="fas fa-sign-out-alt"></i> Déconnexion
          </a>
        </div>
      </div>`;

    bar.style.cssText = `
      position:fixed;top:0;left:0;right:0;z-index:99999;
      background:rgba(10,10,15,.95);backdrop-filter:blur(10px);
      border-bottom:1px solid rgba(212,175,55,.2);
      padding:0 20px;min-height:44px;display:flex;align-items:center;
      font-family:'Jost',sans-serif;`;

    document.body.insertBefore(bar, document.body.firstChild);
    syncOffsetFor(bar);

    document.getElementById('editModeToggle').addEventListener('change', function () {
      editMode = this.checked;
      localStorage.setItem('admin_edit_mode', editMode ? 'on' : 'off');
      applyEditMode();
    });

    updateLabel();
  }

  function updateLabel() {
    const lbl = document.getElementById('editModeLabel');
    if (!lbl) return;
    lbl.textContent = editMode ? '🟢 Actif' : '⚪ Inactif';
    lbl.style.color = editMode ? '#66BB6A' : '#555';
  }

  // ── Toast ──────────────────────────────────────────────────
  let toastEl = null;
  let toastTimer = null;
  function toast(msg, type = 'success') {
    if (!toastEl) {
      toastEl = document.createElement('div');
      toastEl.className = 'admin-toast';
      document.body.appendChild(toastEl);
    }
    toastEl.className = 'admin-toast ' + type;
    toastEl.innerHTML = (type === 'loading'
      ? '<i class="fas fa-spinner fa-spin"></i>'
      : type === 'success' ? '<i class="fas fa-check-circle"></i>' : '<i class="fas fa-exclamation-circle"></i>')
      + ' ' + msg;
    clearTimeout(toastTimer);
    setTimeout(() => toastEl.classList.add('show'), 10);
    if (type !== 'loading') {
      toastTimer = setTimeout(() => toastEl.classList.remove('show'), 3500);
    }
  }

  // ── Upload inline ──────────────────────────────────────────
  function uploadInline(file, zone, itemId, titre, onSuccess) {
    toast('Upload en cours...', 'loading');
    const fd = new FormData();
    fd.append('image', file);
    fd.append('zone', zone);
    fd.append('item_id', itemId || 0);
    fd.append('titre', titre || 'Photo ' + zone);
    fd.append('categorie_id', 1);

    fetch(UPLOAD_URL, { method: 'POST', body: fd })
      .then(r => r.json())
      .then(res => {
        if (res.success) {
          toast('✅ ' + res.message, 'success');
          onSuccess && onSuccess(res);
        } else {
          toast('❌ ' + res.message, 'error');
        }
      })
      .catch(err => toast('❌ Erreur réseau : ' + err.message, 'error'));
  }

  // ── Créer input file invisible ─────────────────────────────
  function createFileInput(callback) {
    const inp = document.createElement('input');
    inp.type   = 'file';
    inp.accept = 'image/jpeg,image/png,image/webp';
    inp.style  = 'display:none';
    document.body.appendChild(inp);
    inp.addEventListener('change', () => {
      if (inp.files[0]) callback(inp.files[0]);
      document.body.removeChild(inp);
    });
    inp.click();
  }

  // ── Rendre une image éditable ──────────────────────────────
  function makeImageEditable(img) {
    if (img.dataset.inlineReady) return;
    img.dataset.inlineReady = 'true';

    const wrap = img.parentElement;
    wrap.classList.add('editable-zone');
    wrap.style.position = wrap.style.position || 'relative';

    // Overlay avec boutons
    const overlay = document.createElement('div');
    overlay.className = 'img-edit-overlay';
    overlay.innerHTML = `
      <button class="img-edit-btn replace"><i class="fas fa-camera"></i> Remplacer</button>
      <button class="img-edit-btn delete"><i class="fas fa-trash"></i></button>`;
    wrap.appendChild(overlay);

    overlay.querySelector('.replace').addEventListener('click', e => {
      e.stopPropagation();
      const zone    = img.dataset.zone || ZONE;
      const itemId  = img.dataset.id   || 0;
      const titre   = img.dataset.titre || img.alt || 'Photo';
      createFileInput(file => {
        uploadInline(file, zone, itemId, titre, res => {
          img.src = res.url + '?t=' + Date.now();
          img.dataset.id = res.id;
        });
      });
    });

    overlay.querySelector('.delete').addEventListener('click', e => {
      e.stopPropagation();
      if (!confirm('Supprimer cette image ?')) return;
      const itemId = img.dataset.id || 0;
      if (itemId > 0) {
        fetch(window.INLINE_DELETE_URL || '/Traiteur_Elmoussaoui/api/delete_media.php',
          { method: 'POST', body: new URLSearchParams({ id: itemId }) })
          .then(r => r.json())
          .then(res => {
            if (res.success) {
              toast('Image supprimée', 'success');
              // Remplacer par zone vide
              img.src = '';
              img.dataset.id = 0;
              makeEmptyZoneEditable(wrap, img.dataset.zone || ZONE);
            } else {
              toast('❌ ' + res.message, 'error');
            }
          });
      } else {
        toast('Aucun ID trouvé pour cette image', 'error');
      }
    });
  }

  // ── Rendre une zone vide éditable ─────────────────────────
  function makeEmptyZoneEditable(el, zone) {
    if (el.dataset.emptyReady) return;
    el.dataset.emptyReady = 'true';

    el.classList.add('editable-empty');
    el.innerHTML = `
      <span class="editable-empty-icon"><i class="fas fa-plus-circle"></i></span>
      <span class="editable-empty-text">Cliquer pour ajouter une photo</span>`;

    el.addEventListener('click', () => {
      createFileInput(file => {
        uploadInline(file, zone, 0, 'Photo ' + zone, res => {
          el.classList.remove('editable-empty', 'editable-empty-icon', 'editable-empty-text');
          el.dataset.emptyReady = '';
          // Créer une img et la rendre éditable
          const img = document.createElement('img');
          img.src = res.url;
          img.dataset.id    = res.id;
          img.dataset.zone  = zone;
          img.style.cssText = 'width:100%;height:100%;object-fit:cover;display:block';
          el.innerHTML = '';
          el.appendChild(img);
          makeImageEditable(img);
        });
      });
    });
  }

  // ── Scanner la page pour les images éditables ─────────────
  function scanPage() {
    // 1. Images avec data-zone → éditables inline
    document.querySelectorAll('img[data-zone]').forEach(img => {
      if (img.dataset.inline === 'false') return;
      makeImageEditable(img);
    });

    // 2. Zones vides avec data-zone-empty
    document.querySelectorAll('[data-zone-empty]').forEach(el => {
      if (el.dataset.emptyReady) return;
      // Ne pas écraser une zone qui a déjà une image de fond réelle
      // (ex: .service-img avec un background-image défini côté serveur).
      // Ces zones sont déjà prises en charge par leur gestionnaire dédié
      // (voir plus bas : ".service-img, .service-card-img").
      const bg = el.style.backgroundImage || window.getComputedStyle(el).backgroundImage;
      if (bg && bg !== 'none') return;
      makeEmptyZoneEditable(el, el.dataset.zoneEmpty);
    });

    // 3. Images de la galerie (masonry-item)
    document.querySelectorAll('.masonry-item img').forEach(img => {
      if (!img.dataset.zone) img.dataset.zone = 'galerie';
      makeImageEditable(img);
    });

    // 4. Images accueil (gallery-item avec vraie img)
    document.querySelectorAll('.gallery-item img[data-zone]').forEach(img => {
      makeImageEditable(img);
    });

    // 5. Gallery-items avec background-image (fallback)
    document.querySelectorAll('.gallery-item:not([data-zone-ready])').forEach(el => {
      if (el.querySelector('img')) return; // déjà géré
      el.dataset.zoneReady = '1';
      el.style.position    = 'relative';
      el.style.cursor      = 'pointer';

      const overlay = document.createElement('div');
      overlay.className = 'img-edit-overlay';
      overlay.innerHTML = `<button class="img-edit-btn replace"><i class="fas fa-camera"></i> Remplacer</button>`;
      el.appendChild(overlay);

      overlay.querySelector('.replace').addEventListener('click', e => {
        e.stopPropagation();
        createFileInput(file => {
          uploadInline(file, 'accueil', 0, el.dataset.titre || 'Photo accueil', res => {
            el.style.backgroundImage = `url('${res.url}?t=${Date.now()}')`;
            toast('✅ Image mise à jour', 'success');
          });
        });
      });

      // Hover
      el.addEventListener('mouseenter', () => overlay.style.opacity = '1');
      el.addEventListener('mouseleave', () => overlay.style.opacity = '0');
    });

    // 6. Service cards images
    document.querySelectorAll('.service-img, .service-card-img').forEach(el => {
      if (el.dataset.zoneReady) return;
      el.dataset.zoneReady = '1';
      el.style.position = 'relative';
      el.style.cursor   = 'pointer';

      const titre = el.dataset.titre || 'Photo service';
      const imgFile = el.dataset.img || '';

      // Overlay édition
      const overlay = document.createElement('div');
      overlay.className = 'img-edit-overlay';
      overlay.style.cssText = 'position:absolute;inset:0;background:rgba(0,0,0,.65);display:flex;align-items:center;justify-content:center;opacity:0;transition:.2s;z-index:10;border-radius:inherit';
      overlay.innerHTML = `<button class="img-edit-btn replace" style="background:#D4AF37;color:#0D0D0D;padding:8px 16px;border-radius:8px;border:none;font-weight:700;cursor:pointer;font-size:.8rem"><i class="fas fa-camera"></i> ${el.style.backgroundImage ? 'Remplacer' : 'Ajouter une photo'}</button>`;
      el.appendChild(overlay);

      overlay.querySelector('.replace').addEventListener('click', e => {
        e.stopPropagation();
        createFileInput(file => {
          uploadInline(file, 'services', 0, titre, res => {
            // Appliquer l'image
            el.style.backgroundImage = `url('${res.url}?t=${Date.now()}')`;
            el.style.backgroundSize  = 'cover';
            el.style.backgroundPosition = 'center';
            // Cacher le placeholder
            const ph = el.querySelector('.service-img-placeholder');
            if (ph) ph.style.display = 'none';
            // Mettre à jour le bouton
            overlay.querySelector('.replace').innerHTML = '<i class="fas fa-camera"></i> Remplacer';
          });
        });
      });

      el.addEventListener('mouseenter', () => { if (editMode) overlay.style.opacity = '1'; });
      el.addEventListener('mouseleave', () => overlay.style.opacity = '0');
    });
  }

  // ── Activer / désactiver le mode édition ───────────────────
  function applyEditMode() {
    updateLabel();
    if (editMode) {
      scanPage();
      document.body.classList.add('admin-edit-active');
    } else {
      document.body.classList.remove('admin-edit-active');
      // Masquer les overlays
      document.querySelectorAll('.img-edit-overlay').forEach(o => o.style.opacity = '0');
      document.querySelectorAll('.editable-zone').forEach(z => z.classList.remove('editable-zone'));
    }
  }

  // ── Init ───────────────────────────────────────────────────
  document.addEventListener('DOMContentLoaded', () => {
    createAdminBar();
    applyEditMode();

    // Re-scanner si du contenu est ajouté dynamiquement
    const observer = new MutationObserver(() => {
      if (editMode) scanPage();
    });
    observer.observe(document.body, { childList: true, subtree: true });
  });

})();