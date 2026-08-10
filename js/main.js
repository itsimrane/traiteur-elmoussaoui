/* ════════════════════════════════════════════════════════════
   TRAITEUR EL MOUSSAOUI — main.js
   ════════════════════════════════════════════════════════════ */

'use strict';

/* ── Loader ──────────────────────────────────────────────────── */
window.addEventListener('load', () => {
  const loader = document.getElementById('loader');
  if (loader) {
    setTimeout(() => loader.classList.add('hidden'), 800);
  }
  if (typeof AOS !== 'undefined') {
    AOS.init({ duration: 700, once: true, offset: 60, easing: 'ease-out-cubic' });
  }
});

/* ── Header scroll ───────────────────────────────────────────── */
const header = document.getElementById('header');
if (header) {
  window.addEventListener('scroll', () => {
    header.classList.toggle('scrolled', window.scrollY > 60);
  }, { passive: true });
}

/* ── Mobile nav ──────────────────────────────────────────────── */
const navToggle = document.getElementById('navToggle');
const navLinks  = document.getElementById('navLinks');
if (navToggle && navLinks) {
  navToggle.addEventListener('click', () => {
    navLinks.classList.toggle('open');
    const open = navLinks.classList.contains('open');
    navToggle.setAttribute('aria-expanded', open);
    document.body.style.overflow = open ? 'hidden' : '';
  });
  // Close on link click
  navLinks.querySelectorAll('a').forEach(link => {
    link.addEventListener('click', () => {
      navLinks.classList.remove('open');
      document.body.style.overflow = '';
    });
  });
  // Close on outside click
  document.addEventListener('click', (e) => {
    if (!navToggle.contains(e.target) && !navLinks.contains(e.target)) {
      navLinks.classList.remove('open');
      document.body.style.overflow = '';
    }
  });
}

/* ── Scroll-to-top button ────────────────────────────────────── */
const scrollTopBtn = document.getElementById('scrollTop');
if (scrollTopBtn) {
  window.addEventListener('scroll', () => {
    scrollTopBtn.classList.toggle('visible', window.scrollY > 400);
  }, { passive: true });
  scrollTopBtn.addEventListener('click', () => window.scrollTo({ top: 0, behavior: 'smooth' }));
}

/* ── Animated counters ───────────────────────────────────────── */
function animateCounter(el) {
  const target = parseInt(el.dataset.count, 10);
  const duration = 2000;
  const step = target / (duration / 16);
  let current = 0;
  const timer = setInterval(() => {
    current = Math.min(current + step, target);
    el.textContent = Math.floor(current);
    if (current >= target) clearInterval(timer);
  }, 16);
}
const counterObserver = new IntersectionObserver((entries) => {
  entries.forEach(entry => {
    if (entry.isIntersecting) {
      entry.target.querySelectorAll('[data-count]').forEach(animateCounter);
      counterObserver.unobserve(entry.target);
    }
  });
}, { threshold: 0.5 });
document.querySelectorAll('.hero-stats').forEach(el => counterObserver.observe(el));

/* ── Gold particles ──────────────────────────────────────────── */
const particlesContainer = document.getElementById('particles');
if (particlesContainer) {
  for (let i = 0; i < 30; i++) {
    const p = document.createElement('div');
    p.className = 'particle';
    p.style.cssText = `
      left: ${Math.random() * 100}%;
      --dur: ${6 + Math.random() * 10}s;
      --delay: ${Math.random() * 10}s;
      width: ${1 + Math.random() * 3}px;
      height: ${1 + Math.random() * 3}px;
    `;
    particlesContainer.appendChild(p);
  }
}

/* ── Testimonials slider ─────────────────────────────────────── */
const track   = document.getElementById('testimonialTrack');
const dotsWrap = document.getElementById('testiDots');
const prevBtn  = document.getElementById('testiPrev');
const nextBtn  = document.getElementById('testiNext');
if (track) {
  const cards = Array.from(track.children);
  let current = 0;
  const perView = () => window.innerWidth < 768 ? 1 : window.innerWidth < 1100 ? 2 : 3;

  function createDots() {
    if (!dotsWrap) return;
    dotsWrap.innerHTML = '';
    const total = Math.ceil(cards.length / perView());
    for (let i = 0; i < total; i++) {
      const d = document.createElement('div');
      d.className = 'testi-dot' + (i === 0 ? ' active' : '');
      d.addEventListener('click', () => goTo(i));
      dotsWrap.appendChild(d);
    }
  }

  function goTo(idx) {
    const pv = perView();
    const max = Math.ceil(cards.length / pv) - 1;
    current = Math.max(0, Math.min(idx, max));
    const cardW = cards[0].offsetWidth + 24; // gap = 24
    track.style.transform = `translateX(-${current * cardW * pv}px)`;
    dotsWrap?.querySelectorAll('.testi-dot').forEach((d, i) => d.classList.toggle('active', i === current));
  }

  prevBtn?.addEventListener('click', () => goTo(current - 1));
  nextBtn?.addEventListener('click', () => goTo(current + 1));
  createDots();
  window.addEventListener('resize', () => { createDots(); goTo(current); });
  // Auto-advance
  setInterval(() => goTo((current + 1) % Math.ceil(cards.length / perView())), 5000);
}

/* ── Multi-step reservation form ─────────────────────────────── */
function initStepForm() {
  const steps    = document.querySelectorAll('.step-panel');
  const stepItems = document.querySelectorAll('.step-item');
  const nextBtns = document.querySelectorAll('.btn-next-step');
  const prevBtns = document.querySelectorAll('.btn-prev-step');
  const form     = document.getElementById('reservationForm');
  if (!steps.length || !form) return;

  let current = 0;

  function showStep(n) {
    steps.forEach((s, i) => s.classList.toggle('active', i === n));
    stepItems.forEach((s, i) => {
      s.classList.toggle('active', i === n);
      s.classList.toggle('done', i < n);
    });
    current = n;
    window.scrollTo({ top: form.getBoundingClientRect().top + window.scrollY - 100, behavior: 'smooth' });
  }

  nextBtns.forEach(btn => {
    btn.addEventListener('click', () => {
      if (current < steps.length - 1) {
        buildRecap();
        showStep(current + 1);
      }
    });
  });

  prevBtns.forEach(btn => {
    btn.addEventListener('click', () => {
      if (current > 0) showStep(current - 1);
    });
  });

  // Event type picker
  document.querySelectorAll('.event-option').forEach(opt => {
    opt.addEventListener('click', () => {
      document.querySelectorAll('.event-option').forEach(o => o.classList.remove('selected'));
      opt.classList.add('selected');
      const input = document.getElementById('eventTypeInput');
      if (input) input.value = opt.dataset.type;
    });
  });

  // Recap builder
  function buildRecap() {
    const fields = {
      'recap-event-type': document.querySelector('.event-option.selected')?.querySelector('span')?.textContent,
      'recap-date':       document.getElementById('eventDate')?.value,
      'recap-lieu':       document.getElementById('eventLieu')?.value,
      'recap-invites':    document.getElementById('eventInvites')?.value,
      'recap-nom':        document.getElementById('clientNom')?.value,
      'recap-telephone':  document.getElementById('clientTel')?.value,
    };
    Object.entries(fields).forEach(([id, val]) => {
      const el = document.getElementById(id);
      if (el && val) el.textContent = val;
    });
    // Services
    const svcs = [...document.querySelectorAll('.service-checkbox-item input:checked')].map(el => el.nextElementSibling?.textContent?.trim()).join(', ');
    const recapSvc = document.getElementById('recap-services');
    if (recapSvc) recapSvc.textContent = svcs || '—';
  }

  // Submit
  const submitBtn = document.getElementById('submitForm');
  if (submitBtn) {
    submitBtn.addEventListener('click', async (e) => {
      e.preventDefault();
      submitBtn.disabled = true;
      submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Envoi en cours...';

      // Simulate API call
      await new Promise(r => setTimeout(r, 1500));

      steps.forEach(s => s.classList.remove('active'));
      const successPanel = document.getElementById('successPanel');
      if (successPanel) successPanel.classList.add('active');
      stepItems.forEach(s => { s.classList.remove('active'); s.classList.add('done'); });
    });
  }

  showStep(0);
}
initStepForm();

/* ── Active nav link ─────────────────────────────────────────── */
const currentPage = window.location.pathname.split('/').pop() || 'index.html';
document.querySelectorAll('.nav-link').forEach(link => {
  const href = link.getAttribute('href')?.split('/').pop();
  if (href === currentPage) link.classList.add('active');
});
