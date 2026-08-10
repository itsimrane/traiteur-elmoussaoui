/* ════════════════════════════════════════════════════════════
   TRAITEUR EL MOUSSAOUI — lang.js
   Système de traduction FR / AR (bouton bascule, sans rechargement)
   ════════════════════════════════════════════════════════════ */

'use strict';

(function () {

  function getCurrentLang() {
    return localStorage.getItem('site_lang') || 'fr';
  }

  /**
   * Enveloppe toute séquence de chiffres (avec espaces, tirets, %, MAD...)
   * dans un <span dir="ltr"> pour empêcher leur inversion visuelle en mode RTL.
   * Exemple : "0626 986 533" reste "0626 986 533" même affiché en arabe.
   */
  function protectNumbers(text) {
    return text.replace(
      /(\+?\d[\d\s\-]{2,}\d|\d+(?:[.,]\d+)?\s?%?)/g,
      '<span dir="ltr" class="ltr-numbers">$1</span>'
    );
  }

  function applyLang(lang) {
    const elements = document.querySelectorAll('[data-fr]');

    elements.forEach(el => {
      let text = el.getAttribute(lang === 'ar' ? 'data-ar' : 'data-fr');
      if (text !== null) {
        if (lang === 'ar') {
          text = protectNumbers(text);
          el.innerHTML = text;
        } else if (el.hasAttribute('data-html')) {
          el.innerHTML = text;
        } else {
          el.textContent = text;
        }
      }
    });

    document.querySelectorAll('[data-fr-placeholder]').forEach(el => {
      const ph = lang === 'ar'
        ? el.getAttribute('data-ar-placeholder')
        : el.getAttribute('data-fr-placeholder');
      if (ph !== null) el.setAttribute('placeholder', ph);
    });

    document.documentElement.setAttribute('lang', lang === 'ar' ? 'ar' : 'fr');
    document.documentElement.setAttribute('dir', lang === 'ar' ? 'rtl' : 'ltr');
    document.body.classList.toggle('rtl-mode', lang === 'ar');

    // ── Protection globale des chiffres en mode arabe ──────────
    // Cible tous les éléments statiques contenant des prix/numéros
    // qui ne passent pas par data-fr/data-ar
    if (lang === 'ar') {
      const numSelectors = [
        '.price-from', '.pkg-price', '.pkg-price-big',
        '.nav-tel span', '.stat-num', '.ltr-numbers',
        '.pkg-price span', '.pkg-price-big strong',
      ];
      numSelectors.forEach(sel => {
        document.querySelectorAll(sel).forEach(el => {
          el.setAttribute('dir', 'ltr');
          el.style.direction = 'ltr';
          el.style.unicodeBidi = 'isolate';
          el.style.display = 'inline-block';
        });
      });

      // Protection générique : tout nœud texte contenant 4+ chiffres consécutifs
      document.querySelectorAll('strong, span, div, td, p').forEach(el => {
        if (el.children.length === 0) {
          const txt = el.textContent.trim();
          if (/\d[\d\s]{2,}\d/.test(txt) && !el.closest('[dir="ltr"]')) {
            el.setAttribute('dir', 'ltr');
            el.style.direction = 'ltr';
            el.style.unicodeBidi = 'isolate';
          }
        }
      });
    } else {
      // Retirer les attributs dir ajoutés dynamiquement (hors éléments HTML statiques)
      document.querySelectorAll('[data-ltr-dynamic]').forEach(el => {
        el.removeAttribute('dir');
        el.style.direction = '';
        el.style.unicodeBidi = '';
      });
    }

    localStorage.setItem('site_lang', lang);

    document.querySelectorAll('.lang-switch').forEach(btn => {
      btn.querySelectorAll('.lang-option').forEach(opt => {
        opt.classList.toggle('active', opt.dataset.lang === lang);
      });
    });
  }

  document.addEventListener('DOMContentLoaded', () => {
    applyLang(getCurrentLang());

    document.querySelectorAll('.lang-switch .lang-option').forEach(opt => {
      opt.addEventListener('click', () => {
        applyLang(opt.dataset.lang);
      });
    });
  });

})();
