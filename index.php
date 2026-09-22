<?php
/**
 * Page : index.php
 * Traiteur EL MOUSSAOUI
 */
require_once __DIR__ . '/includes/config.php';

// ── Paramètres de contact réels (modifiables depuis admin/parametres.php) ──
$paramsContact = [
    'contact_telephone' => '0626986533',
    'contact_whatsapp'  => '+212626986533',
    'contact_email'     => 'contact@traiteur-elmoussaoui.ma',
    'contact_adresse'   => 'Errachidia, Région Drâa-Tafilalet, Maroc',
    'horaires_ouverture'=> 'Lun–Sam : 08h–20h',
];
try {
    $rows = $pdo->query("SELECT cle, valeur FROM parametres WHERE groupe='contact'")->fetchAll();
    foreach ($rows as $r) {
        if ($r['valeur'] !== null) $paramsContact[$r['cle']] = $r['valeur'];
    }
} catch (Exception $e) {}
$waNumeroIndex = ltrim(preg_replace('/[^0-9]/', '', $paramsContact['contact_whatsapp']), '+');
$telAfficheIndex = preg_replace('/(\d{2})(?=\d)/', '$1 ', $paramsContact['contact_telephone']);

// ── Statistiques réelles (plus de faux chiffres) ──────────────
$statEvenements = 0;
$statClients    = 0;
$statNote       = 0;
try { $statEvenements = (int)$pdo->query("SELECT COUNT(*) FROM devis_generes")->fetchColumn(); } catch(Exception $e) {}
try { $statClients    = (int)$pdo->query("SELECT COUNT(*) FROM clients")->fetchColumn(); } catch(Exception $e) {}
try {
    $avg = $pdo->query("SELECT AVG(note) FROM temoignages WHERE statut='publie'")->fetchColumn();
    $statNote = $avg ? round($avg, 1) : 0;
} catch(Exception $e) {}
$hasRealStats = ($statEvenements > 0 || $statClients > 0);

// ── 3 témoignages réels et publiés (les plus mis en avant d'abord) ──
$homeTemoignages = [];
try {
    $homeTemoignages = $pdo->query("
        SELECT nom_client, ville, contenu, note, type_evenement
        FROM temoignages WHERE statut='publie'
        ORDER BY en_vedette DESC, ordre ASC, created_at DESC LIMIT 3
    ")->fetchAll();
} catch(Exception $e) {}

// ── Aperçu galerie : vraies photos les plus récentes ──────────
$homeGalerie = [];
try {
    $homeGalerie = $pdo->query("
        SELECT fichier, titre, alt_text
        FROM galerie WHERE type='photo' AND actif=1 AND fichier IS NOT NULL
        ORDER BY en_vedette DESC, id DESC LIMIT 6
    ")->fetchAll();
} catch(Exception $e) {}
?>
<!DOCTYPE html>
<html lang="fr" dir="ltr">
<head>
  <meta charset="UTF-8">
  <link rel="icon" type="image/png" href="assets/img/favicon-32.png">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Traiteur EL MOUSSAOUI — Organisation des Évènements & Fêtes | Errachidia</title>
  <meta name="description" content="Traiteur EL MOUSSAOUI, votre spécialiste en organisation de mariages, fiançailles et événements à Errachidia, Maroc. Contactez-nous : 0626 986 533">
  <meta name="keywords" content="traiteur errachidia, mariage errachidia, organisation événements maroc, traiteur el moussaoui, أفراح المساوي">
  <meta property="og:title" content="Traiteur EL MOUSSAOUI — Errachidia">
  <meta property="og:description" content="Organisation des Évènements et des Fêtes à Errachidia">
  <meta property="og:type" content="website">
  <!-- Google Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;0,700;1,400;1,600&family=Jost:wght@300;400;500;600&family=Amiri:wght@400;700&display=swap" rel="stylesheet">
  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <!-- AOS Animations -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.css">
  <!-- Main CSS -->
  <link rel="stylesheet" href="css/style.css">
</head>
<body>

<!-- ══════════════════════════════════════════════
     LOADER
══════════════════════════════════════════════ -->
<div id="loader">
  <div class="loader-inner">
    <div class="loader-ring"></div>
    <div class="loader-logo">
      <span class="loader-em">EL</span>
      <span class="loader-moussaoui">MOUSSAOUI</span>
    </div>
  </div>
</div>

<!-- ══════════════════════════════════════════════
     HEADER / NAVIGATION
══════════════════════════════════════════════ -->
<header id="header">
  <nav class="navbar">
    <div class="nav-logo">
      <a href="index.php">
        <div class="logo-text">
          <span class="logo-traiteur">TRAITEUR</span>
          <span class="logo-name">EL MOUSSAOUI</span>
          <span class="logo-sub">أفراح المساوي</span>
        </div>
      </a>
    </div>

    <ul class="nav-links" id="navLinks">
      <li><a href="index.php" class="nav-link active" data-fr="Accueil" data-ar="الرئيسية">Accueil</a></li>
      <li><a href="pages/services.php" class="nav-link" data-fr="Services" data-ar="خدماتنا">Services</a></li>
      <li><a href="pages/galerie.php" class="nav-link" data-fr="Nos réalisations" data-ar="أعمالنا">Nos réalisations</a></li>
      <li><a href="pages/apropos.php" class="nav-link" data-fr="À Propos" data-ar="من نحن">À Propos</a></li>
      <li><a href="pages/contact.php" class="nav-link" data-fr="Contact" data-ar="اتصل بنا">Contact</a></li>
    </ul>

    <div class="nav-actions">
      <a href="pages/reservation.php" class="btn-reservation" data-fr="Réserver" data-ar="احجز الآن" data-html>
        Réserver <i class="fas fa-arrow-right"></i>
      </a>
      <button class="theme-toggle" id="themeToggle" title="Changer de thème">
        <i class="fas fa-moon"></i>
        <i class="fas fa-sun"></i>
      </button>
      <div class="lang-switch">
        <span class="lang-option active" data-lang="fr">FR</span>
        <span class="lang-option" data-lang="ar">AR</span>
      </div>
      <button class="nav-toggle" id="navToggle" aria-label="Menu">
        <span></span><span></span><span></span>
      </button>
    </div>
  </nav>
  <script>
    (function () {
      var saved = localStorage.getItem('theme') || 'dark';
      if (saved === 'light') document.documentElement.setAttribute('data-theme', 'light');
    })();
    document.addEventListener('DOMContentLoaded', function () {
      var btn = document.getElementById('themeToggle');
      if (!btn) return;
      btn.addEventListener('click', function () {
        var isLight = document.documentElement.getAttribute('data-theme') === 'light';
        if (isLight) {
          document.documentElement.removeAttribute('data-theme');
          localStorage.setItem('theme', 'dark');
        } else {
          document.documentElement.setAttribute('data-theme', 'light');
          localStorage.setItem('theme', 'light');
        }
      });
    });
  </script>
</header>

<!-- ══════════════════════════════════════════════
     HERO SECTION
══════════════════════════════════════════════ -->
<section id="hero">
  <div class="hero-bg">
    <!-- Bandes diagonales avec photos de tentes -->
    <div class="hero-diag-bg">
      <div class="diag-band band-1" style="background-image:url('assets/img/tentes/HLDE0799.JPG')"></div>
      <div class="diag-band band-2" style="background-image:url('assets/img/tentes/IEDE8790.JPG')"></div>
      <div class="diag-band band-3" style="background-image:url('assets/img/tentes/WDZN0303.JPG')"></div>
      <div class="diag-band band-4" style="background-image:url('assets/img/tentes/XWTX8922.JPG')"></div>
      <div class="diag-band band-5" style="background-image:url('assets/img/tentes/YABZ8454.JPG')"></div>
    </div>
    <div class="hero-overlay"></div>
    <!-- Animated gold particles -->
    <div class="particles" id="particles"></div>
  </div>

  <div class="hero-content">
    <div class="hero-badge" data-aos="fade-down" data-aos-delay="200">
      <span>✦ Errachidia, Maroc ✦</span>
    </div>
    <h1 class="hero-title" data-aos="fade-up" data-aos-delay="400">
      <span class="hero-title-line1">Traiteur</span>
      <span class="hero-title-line2">EL MOUSSAOUI</span>
      <span class="hero-title-ar">أفراح المساوي</span>
    </h1>
    <p class="hero-subtitle" data-aos="fade-up" data-aos-delay="600" data-fr="Organisation des Évènements et des Fêtes" data-ar="تنظيم وتجهيز جميع المناسبات والحفلات">
      Organisation des Évènements et des Fêtes
    </p>
    <div class="hero-cta" data-aos="fade-up" data-aos-delay="800">
      <a href="pages/reservation.php" class="btn-primary" data-fr="Demander un devis" data-ar="طلب عرض سعر" data-html>
        <i class="fas fa-calendar-check"></i> Demander un devis
      </a>
      <a href="pages/galerie.php" class="btn-outline" data-fr="Voir nos réalisations" data-ar="شاهد أعمالنا" data-html>
        <i class="fas fa-images"></i> Voir nos réalisations
      </a>
    </div>
    <?php if ($hasRealStats): ?>
    <div class="hero-stats" data-aos="fade-up" data-aos-delay="1000">
      <?php if ($statEvenements > 0): ?>
      <div class="stat-item">
        <span class="stat-num" data-count="<?= $statEvenements ?>">0</span><span class="stat-plus">+</span>
        <span class="stat-label" data-fr="Événements traités" data-ar="مناسبة">Événements traités</span>
      </div>
      <?php endif; ?>
      <?php if ($statEvenements > 0 && $statClients > 0): ?><div class="stat-divider"></div><?php endif; ?>
      <?php if ($statClients > 0): ?>
      <div class="stat-item">
        <span class="stat-num" data-count="<?= $statClients ?>">0</span><span class="stat-plus">+</span>
        <span class="stat-label" data-fr="Clients accompagnés" data-ar="عملاء">Clients accompagnés</span>
      </div>
      <?php endif; ?>
      <?php if ($statNote > 0): ?>
      <div class="stat-divider"></div>
      <div class="stat-item">
        <span class="stat-num" dir="ltr"><?= number_format($statNote, 1) ?></span><span class="stat-plus">/5</span>
        <span class="stat-label" data-fr="Note moyenne clients" data-ar="متوسط تقييم العملاء">Note moyenne clients</span>
      </div>
      <?php endif; ?>
    </div>
    <?php else: ?>
    <p class="hero-qualitative" data-aos="fade-up" data-aos-delay="1000"
       data-fr="Un service reconnu pour son professionnalisme et son souci du détail."
       data-ar="خدمة معروفة باحترافيتها واهتمامها بالتفاصيل.">
      Un service reconnu pour son professionnalisme et son souci du détail.
    </p>
    <?php endif; ?>
  </div>

  <div class="hero-scroll-hint">
    <div class="scroll-line"></div>
    <span data-fr="Découvrir" data-ar="اكتشف">Découvrir</span>
  </div>
</section>

<!-- ══════════════════════════════════════════════
     SERVICES
══════════════════════════════════════════════ -->
<section id="services" class="section">
  <div class="container">
    <div class="section-header" data-aos="fade-up">
      <span class="section-tag">Ce que nous faisons</span>
      <h2 class="section-title" data-fr="Nos Services d'Excellence" data-ar="خدماتنا المتميزة">Nos Services d'Excellence</h2>
      <p class="section-desc" data-fr="De la décoration à la restauration, nous orchestrons chaque détail pour que votre événement soit inoubliable." data-ar="من الديكور إلى الضيافة، نهتم بكل التفاصيل لنجعل مناسبتكم لا تُنسى.">De la décoration à la restauration, nous orchestrons chaque détail pour que votre événement soit inoubliable.</p>
    </div>

    <div class="services-grid services-grid-4">
      <div class="service-card" data-aos="fade-up" data-aos-delay="100">
        <div class="service-img" data-zone-empty="services" data-img="mariage.jpg" data-titre="Mariage" style="background-image: url('assets/img/mariage.jpg')"></div>
        <div class="service-body">
          <h3 data-fr="Mariages" data-ar="حفلات الزفاف">Mariages</h3>
          <p data-fr="Décoration, buffet et coordination pour le plus beau jour de votre vie." data-ar="ديكور، بوفيه وتنسيق ليوم لا يُنسى.">Décoration, buffet et coordination pour le plus beau jour de votre vie.</p>
          <a href="pages/services.php#mariages" class="service-link" data-fr="En savoir plus" data-ar="معرفة المزيد" data-html>En savoir plus <i class="fas fa-arrow-right"></i></a>
        </div>
      </div>

      <div class="service-card featured" data-aos="fade-up" data-aos-delay="200">
        <div class="service-badge" data-fr="Populaire" data-ar="الأكثر طلباً">Populaire</div>
        <div class="service-img" data-zone-empty="services" data-img="fiancailles.jpg" data-titre="Fiançailles" style="background-image: url('assets/img/fiancailles.jpg')"></div>
        <div class="service-body">
          <h3 data-fr="Fiançailles" data-ar="الخطوبة">Fiançailles</h3>
          <p data-fr="Cérémonie mémorable avec décoration florale et buffet raffiné." data-ar="حفل خطوبة لا يُنسى مع ديكور زهور وبوفيه راقٍ.">Cérémonie mémorable avec décoration florale et buffet raffiné.</p>
          <a href="pages/services.php#fiancailles" class="service-link" data-fr="En savoir plus" data-ar="معرفة المزيد" data-html>En savoir plus <i class="fas fa-arrow-right"></i></a>
        </div>
      </div>

      <div class="service-card" data-aos="fade-up" data-aos-delay="300">
        <div class="service-img" data-zone-empty="services" data-img="anniversaire.jpg" data-titre="Célébrations" style="background-image: url('assets/img/anniversaire.jpg')"></div>
        <div class="service-body">
          <h3 data-fr="Célébrations" data-ar="الاحتفالات">Célébrations</h3>
          <p data-fr="Anniversaires, circoncisions et fêtes familiales à votre image." data-ar="أعياد ميلاد، ختان واحتفالات عائلية على ذوقكم.">Anniversaires, circoncisions et fêtes familiales à votre image.</p>
          <a href="pages/services.php#anniversaires" class="service-link" data-fr="En savoir plus" data-ar="معرفة المزيد" data-html>En savoir plus <i class="fas fa-arrow-right"></i></a>
        </div>
      </div>

      <div class="service-card" data-aos="fade-up" data-aos-delay="400">
        <div class="service-img" data-zone-empty="services" data-img="buffet.jpg" data-titre="Buffet" style="background-image: url('assets/img/buffet.jpg')"></div>
        <div class="service-body">
          <h3 data-fr="Buffets & Réceptions" data-ar="البوفيه والاستقبالات">Buffets & Réceptions</h3>
          <p data-fr="Gastronomie marocaine et internationale pour tous vos événements." data-ar="مأكولات مغربية وعالمية لكل مناسباتكم.">Gastronomie marocaine et internationale pour tous vos événements.</p>
          <a href="pages/services.php#buffets" class="service-link" data-fr="En savoir plus" data-ar="معرفة المزيد" data-html>En savoir plus <i class="fas fa-arrow-right"></i></a>
        </div>
      </div>
    </div>
    <div class="text-center" data-aos="fade-up" style="margin-top:40px">
      <a href="pages/services.php" class="btn-outline" data-fr="Voir tous nos services" data-ar="عرض جميع خدماتنا" data-html>
        Voir tous nos services <i class="fas fa-arrow-right"></i>
      </a>
    </div>
  </div>
</section>

<!-- ══════════════════════════════════════════════
     WHY US
══════════════════════════════════════════════ -->
<section id="why-us" class="section section-dark">
  <div class="container">
    <div class="why-grid">
      <div class="why-visual" data-aos="fade-right">
        <div class="why-img-frame">
          <div class="why-img" style="background-image:url('assets/img/team.jpg')"></div>
          <div class="why-img-deco"></div>
          <div class="why-badge-float">
            <i class="fas fa-award"></i>
            <span data-fr="Excellence<br>garantie" data-ar="جودة<br>مضمونة" data-html>Excellence<br>garantie</span>
          </div>
        </div>
      </div>
      <div class="why-content" data-aos="fade-left">
        <span class="section-tag light" data-fr="Pourquoi nous choisir ?" data-ar="لماذا تختارنا؟">Pourquoi nous choisir ?</span>
        <h2 class="section-title light" data-fr="L'Art de Célébrer<br>à l'Orientale" data-ar="فن الاحتفال<br>على الطريقة الشرقية" data-html>L'Art de Célébrer<br>à l'Orientale</h2>
        <p class="why-desc" data-fr="Depuis plus de 10 ans, Traiteur EL MOUSSAOUI sublime vos moments précieux avec passion et expertise. Notre équipe dédiée fait de chaque événement une expérience unique." data-ar="منذ أكثر من 10 سنوات، يضفي مطعم المساوي لمسة ساحرة على لحظاتكم الثمينة بشغف وخبرة. فريقنا المتفاني يجعل من كل مناسبة تجربة فريدة.">Depuis plus de 10 ans, Traiteur EL MOUSSAOUI sublime vos moments précieux avec passion et expertise. Notre équipe dédiée fait de chaque événement une expérience unique.</p>
        <div class="why-features">
          <div class="why-feature">
            <div class="why-icon"><i class="fas fa-gem"></i></div>
            <div>
              <h4 data-fr="Décoration Luxueuse" data-ar="ديكور فاخر">Décoration Luxueuse</h4>
              <p data-fr="Fleurs fraîches, nappage premium, ambiance sur-mesure pour chaque fête." data-ar="زهور طازجة، مفارش راقية، وأجواء مخصصة لكل حفل.">Fleurs fraîches, nappage premium, ambiance sur-mesure pour chaque fête.</p>
            </div>
          </div>
          <div class="why-feature">
            <div class="why-icon"><i class="fas fa-concierge-bell"></i></div>
            <div>
              <h4 data-fr="Service Irréprochable" data-ar="خدمة لا تشوبها شائبة">Service Irréprochable</h4>
              <p data-fr="Équipe professionnelle et attentive du début à la fin de votre événement." data-ar="فريق محترف ومهتم من بداية مناسبتكم إلى نهايتها.">Équipe professionnelle et attentive du début à la fin de votre événement.</p>
            </div>
          </div>
          <div class="why-feature">
            <div class="why-icon"><i class="fas fa-utensils"></i></div>
            <div>
              <h4 data-fr="Cuisine Authentique" data-ar="مأكولات أصيلة">Cuisine Authentique</h4>
              <p data-fr="Recettes marocaines traditionnelles et cuisine internationale de qualité." data-ar="أطباق مغربية تقليدية ومأكولات عالمية راقية.">Recettes marocaines traditionnelles et cuisine internationale de qualité.</p>
            </div>
          </div>
          <div class="why-feature">
            <div class="why-icon"><i class="fas fa-handshake"></i></div>
            <div>
              <h4 data-fr="Devis Personnalisé" data-ar="عرض سعر مخصص">Devis Personnalisé</h4>
              <p data-fr="Tarifs adaptés à votre budget, transparence totale sans surprises." data-ar="أسعار تناسب ميزانيتكم، بكل شفافية ودون مفاجآت.">Tarifs adaptés à votre budget, transparence totale sans surprises.</p>
            </div>
          </div>
        </div>
        <a href="pages/apropos.php" class="btn-primary mt-2" data-fr="En savoir plus sur nous" data-ar="اعرف المزيد عنا" data-html>
          En savoir plus sur nous <i class="fas fa-arrow-right"></i>
        </a>
      </div>
    </div>
  </div>
</section>

<!-- ══════════════════════════════════════════════
     PACKAGES APERÇU
══════════════════════════════════════════════ -->
<section id="packages-preview" class="section">
  <div class="container">
    <div class="section-header" data-aos="fade-up">
      <span class="section-tag" data-fr="Nos formules" data-ar="باقاتنا">Nos formules</span>
      <h2 class="section-title" data-fr="Packages & Tarifs" data-ar="الباقات والأسعار">Packages & Tarifs</h2>
      <p class="section-desc" data-fr="Des formules adaptées à chaque budget pour que votre fête soit inoubliable." data-ar="باقات تناسب كل الميزانيات لتكون حفلتكم لا تُنسى.">Des formules adaptées à chaque budget pour que votre fête soit inoubliable.</p>
    </div>
    <div class="packages-grid packages-grid-simple">
      <div class="pkg-card" data-aos="fade-up" data-aos-delay="100">
        <div class="pkg-header bronze">
          <div class="pkg-icon"><i class="fas fa-medal"></i></div>
          <h3 data-fr="Bronze" data-ar="برونزية">Bronze</h3>
          <p data-fr="50–100 invités" data-ar="50-100 ضيف">50–100 invités</p>
        </div>
        <p class="pkg-summary" data-fr="L'essentiel pour une fête familiale réussie et sans stress." data-ar="الأساسيات لحفل عائلي ناجح وبدون عناء.">L'essentiel pour une fête familiale réussie et sans stress.</p>
        <a href="pages/packages.php" class="btn-pkg" data-fr="Voir les détails" data-ar="عرض التفاصيل">Voir les détails</a>
      </div>

      <div class="pkg-card featured" data-aos="fade-up" data-aos-delay="200">
        <div class="pkg-badge" data-fr="Recommandé" data-ar="الأكثر تميزاً">Recommandé</div>
        <div class="pkg-header gold">
          <div class="pkg-icon"><i class="fas fa-crown"></i></div>
          <h3 data-fr="Or" data-ar="ذهبية">Or</h3>
          <p data-fr="120–250 invités" data-ar="120-250 ضيف">120–250 invités</p>
        </div>
        <p class="pkg-summary" data-fr="Notre formule la plus complète : gastronomie, décor et animation." data-ar="باقتنا الأكثر شمولاً: أكل راقٍ، ديكور وأنشطة." data-html>Notre formule la plus complète : gastronomie, décor et animation.</p>
        <a href="pages/packages.php" class="btn-pkg gold" data-fr="Voir les détails" data-ar="عرض التفاصيل">Voir les détails</a>
      </div>

      <div class="pkg-card" data-aos="fade-up" data-aos-delay="300">
        <div class="pkg-header platinum">
          <div class="pkg-icon"><i class="fas fa-star"></i></div>
          <h3 data-fr="Platine" data-ar="بلاتينية">Platine</h3>
          <p data-fr="200–500 invités" data-ar="200-500 ضيف">200–500 invités</p>
        </div>
        <p class="pkg-summary" data-fr="L'expérience tout inclus pour un événement d'exception." data-ar="تجربة شاملة لمناسبة استثنائية.">L'expérience tout inclus pour un événement d'exception.</p>
        <a href="pages/packages.php" class="btn-pkg" data-fr="Voir les détails" data-ar="عرض التفاصيل">Voir les détails</a>
      </div>
    </div>
    <div class="packages-note" data-aos="fade-up">
      <i class="fas fa-info-circle"></i>
      <span data-fr="Tous les packages sont personnalisables." data-ar="جميع الباقات قابلة للتخصيص.">Tous les packages sont personnalisables.</span> <a href="pages/reservation.php" data-fr="Demandez votre devis gratuit" data-ar="اطلب عرض سعرك المجاني">Demandez votre devis gratuit</a> <span data-fr="et sur-mesure." data-ar="والمخصص لك.">et sur-mesure.</span>
    </div>
  </div>
</section>

<!-- ══════════════════════════════════════════════
     GALERIE APERÇU
══════════════════════════════════════════════ -->
<section id="galerie-preview" class="section section-dark">
  <div class="container">
    <div class="section-header" data-aos="fade-up">
      <span class="section-tag light" data-fr="Nos réalisations" data-ar="أعمالنا">Nos réalisations</span>
      <h2 class="section-title light" data-fr="Galerie d'Événements" data-ar="معرض المناسبات">Galerie d'Événements</h2>
      <p class="section-desc" style="color:rgba(255,255,255,.5);margin-top:12px"
         data-fr="Découvrez nos plus belles réalisations en photos et vidéos."
         data-ar="اكتشف أجمل أعمالنا بالصور والفيديوهات.">
        Découvrez nos plus belles réalisations en photos et vidéos.
      </p>
    </div>
    <?php if (!empty($homeGalerie)): ?>
    <div class="gallery-preview-grid">
      <?php foreach ($homeGalerie as $g):
        $src = UPLOAD_URL . '/' . $g['fichier'];
        $alt = htmlspecialchars($g['alt_text'] ?: $g['titre'] ?: 'Réalisation Traiteur EL MOUSSAOUI');
      ?>
      <a href="pages/galerie.php" class="gallery-preview-item" data-aos="zoom-in">
        <img src="<?= htmlspecialchars($src) ?>" alt="<?= $alt ?>" loading="lazy">
      </a>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
    <div class="text-center" data-aos="fade-up" data-aos-delay="150" style="margin-top:40px">
      <a href="pages/galerie.php" class="btn-primary large" data-fr="Voir toute la galerie" data-ar="عرض كل المعرض">
        <i class="fas fa-images"></i> Voir toute la galerie
      </a>
    </div>
  </div>
</section>

<!-- ══════════════════════════════════════════════
     TÉMOIGNAGES
══════════════════════════════════════════════ -->
<section id="temoignages" class="section">
  <div class="container">
    <div class="section-header" data-aos="fade-up">
      <span class="section-tag" data-fr="Ce que disent nos clients" data-ar="ماذا يقول عملاؤنا">Ce que disent nos clients</span>
      <h2 class="section-title" data-fr="Témoignages" data-ar="آراء العملاء">Témoignages</h2>
    </div>
    <div class="testimonials-slider" id="testimonialsSlider">
      <div class="testimonial-track" id="testimonialTrack">
        <?php if (!empty($homeTemoignages)): ?>
          <?php foreach ($homeTemoignages as $t):
            $stars = str_repeat('★', (int)$t['note']) . str_repeat('☆', 5 - (int)$t['note']);
            $initiale = strtoupper(mb_substr($t['nom_client'], 0, 1));
            $lieu = trim(($t['ville'] ?: '') . ($t['type_evenement'] ? ' — ' . $t['type_evenement'] : ''));
          ?>
          <div class="testimonial-card">
            <div class="testi-stars"><?= $stars ?></div>
            <p><?= htmlspecialchars($t['contenu']) ?></p>
            <div class="testi-author">
              <div class="testi-avatar"><?= htmlspecialchars($initiale) ?></div>
              <div>
                <strong><?= htmlspecialchars($t['nom_client']) ?></strong>
                <span><?= htmlspecialchars($lieu) ?></span>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
        <?php else: ?>
          <div class="testimonial-card">
            <div class="testi-stars">★★★★★</div>
            <p data-fr="Vos avis apparaîtront bientôt ici. Merci pour votre confiance !" data-ar="ستظهر آراؤكم هنا قريباً. شكراً لثقتكم!">Vos avis apparaîtront bientôt ici. Merci pour votre confiance !</p>
            <div class="testi-author">
              <div class="testi-avatar">E</div>
              <div><strong>Traiteur EL MOUSSAOUI</strong><span data-fr="Errachidia" data-ar="الراشيدية">Errachidia</span></div>
            </div>
          </div>
        <?php endif; ?>
      </div>
      <div class="testi-controls">
        <button class="testi-prev" id="testiPrev"><i class="fas fa-chevron-left"></i></button>
        <div class="testi-dots" id="testiDots"></div>
        <button class="testi-next" id="testiNext"><i class="fas fa-chevron-right"></i></button>
      </div>
    </div>
    <div class="text-center" data-aos="fade-up" style="margin-top:32px">
      <a href="pages/apropos.php#temoignages" class="service-link" data-fr="Voir tous les avis" data-ar="عرض جميع الآراء" data-html>Voir tous les avis <i class="fas fa-arrow-right"></i></a>
    </div>
  </div>
</section>

<!-- ══════════════════════════════════════════════
     CTA RÉSERVATION
══════════════════════════════════════════════ -->
<section id="cta-reservation" class="section-cta">
  <div class="cta-bg"></div>
  <div class="container">
    <div class="cta-content" data-aos="zoom-in">
      <h2 data-fr="Prêt à organiser votre<br>événement de rêve ?" data-ar="مستعدون لتنظيم<br>مناسبتكم الحلم؟" data-html>Prêt à organiser votre<br>événement de rêve ?</h2>
      <p data-fr="Contactez-nous dès aujourd'hui pour un devis gratuit et personnalisé.<br>Notre équipe vous répond dans les 24 heures." data-ar="تواصلوا معنا اليوم للحصول على عرض سعر مجاني ومخصص.<br>فريقنا يرد عليكم في غضون 24 ساعة." data-html>Contactez-nous dès aujourd'hui pour un devis gratuit et personnalisé.<br>Notre équipe vous répond dans les 24 heures.</p>
      <div class="cta-actions">
        <a href="pages/reservation.php" class="btn-primary large" data-fr="Demander un devis gratuit" data-ar="طلب عرض سعر مجاني" data-html>
          <i class="fas fa-calendar-check"></i> Demander un devis gratuit
        </a>
        <a href="https://wa.me/<?= htmlspecialchars($waNumeroIndex) ?>" target="_blank" class="btn-whatsapp large" data-html>
          <i class="fab fa-whatsapp"></i> WhatsApp : <span dir="ltr"><?= htmlspecialchars($telAfficheIndex) ?></span>
        </a>
      </div>
    </div>
  </div>
</section>

<!-- ══════════════════════════════════════════════
     FOOTER
══════════════════════════════════════════════ -->
<footer id="footer">
  <div class="footer-top">
    <div class="container">
      <div class="footer-grid">
        <!-- Brand -->
        <div class="footer-col footer-brand">
          <div class="footer-logo">
            <span class="logo-traiteur">TRAITEUR</span>
            <span class="logo-name">EL MOUSSAOUI</span>
            <span class="logo-ar">أفراح المساوي</span>
          </div>
          <p data-fr="Organisation des Évènements et des Fêtes à Errachidia. Votre bonheur est notre priorité." data-ar="تنظيم المناسبات والحفلات بالراشيدية. سعادتكم هي أولويتنا.">Organisation des Évènements et des Fêtes à Errachidia. Votre bonheur est notre priorité.</p>
          <div class="footer-social">
            <a href="https://www.facebook.com/profile.php?id=61565592029636" target="_blank" rel="noopener" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
            <a href="https://www.instagram.com/elmoussaoui_traiteur__officiel/" target="_blank" rel="noopener" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
            <a href="https://wa.me/<?= htmlspecialchars($waNumeroIndex) ?>" target="_blank" rel="noopener" aria-label="WhatsApp"><i class="fab fa-whatsapp"></i></a>
          </div>
        </div>
        <!-- Services -->
        <div class="footer-col">
          <h4 data-fr="Nos Services" data-ar="خدماتنا">Nos Services</h4>
          <ul>
            <li><a href="pages/services.php#mariages" data-fr="Mariages" data-ar="حفلات الزفاف">Mariages</a></li>
            <li><a href="pages/services.php#fiancailles" data-fr="Fiançailles" data-ar="الخطوبة">Fiançailles</a></li>
            <li><a href="pages/services.php#anniversaires" data-fr="Célébrations" data-ar="الاحتفالات">Célébrations</a></li>
            <li><a href="pages/services.php#buffets" data-fr="Buffets & Réceptions" data-ar="البوفيه والاستقبالات">Buffets & Réceptions</a></li>
            <li><a href="pages/services.php" data-fr="Voir tous les services" data-ar="جميع الخدمات">Voir tous les services →</a></li>
          </ul>
        </div>
        <!-- Liens -->
        <div class="footer-col">
          <h4 data-fr="Liens utiles" data-ar="روابط مفيدة">Liens utiles</h4>
          <ul>
            <li><a href="index.php" data-fr="Accueil" data-ar="الرئيسية">Accueil</a></li>
            <li><a href="pages/packages.php" data-fr="Packages & Tarifs" data-ar="الباقات والأسعار">Packages & Tarifs</a></li>
            <li><a href="pages/galerie.php" data-fr="Galerie" data-ar="معرض الصور">Galerie</a></li>
            <li><a href="pages/blog.php" data-fr="Blog" data-ar="المقالات">Blog</a></li>
            <li><a href="pages/apropos.php" data-fr="À Propos" data-ar="من نحن">À Propos</a></li>
            <li><a href="pages/reservation.php" data-fr="Réservation" data-ar="الحجز">Réservation</a></li>
            <li><a href="admin/login.php" data-fr="Espace Admin" data-ar="لوحة التحكم">Espace Admin</a></li>
          </ul>
        </div>
        <!-- Contact -->
        <div class="footer-col">
          <h4 data-fr="Contact" data-ar="اتصل بنا">Contact</h4>
          <ul class="footer-contact">
            <li><i class="fas fa-map-marker-alt"></i> <span><?= htmlspecialchars($paramsContact['contact_adresse']) ?></span></li>
            <li><i class="fas fa-phone"></i> <a href="tel:<?= htmlspecialchars($paramsContact['contact_telephone']) ?>"><span dir="ltr"><?= htmlspecialchars($telAfficheIndex) ?></span></a></li>
            <li><i class="fab fa-whatsapp"></i> <a href="https://wa.me/<?= htmlspecialchars($waNumeroIndex) ?>" data-fr="WhatsApp direct" data-ar="واتساب مباشر">WhatsApp direct</a></li>
            <?php if (!empty($paramsContact['contact_email'])): ?>
            <li><i class="fas fa-envelope"></i> <a href="mailto:<?= htmlspecialchars($paramsContact['contact_email']) ?>"><?= htmlspecialchars($paramsContact['contact_email']) ?></a></li>
            <?php endif; ?>
            <li><i class="fas fa-clock"></i> <span><?= htmlspecialchars($paramsContact['horaires_ouverture']) ?></span></li>
          </ul>
        </div>
      </div>
    </div>
  </div>
  <div class="footer-bottom">
    <div class="container">
      <p data-fr="© 2025 Traiteur EL MOUSSAOUI — Errachidia, Maroc. Tous droits réservés." data-ar="© 2025 مطعم المساوي - الراشيدية، المغرب. جميع الحقوق محفوظة.">© 2025 Traiteur EL MOUSSAOUI — Errachidia, Maroc. Tous droits réservés.</p>
      <p><a href="#" data-fr="Politique de confidentialité" data-ar="سياسة الخصوصية">Politique de confidentialité</a> · <a href="#" data-fr="Mentions légales" data-ar="الإشعار القانوني">Mentions légales</a></p>
    </div>
  </div>
</footer>

<!-- WhatsApp Float Button (desktop) -->
<a href="https://wa.me/<?= htmlspecialchars($waNumeroIndex) ?>" class="whatsapp-float" target="_blank" title="Contactez-nous sur WhatsApp">
  <i class="fab fa-whatsapp"></i>
</a>
<a href="https://www.instagram.com/elmoussaoui_traiteur__officiel/" class="instagram-float" target="_blank" rel="noopener" title="Suivez-nous sur Instagram">
  <i class="fab fa-instagram"></i>
</a>
<a href="https://www.facebook.com/profile.php?id=61565592029636" class="facebook-float" target="_blank" rel="noopener" title="Suivez-nous sur Facebook">
  <i class="fab fa-facebook-f"></i>
</a>

<!-- Barre d'actions flottante mobile -->
<div class="mobile-action-bar">
  <a href="https://wa.me/<?= htmlspecialchars($waNumeroIndex) ?>" target="_blank" rel="noopener" class="mab-item mab-whatsapp">
    <i class="fab fa-whatsapp"></i><span data-fr="WhatsApp" data-ar="واتساب">WhatsApp</span>
  </a>
  <a href="https://www.instagram.com/elmoussaoui_traiteur__officiel/" target="_blank" rel="noopener" class="mab-item mab-instagram">
    <i class="fab fa-instagram"></i><span>Instagram</span>
  </a>
  <a href="pages/reservation.php" class="mab-item mab-reserve">
    <i class="fas fa-calendar-check"></i><span data-fr="Réserver" data-ar="احجز">Réserver</span>
  </a>
</div>

<!-- Scroll To Top -->
<button class="scroll-top" id="scrollTop" title="Retour en haut">
  <i class="fas fa-chevron-up"></i>
</button>

<!-- Scripts -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js"></script>
<script src="js/main.js"></script>
<script src="js/lang.js"></script>
<?php include_once __DIR__ . '/includes/admin-bar.php'; ?>
</body>
</html>
