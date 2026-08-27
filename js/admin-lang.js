/* ════════════════════════════════════════
   admin-lang.js — Traduction FR/AR Admin
════════════════════════════════════════ */
'use strict';
(function () {
  const KEY = 'admin_lang';

  function getLang() {
    // Traduction désactivée : l'admin reste toujours en français,
    // même si un navigateur avait "ar" enregistré depuis un ancien test.
    return 'fr';
  }

  function applyLang(lang) {
    const isAr = lang === 'ar';

    // Direction globale
    document.documentElement.setAttribute('lang', isAr ? 'ar' : 'fr');
    document.documentElement.setAttribute('dir', isAr ? 'rtl' : 'ltr');
    document.body.classList.toggle('rtl-mode', isAr);

    // Traduire tous les éléments data-fr / data-ar
    document.querySelectorAll('[data-fr]').forEach(el => {
      const val = el.getAttribute(isAr ? 'data-ar' : 'data-fr');
      if (val !== null) {
        el.hasAttribute('data-html') ? (el.innerHTML = val) : (el.textContent = val);
      }
    });

    // Placeholders
    document.querySelectorAll('[data-fr-placeholder]').forEach(el => {
      const ph = el.getAttribute(isAr ? 'data-ar-placeholder' : 'data-fr-placeholder');
      if (ph) el.setAttribute('placeholder', ph);
    });

    // Sauvegarder
    localStorage.setItem(KEY, lang);

    // Boutons actifs
    document.querySelectorAll('.lang-switch .lang-option').forEach(btn => {
      btn.classList.toggle('active', btn.dataset.lang === lang);
    });

    // Garder les chiffres/badges en LTR
    document.querySelectorAll('.stat-card-value, .sidebar-badge, [dir="ltr"]').forEach(el => {
      el.setAttribute('dir', 'ltr');
    });
  }

  document.addEventListener('DOMContentLoaded', () => {
    applyLang(getLang());
  });
})();