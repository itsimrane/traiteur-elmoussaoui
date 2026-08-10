<?php
require_once __DIR__ . '/../includes/config.php';
?>
<!DOCTYPE html>
<html lang="fr" dir="ltr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>À Propos — Traiteur EL MOUSSAOUI | Errachidia</title>
  <meta name="description" content="Découvrez l'histoire de Traiteur EL MOUSSAOUI, expert en organisation d'événements à Errachidia depuis plus de 10 ans.">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,600;0,700;1,400&family=Jost:wght@300;400;500;600&family=Amiri:wght@400;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.css">
  <link rel="stylesheet" href="../css/style.css">
  <style>
    .about-story { padding:90px 0; }
    .story-grid { display:grid; grid-template-columns:1fr 1fr; gap:80px; align-items:center; }
    .story-img { aspect-ratio:4/5; border-radius:var(--radius-lg); background:var(--dark-3); background-image:linear-gradient(135deg,#1A1200,#0D0900); position:relative; overflow:hidden; display:flex;align-items:center;justify-content:center;flex-direction:column;gap:16px; border:1px solid var(--border); }
    .story-img i { font-size:5rem; color:rgba(212,175,55,0.2); }
    .story-img .story-year { position:absolute; bottom:28px; left:28px; background:linear-gradient(135deg,var(--gold),var(--gold-dark)); color:var(--dark); padding:12px 20px; border-radius:12px; }
    .story-img .story-year .yr { font-family:var(--ff-display); font-size:2rem; font-weight:700; display:block; }
    .story-img .story-year .yr-label { font-size:.7rem; opacity:.8; }
    .ar-title { display:block; font-family:'Amiri',serif; font-size:1.4rem; color:var(--gold); opacity:.7; margin-bottom:20px; }
    .story-text p { color:var(--text-muted); line-height:1.9; margin-bottom:16px; font-size:.95rem; }
    .story-values { display:grid; grid-template-columns:1fr 1fr; gap:14px; margin-top:32px; }
    .story-val { background:var(--dark-card); border:1px solid var(--border); border-radius:12px; padding:16px; }
    .story-val i { color:var(--gold); margin-bottom:8px; font-size:1.1rem; }
    .story-val h4 { font-size:.88rem; color:var(--white); margin-bottom:4px; }
    .story-val p { font-size:.78rem; color:var(--text-muted); line-height:1.6; margin:0; }

    /* Stats */
    .stats-section { background:linear-gradient(135deg,#0D0900,#050300); padding:70px 0; }
    .stats-row { display:grid; grid-template-columns:repeat(4,1fr); gap:20px; }
    .stat-block { text-align:center; }
    .big-num { font-family:var(--ff-display); font-size:3rem; color:var(--gold); font-weight:700; }
    .stat-unit { font-family:var(--ff-display); font-size:2rem; color:var(--gold); }
    .stat-lbl { font-size:.78rem; color:var(--text-muted); margin-top:6px; }

    /* Équipe */
    .team-section { padding:90px 0; background:linear-gradient(180deg,#0D0900 0%,var(--dark) 100%); }
    .team-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:20px; }
    .team-card { background:var(--dark-card); border:1px solid var(--border); border-radius:var(--radius); overflow:hidden; text-align:center; transition:var(--transition); }
    .team-card:hover { border-color:rgba(212,175,55,.3); transform:translateY(-4px); }
    .team-avatar { height:140px; background:linear-gradient(135deg,#1A1200,var(--dark-3)); display:flex;align-items:center;justify-content:center; font-size:3rem; color:rgba(212,175,55,.25); }
    .team-body { padding:18px; }
    .team-name { font-size:.95rem; color:var(--white); font-weight:700; margin-bottom:4px; }
    .team-role { font-size:.75rem; color:var(--gold); margin-bottom:6px; }
    .team-role-ar { font-size:.72rem; color:var(--text-muted); font-family:'Amiri',serif; }

    /* Timeline */
    .timeline-section { padding:90px 0; }
    .timeline { position:relative; max-width:700px; margin:0 auto; }
    .timeline::before { content:''; position:absolute; left:50%; top:0; bottom:0; width:1px; background:var(--border); transform:translateX(-50%); }
    .tl-item { display:flex; gap:32px; margin-bottom:48px; align-items:flex-start; }
    .tl-item:nth-child(even) { flex-direction:row-reverse; }
    .tl-year-wrap { flex:1; text-align:right; }
    .tl-item:nth-child(even) .tl-year-wrap { text-align:left; }
    .tl-year { display:inline-block; background:var(--gold); color:var(--dark); font-family:var(--ff-display); font-size:1rem; font-weight:700; padding:6px 14px; border-radius:20px; }
    .tl-dot { width:14px; height:14px; border-radius:50%; background:var(--gold); border:3px solid var(--dark); flex-shrink:0; margin-top:8px; position:relative; z-index:1; }
    .tl-content { flex:1; }
    .tl-item:nth-child(even) .tl-content { text-align:right; }
    .tl-content h4 { font-size:.95rem; color:var(--white); margin-bottom:4px; }
    .tl-content p { font-size:.8rem; color:var(--text-muted); line-height:1.6; }

    @media(max-width:900px) {
      .story-grid { grid-template-columns:1fr; gap:40px; }
      .stats-row { grid-template-columns:repeat(2,1fr); }
      .team-grid { grid-template-columns:repeat(2,1fr); }
      .story-values { grid-template-columns:1fr; }
      .timeline::before { left:20px; }
      .tl-item,.tl-item:nth-child(even) { flex-direction:column; padding-left:48px; position:relative; }
      .tl-dot { position:absolute; left:14px; top:8px; }
      .tl-year-wrap,.tl-item:nth-child(even) .tl-year-wrap { text-align:left; }
      .tl-item:nth-child(even) .tl-content { text-align:left; }
    }
    @media(max-width:600px) { .team-grid { grid-template-columns:1fr; } }
  </style>
</head>
<body>
<div id="loader"><div class="loader-inner"><div class="loader-ring"></div><div class="loader-logo"><span class="loader-em">EL</span><span class="loader-moussaoui">MOUSSAOUI</span></div></div></div>

<?php $navActive = 'apropos'; include_once __DIR__ . '/../includes/navbar.php'; ?>

<!-- Hero -->
<div class="page-hero">
  <div class="container">
    <span class="section-tag" data-aos="fade-down"
          data-fr="Notre histoire" data-ar="قصتنا">Notre histoire</span>
    <h1 data-aos="fade-up"
        data-fr="À Propos de Nous" data-ar="من نحن">À Propos de Nous</h1>
    <p data-aos="fade-up" data-aos-delay="200"
       data-fr="Depuis plus de 10 ans, nous mettons notre passion et notre expertise au service de vos moments les plus précieux à Errachidia et dans la région."
       data-ar="منذ أكثر من 10 سنوات، نضع شغفنا وخبرتنا في خدمة أثمن لحظاتكم بالراشيدية والمنطقة.">
      Depuis plus de 10 ans, nous mettons notre passion et notre expertise au service de vos moments les plus précieux à Errachidia et dans la région.
    </p>
  </div>
</div>

<!-- Notre Histoire -->
<section class="about-story">
  <div class="container">
    <div class="story-grid">
      <!-- Image -->
      <div data-aos="fade-right">
        <div class="story-img">
          <i class="fas fa-utensils"></i>
          <span style="font-family:var(--ff-display);font-size:1.5rem;color:var(--gold)"
                data-fr="Traiteur EL MOUSSAOUI" data-ar="أفراح المساوي">Traiteur EL MOUSSAOUI</span>
          <span style="color:var(--text-muted);font-size:0.85rem;font-family:'Amiri',serif">أفراح المساوي</span>
          <div class="story-year">
            <span class="yr">2015</span>
            <span class="yr-label" data-fr="Fondé en" data-ar="تأسس عام">Fondé en</span>
          </div>
        </div>
      </div>

      <!-- Texte -->
      <div data-aos="fade-left">
        <span class="section-tag"
              data-fr="Notre histoire" data-ar="قصتنا">Notre histoire</span>
        <h2 style="margin-top:12px"
            data-fr="L'Art de Créer des Souvenirs Inoubliables"
            data-ar="فن صنع الذكريات التي لا تُنسى">
          L'Art de Créer<br>des Souvenirs Inoubliables
        </h2>
        <span class="ar-title">فن صنع الذكريات التي لا تُنسى</span>

        <p data-fr="Traiteur EL MOUSSAOUI est né d'une passion profonde pour l'art culinaire marocain et l'organisation d'événements. Fondé en 2015 à Errachidia, nous avons bâti notre réputation sur la qualité, la générosité et l'attention portée aux moindres détails."
           data-ar="ولد ترايتور المساوي من شغف عميق بفن الطهي المغربي وتنظيم الفعاليات. تأسس عام 2015 بالراشيدية، بنينا سمعتنا على الجودة والكرم والاهتمام بأدق التفاصيل.">
          Traiteur EL MOUSSAOUI est né d'une passion profonde pour l'art culinaire marocain et l'organisation d'événements. Fondé en 2015 à Errachidia, nous avons bâti notre réputation sur la qualité, la générosité et l'attention portée aux moindres détails.
        </p>
        <p data-fr="Chaque événement est unique pour nous. Qu'il s'agisse d'un mariage grandiose, de fiançailles intimes ou d'une circoncision en famille, nous apportons le même niveau d'excellence et de professionnalisme à chaque prestation."
           data-ar="كل مناسبة فريدة بالنسبة لنا. سواء كان زفافاً فخماً أو خطوبة حميمية أو حفل ختان عائلياً، نقدم نفس مستوى التميز والاحترافية في كل خدمة.">
          Chaque événement est unique pour nous. Qu'il s'agisse d'un mariage grandiose, de fiançailles intimes ou d'une circoncision en famille, nous apportons le même niveau d'excellence et de professionnalisme.
        </p>

        <div class="story-values">
          <div class="story-val">
            <i class="fas fa-heart"></i>
            <h4 data-fr="Passion" data-ar="الشغف">Passion</h4>
            <p data-fr="Chaque plat est préparé avec amour et savoir-faire traditionnel."
               data-ar="كل طبق يُعدّ بحب ومهارة تقليدية.">Chaque plat est préparé avec amour et savoir-faire traditionnel.</p>
          </div>
          <div class="story-val">
            <i class="fas fa-medal"></i>
            <h4 data-fr="Excellence" data-ar="التميز">Excellence</h4>
            <p data-fr="Des standards élevés à chaque étape de votre événement."
               data-ar="معايير عالية في كل مرحلة من مراحل مناسبتك.">Des standards élevés à chaque étape de votre événement.</p>
          </div>
          <div class="story-val">
            <i class="fas fa-handshake"></i>
            <h4 data-fr="Confiance" data-ar="الثقة">Confiance</h4>
            <p data-fr="Plus de 500 familles nous font confiance depuis 10 ans."
               data-ar="أكثر من 500 عائلة تثق بنا منذ 10 سنوات.">Plus de 500 familles nous font confiance depuis 10 ans.</p>
          </div>
          <div class="story-val">
            <i class="fas fa-map-marker-alt"></i>
            <h4 data-fr="Local & Régional" data-ar="محلي وإقليمي">Local & Régional</h4>
            <p data-fr="Présents dans toute la région de Drâa-Tafilalet."
               data-ar="نغطي منطقة درعة تافيلالت بأكملها.">Présents dans toute la région de Drâa-Tafilalet.</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Statistiques -->
<section class="stats-section">
  <div class="container">
    <div class="stats-row">
      <div class="stat-block" data-aos="fade-up" data-aos-delay="0">
        <div><span class="big-num" data-count="500">0</span><span class="stat-unit">+</span></div>
        <div class="stat-lbl" data-fr="Événements réalisés" data-ar="مناسبة منجزة">Événements réalisés</div>
      </div>
      <div class="stat-block" data-aos="fade-up" data-aos-delay="80">
        <div><span class="big-num" data-count="10">0</span><span class="stat-unit">+</span></div>
        <div class="stat-lbl" data-fr="Années d'expérience" data-ar="سنوات من الخبرة">Années d'expérience</div>
      </div>
      <div class="stat-block" data-aos="fade-up" data-aos-delay="160">
        <div><span class="big-num" data-count="98">0</span><span class="stat-unit">%</span></div>
        <div class="stat-lbl" data-fr="Clients satisfaits" data-ar="عملاء راضون">Clients satisfaits</div>
      </div>
      <div class="stat-block" data-aos="fade-up" data-aos-delay="240">
        <div><span class="big-num" data-count="15">0</span><span class="stat-unit">+</span></div>
        <div class="stat-lbl" data-fr="Villes couvertes" data-ar="مدينة مغطاة">Villes couvertes</div>
      </div>
    </div>
  </div>
</section>

<!-- Équipe -->
<section class="team-section">
  <div class="container">
    <div class="section-header" data-aos="fade-up" style="margin-bottom:48px">
      <span class="section-tag light"
            data-fr="Les personnes derrière la magie" data-ar="الأشخاص خلف السحر">Les personnes derrière la magie</span>
      <h2 class="section-title light"
          data-fr="Notre Équipe" data-ar="فريقنا">Notre Équipe</h2>
    </div>
    <div class="team-grid">
      <div class="team-card" data-aos="fade-up" data-aos-delay="0">
        <div class="team-avatar"><i class="fas fa-user-tie"></i></div>
        <div class="team-body">
          <div class="team-name">Mohammed EL MOUSSAOUI</div>
          <div class="team-role" data-fr="Directeur Général & Chef de Cuisine" data-ar="المدير العام ورئيس الطهاة">Directeur Général & Chef de Cuisine</div>
          <div class="team-role-ar">مدير عام وشيف طهاة</div>
        </div>
      </div>
      <div class="team-card" data-aos="fade-up" data-aos-delay="80">
        <div class="team-avatar"><i class="fas fa-paint-brush"></i></div>
        <div class="team-body">
          <div class="team-name">Fatima EL MOUSSAOUI</div>
          <div class="team-role" data-fr="Responsable Décoration" data-ar="مسؤولة الديكور">Responsable Décoration</div>
          <div class="team-role-ar">مسؤولة الزينة والديكور</div>
        </div>
      </div>
      <div class="team-card" data-aos="fade-up" data-aos-delay="160">
        <div class="team-avatar"><i class="fas fa-utensils"></i></div>
        <div class="team-body">
          <div class="team-name">Ahmed BENALI</div>
          <div class="team-role" data-fr="Chef Cuisinier" data-ar="رئيس الطهاة">Chef Cuisinier</div>
          <div class="team-role-ar">رئيس الطهاة</div>
        </div>
      </div>
      <div class="team-card" data-aos="fade-up" data-aos-delay="240">
        <div class="team-avatar"><i class="fas fa-clipboard-list"></i></div>
        <div class="team-body">
          <div class="team-name">Sara MANSOURI</div>
          <div class="team-role" data-fr="Coordinatrice Événements" data-ar="منسقة الفعاليات">Coordinatrice Événements</div>
          <div class="team-role-ar">منسقة المناسبات</div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Timeline -->
<section class="timeline-section">
  <div class="container">
    <div class="section-header" data-aos="fade-up" style="margin-bottom:56px">
      <span class="section-tag light"
            data-fr="Notre parcours" data-ar="مسيرتنا">Notre parcours</span>
      <h2 class="section-title"
          data-fr="Notre Histoire en Dates" data-ar="تاريخنا عبر السنين">Notre Histoire en Dates</h2>
    </div>
    <div class="timeline">
      <div class="tl-item" data-aos="fade-right">
        <div class="tl-year-wrap"><span class="tl-year">2015</span></div>
        <div class="tl-dot"></div>
        <div class="tl-content">
          <h4 data-fr="Fondation" data-ar="التأسيس">Fondation</h4>
          <p data-fr="Création de Traiteur EL MOUSSAOUI à Errachidia avec une première équipe de 3 personnes."
             data-ar="تأسيس ترايتور المساوي بالراشيدية مع فريق أول من 3 أشخاص.">
            Création de Traiteur EL MOUSSAOUI à Errachidia avec une première équipe de 3 personnes.
          </p>
        </div>
      </div>
      <div class="tl-item" data-aos="fade-left">
        <div class="tl-year-wrap"><span class="tl-year">2017</span></div>
        <div class="tl-dot"></div>
        <div class="tl-content">
          <h4 data-fr="Expansion régionale" data-ar="التوسع الإقليمي">Expansion régionale</h4>
          <p data-fr="Extension de nos services à Erfoud, Rissani et Goulmima."
             data-ar="توسيع خدماتنا إلى أرفود والريصاني وكلميمة.">
            Extension de nos services à Erfoud, Rissani et Goulmima.
          </p>
        </div>
      </div>
      <div class="tl-item" data-aos="fade-right">
        <div class="tl-year-wrap"><span class="tl-year">2019</span></div>
        <div class="tl-dot"></div>
        <div class="tl-content">
          <h4 data-fr="100ème événement" data-ar="المناسبة الـ100">100ème événement</h4>
          <p data-fr="Nous célébrons notre 100ème mariage organisé avec succès."
             data-ar="نحتفل بتنظيم زفافنا الـ100 بنجاح.">
            Nous célébrons notre 100ème mariage organisé avec succès.
          </p>
        </div>
      </div>
      <div class="tl-item" data-aos="fade-left">
        <div class="tl-year-wrap"><span class="tl-year">2022</span></div>
        <div class="tl-dot"></div>
        <div class="tl-content">
          <h4 data-fr="Nouveau siège" data-ar="مقر جديد">Nouveau siège</h4>
          <p data-fr="Ouverture de notre nouveau showroom et cuisine centrale à Errachidia."
             data-ar="افتتاح معرضنا الجديد والمطبخ المركزي بالراشيدية.">
            Ouverture de notre nouveau showroom et cuisine centrale à Errachidia.
          </p>
        </div>
      </div>
      <div class="tl-item" data-aos="fade-right">
        <div class="tl-year-wrap"><span class="tl-year">2025</span></div>
        <div class="tl-dot"></div>
        <div class="tl-content">
          <h4 data-fr="500+ événements" data-ar="+500 مناسبة">500+ événements</h4>
          <p data-fr="Plus de 500 événements réalisés et une équipe de 20 professionnels dédiés à votre bonheur."
             data-ar="أكثر من 500 مناسبة منجزة وفريق من 20 محترفاً مكرسين لسعادتكم.">
            Plus de 500 événements réalisés et une équipe de 20 professionnels dédiés à votre bonheur.
          </p>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- CTA -->
<section class="section-cta">
  <div class="cta-bg"></div>
  <div class="container">
    <div class="cta-content" data-aos="zoom-in">
      <h2 data-fr="Rejoignez notre famille<br>de clients satisfaits"
          data-ar="انضم إلى عائلتنا<br>من العملاء الراضين" data-html>
        Rejoignez notre famille<br>de clients satisfaits
      </h2>
      <div class="cta-actions">
        <a href="reservation.php" class="btn-primary large">
          <i class="fas fa-file-invoice"></i>
          <span data-fr="Demander un devis gratuit" data-ar="طلب عرض مجاني">Demander un devis gratuit</span>
        </a>
        <a href="https://wa.me/212626986533" target="_blank" class="btn-whatsapp large">
          <i class="fab fa-whatsapp"></i> <span dir="ltr">0626 986 533</span>
        </a>
      </div>
    </div>
  </div>
</section>

<footer id="footer">
  <div class="footer-bottom"><div class="container">
    <p>© <?= date('Y') ?> Traiteur EL MOUSSAOUI — Errachidia, Maroc</p>
    <a href="../index.php" style="color:var(--text-muted);text-decoration:none;font-size:.8rem"
       data-fr="Retour à l'accueil" data-ar="العودة للرئيسية">Retour à l'accueil</a>
  </div></div>
</footer>

<a href="https://wa.me/212626986533" class="whatsapp-float" target="_blank"><i class="fab fa-whatsapp"></i></a>
<button class="scroll-top" id="scrollTop"><i class="fas fa-chevron-up"></i></button>

<script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js"></script>
<script src="../js/main.js"></script>
<script src="../js/lang.js"></script>
<script>
// Compteurs animés
const obs = new IntersectionObserver((entries) => {
  entries.forEach(e => {
    if (e.isIntersecting) {
      e.target.querySelectorAll('[data-count]').forEach(el => {
        const t = parseInt(el.dataset.count);
        let c = 0;
        const s = t / 80;
        const timer = setInterval(() => {
          c = Math.min(c + s, t);
          el.textContent = Math.floor(c);
          if (c >= t) clearInterval(timer);
        }, 16);
      });
      obs.unobserve(e.target);
    }
  });
}, { threshold: 0.5 });
document.querySelector('.stats-section') && obs.observe(document.querySelector('.stats-section'));
</script>
<?php include_once __DIR__ . '/../includes/admin-bar.php'; ?>
</body>
</html>
