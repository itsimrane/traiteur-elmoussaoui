<?php
require_once __DIR__ . '/../includes/config.php';

try {
    $services = $pdo->query("SELECT * FROM services WHERE actif=1 ORDER BY ordre ASC")->fetchAll();
} catch(Exception $e) { $services = []; }

// Services statiques si BDD vide
if (empty($services)) {
    $services = [
        ['id'=>1,'nom'=>'Restauration & Traiteur','nom_ar'=>'التموين والمطعم','description'=>'Buffet complet, plats marocains traditionnels et modernes, pâtisseries, jus de fruits.','description_ar'=>'بوفيه متكامل، أطباق مغربية تقليدية وعصرية، حلويات، عصائر طازجة.','icone'=>'fa-utensils','slug'=>'restauration'],
        ['id'=>2,'nom'=>'Décoration & Scénographie','nom_ar'=>'الزينة والديكور','description'=>'Décoration florale, tissus, éclairages LED et mise en scène complète de la salle.','description_ar'=>'زينة زهرية، أقمشة، إضاءة LED وتزيين كامل للقاعة.','icone'=>'fa-paint-brush','slug'=>'decoration'],
        ['id'=>3,'nom'=>'Tente & Structure','nom_ar'=>'الخيمة والهيكل','description'=>'Location et installation de tentes de réception toutes tailles, chapiteaux et structures.','description_ar'=>'تأجير وتركيب خيام الاستقبال بجميع الأحجام والهياكل.','icone'=>'fa-tent','slug'=>'tente'],
        ['id'=>4,'nom'=>'Animation Musicale','nom_ar'=>'الموسيقى والترفيه','description'=>'Groupe musical, DJ, chanteur andalou ou gnaoua selon vos préférences.','description_ar'=>'فرقة موسيقية، DJ، مغني أندلسي أو كناوي حسب تفضيلاتك.','icone'=>'fa-music','slug'=>'animation'],
        ['id'=>5,'nom'=>'Photo & Vidéo Professionnelle','nom_ar'=>'التصوير الفوتوغرافي','description'=>'Photographe et vidéaste professionnels, reportage complet, drone disponible.','description_ar'=>'مصور ومصور فيديو محترفان، تغطية كاملة، طائرة مسيّرة متاحة.','icone'=>'fa-camera','slug'=>'photo-video'],
        ['id'=>6,'nom'=>'Coordination d\'Événement','nom_ar'=>'تنسيق الحفل','description'=>'Chef de projet dédié pour coordonner tous les prestataires le jour de votre événement.','description_ar'=>'مدير مشروع مخصص لتنسيق جميع مزودي الخدمات يوم مناسبتك.','icone'=>'fa-clipboard-list','slug'=>'coordination'],
    ];
}
?>
<!DOCTYPE html>
<html lang="fr" dir="ltr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Services — Traiteur EL MOUSSAOUI | Errachidia</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,600;0,700;1,400&family=Jost:wght@300;400;500;600&family=Amiri:wght@400;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.css">
  <link rel="stylesheet" href="../css/style.css">
  <style>
    /* ── Grille services ──────────────────────────── */
    .services-grid-full{display:grid;grid-template-columns:repeat(auto-fit,minmax(320px,1fr));gap:24px;margin-bottom:60px}
    .service-card-full{background:var(--dark-card);border:1px solid var(--border);border-radius:var(--radius-lg);overflow:hidden;transition:var(--transition);display:flex;flex-direction:column}
    .service-card-full:hover{transform:translateY(-4px);border-color:rgba(212,175,55,.3);box-shadow:0 16px 48px rgba(0,0,0,.4)}
    .svc-img-zone{height:180px;background:var(--dark-3);position:relative;overflow:hidden;display:flex;align-items:center;justify-content:center}
    .svc-img-zone i{font-size:3.5rem;color:rgba(212,175,55,.2)}
    .svc-img-zone img{width:100%;height:100%;object-fit:cover;position:absolute;inset:0}
    .svc-img-overlay{position:absolute;inset:0;background:linear-gradient(to top,rgba(0,0,0,.7) 0%,transparent 60%)}
    .svc-icon-badge{position:absolute;bottom:14px;left:14px;width:44px;height:44px;border-radius:12px;background:rgba(10,10,15,.85);border:1px solid rgba(212,175,55,.3);display:flex;align-items:center;justify-content:center;color:var(--gold);font-size:1.1rem}
    .svc-icon-big{display:flex;align-items:center;justify-content:center;width:100%;height:100%;font-size:3.5rem;color:rgba(212,175,55,.2)}
    .svc-body{padding:22px;flex:1;display:flex;flex-direction:column}
    .svc-name{font-size:1.05rem;font-weight:700;color:var(--white);margin-bottom:4px}
    .svc-name-ar{font-size:.82rem;color:var(--gold);opacity:.7;font-family:'Amiri',serif;margin-bottom:10px}
    .svc-desc{font-size:.82rem;color:var(--text-muted);line-height:1.7;flex:1}
    .svc-footer{margin-top:16px;padding-top:14px;border-top:1px solid var(--border);display:flex;align-items:center;justify-content:space-between}
    /* ✅ Pas de prix — badge devis à la place */
    .svc-devis-tag{display:inline-flex;align-items:center;gap:5px;background:rgba(212,175,55,.08);border:1px solid rgba(212,175,55,.2);color:var(--gold);padding:5px 12px;border-radius:20px;font-size:.72rem;font-weight:600}
    .svc-link{font-size:.78rem;color:var(--gold);text-decoration:none;display:flex;align-items:center;gap:5px;transition:var(--transition)}
    .svc-link:hover{opacity:.75}

    /* ── Pourquoi nous ────────────────────────────── */
    .why-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:20px;margin-bottom:60px}
    .why-card{background:var(--dark-card);border:1px solid var(--border);border-radius:var(--radius);padding:24px;text-align:center}
    .why-icon{width:54px;height:54px;border-radius:14px;background:rgba(212,175,55,.1);display:flex;align-items:center;justify-content:center;font-size:1.3rem;color:var(--gold);margin:0 auto 14px}
    .why-title{font-size:.9rem;font-weight:700;color:var(--white);margin-bottom:8px}
    .why-desc{font-size:.78rem;color:var(--text-muted);line-height:1.6}

    @media(max-width:768px){.services-grid-full{grid-template-columns:1fr}.why-grid{grid-template-columns:1fr}}
  </style>
</head>
<body>
<div id="loader"><div class="loader-inner"><div class="loader-ring"></div><div class="loader-logo"><span class="loader-em">EL</span><span class="loader-moussaoui">MOUSSAOUI</span></div></div></div>


<?php $navActive = "services"; include_once __DIR__ . "/../includes/navbar.php"; ?>


<div class="page-hero">
  <div class="container">
    <span class="section-tag" data-aos="fade-down" data-fr="Ce que nous offrons" data-ar="ما نقدمه">Ce que nous offrons</span>
    <h1 data-aos="fade-up" data-fr="Nos Services" data-ar="خدماتنا">Nos Services</h1>
    <p data-aos="fade-up" data-aos-delay="200"
       data-fr="Nous prenons en charge chaque aspect de votre événement pour vous offrir une expérience inoubliable."
       data-ar="نتولى كل جانب من جوانب مناسبتك لنقدم لك تجربة لا تُنسى.">
      Nous prenons en charge chaque aspect de votre événement pour vous offrir une expérience inoubliable.
    </p>
  </div>
</div>

<section class="section">
  <div class="container">

    <!-- Grille services -->
    <div class="services-grid-full">
      <?php foreach ($services as $idx => $s): ?>
      <div class="service-card-full" id="<?= htmlspecialchars($s['slug'] ?? 'service-'.$s['id']) ?>"
           data-aos="fade-up" data-aos-delay="<?= ($idx % 3) * 80 ?>">
        <div class="svc-img-zone">
          <div class="svc-icon-big"><i class="fas <?= htmlspecialchars($s['icone'] ?? 'fa-star') ?>"></i></div>
          <div class="svc-img-overlay"></div>
        </div>
        <div class="svc-body">
          <div class="svc-name"><?= htmlspecialchars($s['nom']) ?></div>
          <?php if (!empty($s['nom_ar'])): ?>
          <div class="svc-name-ar"><?= htmlspecialchars($s['nom_ar']) ?></div>
          <?php endif; ?>
          <div class="svc-desc"
               data-fr="<?= htmlspecialchars($s['description'] ?? '') ?>"
               data-ar="<?= htmlspecialchars($s['description_ar'] ?? $s['description'] ?? '') ?>">
            <?= htmlspecialchars($s['description'] ?? '') ?>
          </div>
          <div class="svc-footer">
            <!-- ✅ Pas de prix — redirection vers devis -->
            <span class="svc-devis-tag">
              <i class="fas fa-file-invoice"></i>
              <span data-fr="Sur devis" data-ar="بعرض أسعار">Sur devis</span>
            </span>
            <a href="reservation.php" class="svc-link">
              <span data-fr="Demander un devis" data-ar="طلب عرض أسعار">Demander un devis</span>
              <i class="fas fa-arrow-right"></i>
            </a>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

    <!-- Pourquoi nous choisir -->
    <div class="section-header" data-aos="fade-up">
      <span class="section-tag" data-fr="Nos engagements" data-ar="التزاماتنا">Nos engagements</span>
      <h2 class="section-title" data-fr="Pourquoi nous choisir ?" data-ar="لماذا تختارنا؟">Pourquoi nous choisir ?</h2>
    </div>
    <div class="why-grid">
      <div class="why-card" data-aos="fade-up" data-aos-delay="0">
        <div class="why-icon"><i class="fas fa-medal"></i></div>
        <div class="why-title" data-fr="10+ ans d'expérience" data-ar="+10 سنوات خبرة">10+ ans d'expérience</div>
        <div class="why-desc" data-fr="Experts en organisation d'événements à Errachidia et dans la région depuis plus d'une décennie." data-ar="خبراء في تنظيم المناسبات بالراشيدية والمنطقة منذ أكثر من عقد.">Experts en organisation d'événements à Errachidia et dans la région depuis plus d'une décennie.</div>
      </div>
      <div class="why-card" data-aos="fade-up" data-aos-delay="80">
        <div class="why-icon"><i class="fas fa-handshake"></i></div>
        <div class="why-title" data-fr="Devis gratuit & transparent" data-ar="عرض مجاني وشفاف">Devis gratuit & transparent</div>
        <div class="why-desc" data-fr="Pas de prix cachés — chaque service est détaillé dans votre devis. Vous connaissez le coût exact avant de vous engager." data-ar="لا أسعار خفية — كل خدمة مفصلة في عرض أسعارك. تعرف التكلفة الدقيقة قبل الالتزام.">Pas de prix cachés — chaque service est détaillé dans votre devis. Vous connaissez le coût exact avant de vous engager.</div>
      </div>
      <div class="why-card" data-aos="fade-up" data-aos-delay="160">
        <div class="why-icon"><i class="fas fa-map-marker-alt"></i></div>
        <div class="why-title" data-fr="Présent dans toute la région" data-ar="نغطي المنطقة بأكملها">Présent dans toute la région</div>
        <div class="why-desc" data-fr="Errachidia, Erfoud, Rissani, Goulmima, Rich, Tinghir, Ouarzazate et environs — nous nous déplaçons partout." data-ar="الراشيدية، أرفود، الريصاني، كلميمة، الريش، تنغير، ورزازات والمحيط — نتنقل في كل مكان.">Errachidia, Erfoud, Rissani, Goulmima, Rich, Tinghir, Ouarzazate et environs — nous nous déplaçons partout.</div>
      </div>
    </div>

  </div>
</section>

<section class="section-cta">
  <div class="cta-bg"></div>
  <div class="container">
    <div class="cta-content" data-aos="zoom-in">
      <h2 data-fr="Obtenez votre devis<br>gratuit maintenant" data-ar="احصل على عرض<br>أسعارك المجاني الآن" data-html>Obtenez votre devis<br>gratuit maintenant</h2>
      <div class="cta-actions">
        <a href="reservation.php" class="btn-primary large" data-fr="Demander un devis" data-ar="طلب عرض أسعار" data-html>
          <i class="fas fa-file-invoice"></i> Demander un devis
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
    <p>© 2025 Traiteur EL MOUSSAOUI — Errachidia, Maroc</p>
  </div></div>
</footer>

<a href="https://wa.me/212626986533" class="whatsapp-float" target="_blank"><i class="fab fa-whatsapp"></i></a>
<button class="scroll-top" id="scrollTop"><i class="fas fa-chevron-up"></i></button>

<script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js"></script>
<script src="../js/main.js"></script>
<script src="../js/lang.js"></script>
<?php include_once __DIR__ . '/../includes/admin-bar.php'; ?>
</body>
</html>
