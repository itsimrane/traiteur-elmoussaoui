<?php
/**
 * Page : index.php
 * Traiteur EL MOUSSAOUI
 */
require_once __DIR__ . '/includes/config.php';
?>
<!DOCTYPE html>
<html lang="fr" dir="ltr">
<head>
  <meta charset="UTF-8">
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
      <li class="has-dropdown">
        <a href="pages/services.php" class="nav-link" data-fr="Services" data-ar="خدماتنا" data-html>Services <i class="fas fa-chevron-down"></i></a>
        <ul class="dropdown">
          <li><a href="pages/services.php#mariages"><i class="fas fa-heart"></i> <span data-fr="Mariages" data-ar="حفلات الزفاف">Mariages</span></a></li>
          <li><a href="pages/services.php#fiancailles"><i class="fas fa-ring"></i> <span data-fr="Fiançailles" data-ar="الخطوبة">Fiançailles</span></a></li>
          <li><a href="pages/services.php#circoncision"><i class="fas fa-baby"></i> <span data-fr="Circoncisions" data-ar="حفلات الختان">Circoncisions</span></a></li>
          <li><a href="pages/services.php#anniversaires"><i class="fas fa-birthday-cake"></i> <span data-fr="Anniversaires" data-ar="أعياد الميلاد">Anniversaires</span></a></li>
          <li><a href="pages/services.php#entreprise"><i class="fas fa-briefcase"></i> <span data-fr="Réceptions Pro" data-ar="المناسبات المهنية">Réceptions Pro</span></a></li>
          <li><a href="pages/services.php#buffets"><i class="fas fa-utensils"></i> <span data-fr="Buffets & Banquets" data-ar="البوفيه والولائم">Buffets & Banquets</span></a></li>
          <li><a href="pages/services.php#ceremonies"><i class="fas fa-mosque"></i> <span data-fr="Cérémonies religieuses" data-ar="المناسبات الدينية">Cérémonies religieuses</span></a></li>
        </ul>
      </li>
      <li><a href="pages/packages.php" class="nav-link" data-fr="Packages" data-ar="الباقات">Packages</a></li>
      <li><a href="pages/galerie.php" class="nav-link" data-fr="Galerie" data-ar="معرض الصور">Galerie</a></li>
      <li><a href="pages/blog.php" class="nav-link" data-fr="Blog" data-ar="المقالات">Blog</a></li>
      <li><a href="pages/apropos.php" class="nav-link" data-fr="À Propos" data-ar="من نحن">À Propos</a></li>
      <li><a href="pages/contact.php" class="nav-link" data-fr="Contact" data-ar="اتصل بنا">Contact</a></li>
    </ul>

    <div class="nav-actions">
      <a href="tel:0626986533" class="nav-tel">
        <i class="fab fa-whatsapp"></i>
        <span><span dir="ltr">0626 986 533</span></span>
      </a>
      <a href="pages/reservation.php" class="btn-reservation" data-fr="Réserver" data-ar="احجز الآن" data-html>
        Réserver <i class="fas fa-arrow-right"></i>
      </a>
      <div class="lang-switch">
        <span class="lang-option active" data-lang="fr">FR</span>
        <span class="lang-option" data-lang="ar">AR</span>
      </div>
      <button class="nav-toggle" id="navToggle" aria-label="Menu">
        <span></span><span></span><span></span>
      </button>
    </div>
  </nav>
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
    <div class="hero-stats" data-aos="fade-up" data-aos-delay="1000">
      <div class="stat-item">
        <span class="stat-num" data-count="500">0</span><span class="stat-plus">+</span>
        <span class="stat-label" data-fr="Événements" data-ar="مناسبة">Événements</span>
      </div>
      <div class="stat-divider"></div>
      <div class="stat-item">
        <span class="stat-num" data-count="10">0</span><span class="stat-plus">+</span>
        <span class="stat-label" data-fr="Années d'exp." data-ar="سنوات الخبرة">Années d'exp.</span>
      </div>
      <div class="stat-divider"></div>
      <div class="stat-item">
        <span class="stat-num" data-count="98">0</span><span class="stat-pct">%</span>
        <span class="stat-label" data-fr="Clients satisfaits" data-ar="عملاء راضون">Clients satisfaits</span>
      </div>
    </div>
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

    <div class="services-grid">
      <div class="service-card" data-aos="fade-up" data-aos-delay="100">
        <div class="service-icon"><i class="fas fa-heart"></i></div>
        <div class="service-img" data-zone-empty="services" data-img="mariage.jpg" data-titre="Mariage" style="background-image: url('assets/img/mariage.jpg')"></div>
        <div class="service-body">
          <h3 data-fr="Mariages" data-ar="حفلات الزفاف">Mariages</h3>
          <p data-fr="Organisation complète de votre mariage : décoration, buffet, animation musicale, coordination." data-ar="تنظيم كامل لحفل زفافك: ديكور، بوفيه، موسيقى، وتنسيق شامل.">Organisation complète de votre mariage : décoration, buffet, animation musicale, coordination.</p>
          <a href="pages/services.php#mariages" class="service-link" data-fr="En savoir plus" data-ar="معرفة المزيد" data-html>En savoir plus <i class="fas fa-arrow-right"></i></a>
        </div>
      </div>

      <div class="service-card featured" data-aos="fade-up" data-aos-delay="200">
        <div class="service-badge" data-fr="Populaire" data-ar="الأكثر طلباً">Populaire</div>
        <div class="service-icon"><i class="fas fa-ring"></i></div>
        <div class="service-img" data-zone-empty="services" data-img="fiancailles.jpg" data-titre="Fiançailles" style="background-image: url('assets/img/fiancailles.jpg')"></div>
        <div class="service-body">
          <h3 data-fr="Fiançailles" data-ar="الخطوبة">Fiançailles</h3>
          <p data-fr="Cérémonie de fiançailles mémorable avec décoration florale et buffet raffiné." data-ar="حفل خطوبة لا يُنسى مع ديكور زهور وبوفيه راقٍ.">Cérémonie de fiançailles mémorable avec décoration florale et buffet raffiné.</p>
          <a href="pages/services.php#fiancailles" class="service-link" data-fr="En savoir plus" data-ar="معرفة المزيد" data-html>En savoir plus <i class="fas fa-arrow-right"></i></a>
        </div>
      </div>

      <div class="service-card" data-aos="fade-up" data-aos-delay="300">
        <div class="service-icon"><i class="fas fa-baby"></i></div>
        <div class="service-img" data-zone-empty="services" data-img="circoncision.jpg" data-titre="Circoncision" style="background-image: url('assets/img/circoncision.jpg')"></div>
        <div class="service-body">
          <h3 data-fr="Circoncisions" data-ar="حفلات الختان">Circoncisions</h3>
          <p data-fr="Fêtes traditionnelles avec décoration colorée, buffet généreux et animation." data-ar="احتفالات تقليدية بديكور مبهج وبوفيه غني وأنشطة ترفيهية.">Fêtes traditionnelles avec décoration colorée, buffet généreux et animation.</p>
          <a href="pages/services.php#circoncision" class="service-link" data-fr="En savoir plus" data-ar="معرفة المزيد" data-html>En savoir plus <i class="fas fa-arrow-right"></i></a>
        </div>
      </div>

      <div class="service-card" data-aos="fade-up" data-aos-delay="100">
        <div class="service-icon"><i class="fas fa-birthday-cake"></i></div>
        <div class="service-img" data-zone-empty="services" data-img="anniversaire.jpg" data-titre="Anniversaire" style="background-image: url('assets/img/anniversaire.jpg')"></div>
        <div class="service-body">
          <h3 data-fr="Anniversaires" data-ar="أعياد الميلاد">Anniversaires</h3>
          <p data-fr="Célébrations d'anniversaire pour tous les âges avec ambiance personnalisée." data-ar="احتفالات أعياد ميلاد لجميع الأعمار بأجواء مخصصة.">Célébrations d'anniversaire pour tous les âges avec ambiance personnalisée.</p>
          <a href="pages/services.php#anniversaires" class="service-link" data-fr="En savoir plus" data-ar="معرفة المزيد" data-html>En savoir plus <i class="fas fa-arrow-right"></i></a>
        </div>
      </div>

      <div class="service-card" data-aos="fade-up" data-aos-delay="200">
        <div class="service-icon"><i class="fas fa-briefcase"></i></div>
        <div class="service-img" data-zone-empty="services" data-img="entreprise.jpg" data-titre="Réception Pro" style="background-image: url('assets/img/entreprise.jpg')"></div>
        <div class="service-body">
          <h3 data-fr="Réceptions Pro" data-ar="المناسبات المهنية">Réceptions Pro</h3>
          <p data-fr="Séminaires, galas d'entreprise et réceptions professionnelles de prestige." data-ar="ندوات وحفلات شركات واستقبالات مهنية راقية.">Séminaires, galas d'entreprise et réceptions professionnelles de prestige.</p>
          <a href="pages/services.php#entreprise" class="service-link" data-fr="En savoir plus" data-ar="معرفة المزيد" data-html>En savoir plus <i class="fas fa-arrow-right"></i></a>
        </div>
      </div>

      <div class="service-card" data-aos="fade-up" data-aos-delay="300">
        <div class="service-icon"><i class="fas fa-utensils"></i></div>
        <div class="service-img" data-zone-empty="services" data-img="buffet.jpg" data-titre="Buffet" style="background-image: url('assets/img/buffet.jpg')"></div>
        <div class="service-body">
          <h3 data-fr="Buffets & Banquets" data-ar="البوفيه والولائم">Buffets & Banquets</h3>
          <p data-fr="Buffets froids et chauds, gastronomie marocaine et internationale." data-ar="بوفيهات ساخنة وبادرة، مأكولات مغربية وعالمية.">Buffets froids et chauds, gastronomie marocaine et internationale.</p>
          <a href="pages/services.php#buffets" class="service-link" data-fr="En savoir plus" data-ar="معرفة المزيد" data-html>En savoir plus <i class="fas fa-arrow-right"></i></a>
        </div>
      </div>
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
    <div class="packages-grid">
      <div class="pkg-card" data-aos="fade-up" data-aos-delay="100">
        <div class="pkg-header bronze">
          <div class="pkg-icon"><i class="fas fa-medal"></i></div>
          <h3 data-fr="Bronze" data-ar="برونزية">Bronze</h3>
          <div class="pkg-devis-badge"><i class="fas fa-file-invoice"></i> <span data-fr="Sur devis" data-ar="بعرض أسعار">Sur devis</span></div>
          <p data-fr="50–100 invités" data-ar="50-100 ضيف">50–100 invités</p>
        </div>
        <ul class="pkg-features">
          <li><i class="fas fa-check"></i> <span data-fr="Restauration de base" data-ar="ضيافة أساسية">Restauration de base</span></li>
          <li><i class="fas fa-check"></i> <span data-fr="Décoration simple" data-ar="ديكور بسيط">Décoration simple</span></li>
          <li><i class="fas fa-check"></i> <span data-fr="1 serveur" data-ar="نادل واحد">1 serveur</span></li>
          <li><i class="fas fa-check"></i> <span data-fr="Thé & pâtisseries" data-ar="شاي وحلويات">Thé & pâtisseries</span></li>
          <li class="disabled"><i class="fas fa-times"></i> <span data-fr="Animation musicale" data-ar="فرقة موسيقية">Animation musicale</span></li>
          <li class="disabled"><i class="fas fa-times"></i> <span data-fr="Photographe" data-ar="مصور">Photographe</span></li>
        </ul>
        <a href="pages/packages.php" class="btn-pkg" data-fr="Choisir Bronze" data-ar="اختر البرونزية">Choisir Bronze</a>
      </div>

      <div class="pkg-card featured" data-aos="fade-up" data-aos-delay="200">
        <div class="pkg-badge" data-fr="Recommandé" data-ar="الأكثر تميزاً">Recommandé</div>
        <div class="pkg-header gold">
          <div class="pkg-icon"><i class="fas fa-crown"></i></div>
          <h3 data-fr="Or" data-ar="ذهبية">Or</h3>
          <div class="pkg-devis-badge"><i class="fas fa-file-invoice"></i> <span data-fr="Sur devis" data-ar="بعرض أسعار">Sur devis</span></div>
          <p data-fr="120–250 invités" data-ar="120-250 ضيف">120–250 invités</p>
        </div>
        <ul class="pkg-features">
          <li><i class="fas fa-check"></i> <span data-fr="Repas gastronomique" data-ar="وجبة فاخرة">Repas gastronomique</span></li>
          <li><i class="fas fa-check"></i> <span data-fr="Décoration premium" data-ar="ديكور راقٍ">Décoration premium</span></li>
          <li><i class="fas fa-check"></i> <span data-fr="5 serveurs" data-ar="5 نوادل">5 serveurs</span></li>
          <li><i class="fas fa-check"></i> <span data-fr="Gâteau personnalisé" data-ar="كعكة مخصصة">Gâteau personnalisé</span></li>
          <li><i class="fas fa-check"></i> <span data-fr="DJ & animation" data-ar="دي جي وأنشطة">DJ & animation</span></li>
          <li><i class="fas fa-check"></i> <span data-fr="Photographe" data-ar="مصور">Photographe</span></li>
        </ul>
        <a href="pages/packages.php" class="btn-pkg gold" data-fr="Choisir Or" data-ar="اختر الذهبية">Choisir Or</a>
      </div>

      <div class="pkg-card" data-aos="fade-up" data-aos-delay="300">
        <div class="pkg-header platinum">
          <div class="pkg-icon"><i class="fas fa-star"></i></div>
          <h3 data-fr="Platine" data-ar="بلاتينية">Platine</h3>
          <div class="pkg-devis-badge"><i class="fas fa-file-invoice"></i> <span data-fr="Sur devis" data-ar="بعرض أسعار">Sur devis</span></div>
          <p data-fr="200–500 invités" data-ar="200-500 ضيف">200–500 invités</p>
        </div>
        <ul class="pkg-features">
          <li><i class="fas fa-check"></i> <span data-fr="Tout inclus" data-ar="كل شيء مشمول">Tout inclus</span></li>
          <li><i class="fas fa-check"></i> <span data-fr="Tente de réception" data-ar="خيمة استقبال">Tente de réception</span></li>
          <li><i class="fas fa-check"></i> <span data-fr="Limousine décorée" data-ar="ليموزين مزينة">Limousine décorée</span></li>
          <li><i class="fas fa-check"></i> <span data-fr="Photo & Vidéo HD" data-ar="تصوير وفيديو HD">Photo & Vidéo HD</span></li>
          <li><i class="fas fa-check"></i> <span data-fr="Animateur live" data-ar="منشط حفلات">Animateur live</span></li>
          <li><i class="fas fa-check"></i> <span data-fr="Invitations imprimées" data-ar="دعوات مطبوعة">Invitations imprimées</span></li>
        </ul>
        <a href="pages/packages.php" class="btn-pkg" data-fr="Choisir Platine" data-ar="اختر البلاتينية">Choisir Platine</a>
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
        <div class="testimonial-card">
          <div class="testi-stars">★★★★★</div>
          <p data-fr="&quot;Un service exceptionnel pour notre mariage ! L'équipe d'EL MOUSSAOUI a tout géré avec professionnalisme. La décoration était magnifique et le buffet délicieux.&quot;" data-ar="&quot;خدمة استثنائية لحفل زفافنا! فريق المساوي تولى كل شيء بمهنية عالية. كان الديكور رائعاً والبوفيه شهياً.&quot;">"Un service exceptionnel pour notre mariage ! L'équipe d'EL MOUSSAOUI a tout géré avec professionnalisme. La décoration était magnifique et le buffet délicieux."</p>
          <div class="testi-author">
            <div class="testi-avatar">F</div>
            <div>
              <strong>Fatima Zahra B.</strong>
              <span data-fr="Errachidia — Mariage" data-ar="الراشيدية - زفاف">Errachidia — Mariage</span>
            </div>
          </div>
        </div>
        <div class="testimonial-card">
          <div class="testi-stars">★★★★★</div>
          <p data-fr="&quot;Très satisfait de l'organisation de nos fiançailles. Équipe réactive, prix raisonnables et résultat au-delà de nos espérances. Je recommande vivement !&quot;" data-ar="&quot;راضون جداً عن تنظيم خطوبتنا. فريق سريع الاستجابة، أسعار معقولة، ونتيجة تجاوزت توقعاتنا. أنصح به بشدة!&quot;">"Très satisfait de l'organisation de nos fiançailles. Équipe réactive, prix raisonnables et résultat au-delà de nos espérances. Je recommande vivement !"</p>
          <div class="testi-author">
            <div class="testi-avatar">M</div>
            <div>
              <strong>Mohammed K.</strong>
              <span data-fr="Errachidia — Fiançailles" data-ar="الراشيدية - خطوبة">Errachidia — Fiançailles</span>
            </div>
          </div>
        </div>
        <div class="testimonial-card">
          <div class="testi-stars">★★★★☆</div>
          <p data-fr="&quot;Service de qualité pour notre buffet familial. Livraison à temps, plats chauds et savoureux. Je referai appel à Traiteur EL MOUSSAOUI sans hésitation.&quot;" data-ar="&quot;خدمة راقية لبوفيه عائلتنا. التوصيل في الوقت المحدد، أطباق ساخنة ولذيذة. سأتعامل مع المساوي مجدداً دون تردد.&quot;">"Service de qualité pour notre buffet familial. Livraison à temps, plats chauds et savoureux. Je referai appel à Traiteur EL MOUSSAOUI sans hésitation."</p>
          <div class="testi-author">
            <div class="testi-avatar">A</div>
            <div>
              <strong>Aicha M.</strong>
              <span data-fr="Goulmima — Buffet" data-ar="كولميمة - بوفيه">Goulmima — Buffet</span>
            </div>
          </div>
        </div>
        <div class="testimonial-card">
          <div class="testi-stars">★★★★★</div>
          <p data-fr="&quot;Notre mariage était un vrai conte de fée grâce à leur travail. La tente, la décoration, la musique... tout était parfait. Bravo à toute l'équipe !&quot;" data-ar="&quot;كان زفافنا قصة خيالية حقيقية بفضل عملهم. الخيمة، الديكور، الموسيقى... كل شيء كان مثالياً. تحية لكل الفريق!&quot;">"Notre mariage était un vrai conte de fée grâce à leur travail. La tente, la décoration, la musique... tout était parfait. Bravo à toute l'équipe !"</p>
          <div class="testi-author">
            <div class="testi-avatar">H</div>
            <div>
              <strong>Hassan El A.</strong>
              <span data-fr="Erfoud — Mariage" data-ar="أرفود - زفاف">Erfoud — Mariage</span>
            </div>
          </div>
        </div>
      </div>
      <div class="testi-controls">
        <button class="testi-prev" id="testiPrev"><i class="fas fa-chevron-left"></i></button>
        <div class="testi-dots" id="testiDots"></div>
        <button class="testi-next" id="testiNext"><i class="fas fa-chevron-right"></i></button>
      </div>
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
        <a href="https://wa.me/212626986533" target="_blank" class="btn-whatsapp large" data-fr="WhatsApp : 0626 986 533" data-ar="واتساب: 0626986533" data-html>
          <i class="fab fa-whatsapp"></i> WhatsApp : <span dir="ltr">0626 986 533</span>
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
            <a href="#" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
            <a href="#" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
            <a href="https://wa.me/212626986533" aria-label="WhatsApp"><i class="fab fa-whatsapp"></i></a>
            <a href="#" aria-label="TikTok"><i class="fab fa-tiktok"></i></a>
          </div>
        </div>
        <!-- Services -->
        <div class="footer-col">
          <h4 data-fr="Nos Services" data-ar="خدماتنا">Nos Services</h4>
          <ul>
            <li><a href="pages/services.php#mariages" data-fr="Mariages" data-ar="حفلات الزفاف">Mariages</a></li>
            <li><a href="pages/services.php#fiancailles" data-fr="Fiançailles" data-ar="الخطوبة">Fiançailles</a></li>
            <li><a href="pages/services.php#circoncision" data-fr="Circoncisions" data-ar="حفلات الختان">Circoncisions</a></li>
            <li><a href="pages/services.php#anniversaires" data-fr="Anniversaires" data-ar="أعياد الميلاد">Anniversaires</a></li>
            <li><a href="pages/services.php#entreprise" data-fr="Réceptions Pro" data-ar="المناسبات المهنية">Réceptions Pro</a></li>
            <li><a href="pages/services.php#buffets" data-fr="Buffets & Banquets" data-ar="البوفيه والولائم">Buffets & Banquets</a></li>
            <li><a href="pages/services.php#ceremonies" data-fr="Cérémonies religieuses" data-ar="المناسبات الدينية">Cérémonies religieuses</a></li>
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
            <li><i class="fas fa-map-marker-alt"></i> <span data-fr="Errachidia, Région Drâa-Tafilalet, Maroc" data-ar="الراشيدية، جهة درعة تافيلالت، المغرب">Errachidia, Région Drâa-Tafilalet, Maroc</span></li>
            <li><i class="fas fa-phone"></i> <a href="tel:0626986533"><span dir="ltr">0626 986 533</span></a></li>
            <li><i class="fab fa-whatsapp"></i> <a href="https://wa.me/212626986533" data-fr="WhatsApp direct" data-ar="واتساب مباشر">WhatsApp direct</a></li>
            <li><i class="fas fa-envelope"></i> <a href="mailto:contact@traiteur-elmoussaoui.ma">contact@traiteur-elmoussaoui.ma</a></li>
            <li><i class="fas fa-clock"></i> <span data-fr="Lun–Sam : 08h–20h" data-ar="الإثنين-السبت: 08h-20h">Lun–Sam : 08h–20h</span></li>
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

<!-- WhatsApp Float Button -->
<a href="https://wa.me/212626986533" class="whatsapp-float" target="_blank" title="Contactez-nous sur WhatsApp">
  <i class="fab fa-whatsapp"></i>
</a>

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
