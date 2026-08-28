<?php
require_once __DIR__ . '/../includes/config.php';

// Charger les packages depuis la BDD
try {
    $packages = $pdo->query("SELECT * FROM packages WHERE actif=1 ORDER BY ordre ASC")->fetchAll();
} catch(Exception $e) { $packages = []; }
?>
<!DOCTYPE html>
<html lang="fr" dir="ltr">
<head>
  <meta charset="UTF-8">
  <link rel="icon" type="image/png" href="../assets/img/favicon-32.png">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Packages — Traiteur EL MOUSSAOUI | Errachidia</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,600;0,700;1,400&family=Jost:wght@300;400;500;600&family=Amiri:wght@400;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.css">
  <link rel="stylesheet" href="../css/style.css">
  <style>
    .pkg-cards-full{display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:24px;margin-bottom:60px}
    .pkg-card-full{background:var(--dark-card);border:1px solid var(--border);border-radius:var(--radius-lg);overflow:hidden;transition:var(--transition);position:relative;display:flex;flex-direction:column}
    .pkg-card-full:hover{transform:translateY(-6px);box-shadow:0 20px 60px rgba(0,0,0,.4)}
    .pkg-card-full.featured{border-color:var(--gold);box-shadow:0 0 0 1px rgba(212,175,55,.3)}
    .pkg-top{padding:32px 28px 24px;text-align:center;border-bottom:1px solid var(--border);position:relative}
    .pkg-medal{width:52px;height:52px;border-radius:14px;background:rgba(212,175,55,.1);display:flex;align-items:center;justify-content:center;font-size:1.4rem;color:var(--gold);margin:0 auto 14px}
    .pkg-card-full.bronze .pkg-medal{background:rgba(205,127,50,.12);color:#CD7F32}
    .pkg-card-full.argent .pkg-medal{background:rgba(192,192,192,.1);color:#C0C0C0}
    .pkg-card-full.or .pkg-medal{background:rgba(212,175,55,.15);color:#D4AF37}
    .pkg-card-full.platine .pkg-medal{background:rgba(168,85,247,.1);color:#C084FC}
    .pkg-top h3{font-family:var(--ff-display);font-size:1.6rem;color:var(--white);margin-bottom:6px}
    /* ── Prix supprimé ── */
    .pkg-guests{display:flex;align-items:center;justify-content:center;gap:6px;font-size:.8rem;color:var(--text-muted);margin-top:8px}
    .pkg-devis-badge{display:inline-flex;align-items:center;gap:6px;background:rgba(212,175,55,.1);border:1px solid rgba(212,175,55,.25);color:var(--gold);padding:8px 18px;border-radius:30px;font-size:.82rem;font-weight:600;margin:14px 0 4px}
    .recommended-badge{position:absolute;top:16px;right:16px;background:linear-gradient(135deg,var(--gold),var(--gold-dark));color:var(--dark);font-size:.62rem;font-weight:800;padding:4px 12px;border-radius:12px;letter-spacing:.5px}
    .pkg-list{list-style:none;padding:22px 28px;flex:1}
    .pkg-list li{display:flex;align-items:flex-start;gap:10px;padding:7px 0;border-bottom:1px solid rgba(255,255,255,.04);font-size:.84rem;color:var(--text-muted)}
    .pkg-list li:last-child{border-bottom:none}
    .pkg-list li i.fa-check{color:#25D366;font-size:.75rem;margin-top:3px;flex-shrink:0}
    .pkg-list li.disabled{opacity:.35}
    .pkg-list li.disabled i{color:#555}
    .btn-pkg-full{display:block;margin:0 28px 28px;padding:13px;text-align:center;border-radius:10px;background:linear-gradient(135deg,rgba(212,175,55,.15),rgba(212,175,55,.05));border:1px solid rgba(212,175,55,.3);color:var(--gold);font-family:var(--ff-body);font-size:.88rem;font-weight:700;text-decoration:none;transition:var(--transition);letter-spacing:.5px}
    .btn-pkg-full:hover{background:linear-gradient(135deg,var(--gold),var(--gold-dark));color:var(--dark);border-color:transparent}
    .btn-pkg-full.gold{background:linear-gradient(135deg,var(--gold),var(--gold-dark));color:var(--dark);border-color:transparent}
    .btn-pkg-full.gold:hover{opacity:.9}
    /* Compare table */
    .compare-table{width:100%;border-collapse:collapse;margin-bottom:60px}
    .compare-table th,.compare-table td{padding:13px 16px;border-bottom:1px solid var(--border);font-size:.83rem;text-align:center}
    .compare-table th{background:var(--dark-card);color:var(--text-muted);font-weight:600;font-size:.72rem;text-transform:uppercase;letter-spacing:.5px}
    .compare-table th:first-child,.compare-table td:first-child{text-align:left;color:var(--white)}
    .compare-table tr:hover td{background:rgba(255,255,255,.02)}
    .compare-table .fa-check{color:#25D366}
    .compare-table .fa-times{color:#444}
    .compare-table td:nth-child(4){background:rgba(212,175,55,.04)}
  </style>
</head>
<body>
<div id="loader"><div class="loader-inner"><div class="loader-ring"></div><div class="loader-logo"><span class="loader-em">EL</span><span class="loader-moussaoui">MOUSSAOUI</span></div></div></div>


<?php $navActive = "packages"; include_once __DIR__ . "/../includes/navbar.php"; ?>


<div class="page-hero">
  <div class="container">
    <span class="section-tag" data-aos="fade-down" data-fr="Nos formules" data-ar="باقاتنا">Nos formules</span>
    <h1 data-aos="fade-up" data-fr="Packages & Formules" data-ar="الباقات والعروض">Packages & Formules</h1>
    <p data-aos="fade-up" data-aos-delay="200"
       data-fr="Des formules complètes pour tous vos événements. Demandez un devis gratuit et personnalisé — les prix sont établis selon vos besoins réels."
       data-ar="باقات متكاملة لجميع مناسباتكم. اطلب عرض أسعار مجانياً ومخصصاً — يتم تحديد الأسعار وفقاً لاحتياجاتك الفعلية.">
      Des formules complètes pour tous vos événements. Demandez un devis gratuit et personnalisé — les prix sont établis selon vos besoins réels.
    </p>
  </div>
</div>

<section class="packages-full">
  <div class="container">

    <!-- Bandeau info -->
    <div data-aos="fade-up" style="background:rgba(212,175,55,.06);border:1px solid rgba(212,175,55,.2);border-radius:14px;padding:18px 24px;margin-bottom:40px;display:flex;align-items:center;gap:14px">
      <i class="fas fa-info-circle" style="color:var(--gold);font-size:1.3rem;flex-shrink:0"></i>
      <div>
        <strong style="color:var(--white);font-size:.9rem" data-fr="Devis gratuit & sans engagement" data-ar="عرض أسعار مجاني وبدون التزام">Devis gratuit & sans engagement</strong>
        <p style="color:var(--text-muted);font-size:.8rem;margin-top:3px"
           data-fr="Nous ne pratiquons pas de tarif fixe — chaque événement est unique. Sélectionnez votre formule et demandez un devis personnalisé selon votre date, ville et nombre d'invités."
           data-ar="لا نطبق أسعاراً ثابتة — كل مناسبة فريدة. اختر الباقة المناسبة واطلب عرض أسعار مخصصاً حسب تاريخك ومدينتك وعدد ضيوفك.">
          Nous ne pratiquons pas de tarif fixe — chaque événement est unique. Sélectionnez votre formule et demandez un devis personnalisé selon votre date, ville et nombre d'invités.
        </p>
      </div>
    </div>

    <div class="section-header" data-aos="fade-up">
      <span class="section-tag" data-fr="Comparez nos formules" data-ar="قارنوا باقاتنا">Comparez nos formules</span>
      <h2 class="section-title" data-fr="Choisissez votre Formule" data-ar="اختاروا باقتكم">Choisissez votre Formule</h2>
    </div>

    <?php if (!empty($packages)): ?>
    <div class="pkg-cards-full">
      <?php foreach ($packages as $idx => $pkg):
        $contenu    = json_decode($pkg['contenu']    ?? '[]', true) ?: [];
        $contenu_ar = json_decode($pkg['contenu_ar'] ?? '[]', true) ?: [];
        $slugs   = ['bronze','argent','or','platine'];
        $cssClass= $slugs[$idx] ?? 'bronze';
        $icons   = ['fa-medal','fa-gem','fa-crown','fa-star'];
        $icon    = $icons[$idx] ?? 'fa-star';
        $featured= $pkg['mis_en_avant'] ?? 0;
      ?>
      <div class="pkg-card-full <?= $cssClass ?> <?= $featured ? 'featured' : '' ?>" data-aos="fade-up" data-aos-delay="<?= $idx*80 ?>">
        <div class="pkg-top">
          <?php if ($featured): ?>
          <div class="recommended-badge" data-fr="⭐ RECOMMANDÉ" data-ar="⭐ الأكثر تميزاً">⭐ RECOMMANDÉ</div>
          <?php endif; ?>
          <div class="pkg-medal"><i class="fas <?= $icon ?>"></i></div>
          <h3><?= htmlspecialchars($pkg['nom']) ?></h3>
          <?php if (!empty($pkg['nom_ar'])): ?>
          <div style="font-family:'Amiri',serif;font-size:.9rem;color:var(--gold);opacity:.7;margin-top:4px"><?= htmlspecialchars($pkg['nom_ar']) ?></div>
          <?php endif; ?>
          <!-- ✅ PAS DE PRIX AFFICHÉ — à la place : badge devis -->
          <div class="pkg-devis-badge">
            <i class="fas fa-file-invoice"></i>
            <span data-fr="Sur devis personnalisé" data-ar="بعرض أسعار مخصص">Sur devis personnalisé</span>
          </div>
          <div class="pkg-guests">
            <i class="fas fa-users"></i>
            <span data-fr="<?= $pkg['min_personnes'] ?>–<?= $pkg['max_personnes'] ?> invités · <?= $pkg['duree_heures'] ?>h"
                  data-ar="<?= $pkg['min_personnes'] ?>-<?= $pkg['max_personnes'] ?> ضيف · <?= $pkg['duree_heures'] ?> ساعات">
              <?= $pkg['min_personnes'] ?>–<?= $pkg['max_personnes'] ?> invités · <?= $pkg['duree_heures'] ?>h
            </span>
          </div>
        </div>
        <ul class="pkg-list">
          <?php
          $hasAr = !empty($contenu_ar);
          $count = max(count($contenu), $hasAr ? count($contenu_ar) : 0);
          for ($i = 0; $i < $count; $i++):
            $fr = $contenu[$i]    ?? '';
            $ar = $hasAr ? ($contenu_ar[$i] ?? $fr) : $fr;
          ?>
          <li><i class="fas fa-check"></i>
            <span data-fr="<?= htmlspecialchars($fr) ?>"
                  data-ar="<?= htmlspecialchars($ar) ?>"><?= htmlspecialchars($fr) ?></span>
          </li>
          <?php endfor; ?>
        </ul>
        <a href="reservation.php" class="btn-pkg-full <?= $featured ? 'gold' : '' ?>"
           data-fr="Demander un devis" data-ar="طلب عرض أسعار">
          Demander un devis <i class="fas fa-arrow-right" style="margin-left:5px"></i>
        </a>
      </div>
      <?php endforeach; ?>
    </div>

    <?php else: ?>
    <!-- Packages statiques si BDD vide -->
    <div class="pkg-cards-full">
      <?php
      $staticPkgs = [
        ['bronze','fa-medal','Bronze','برونزية','50–100 invités · 5h',['Restauration de base','Thé & pâtisseries marocaines','Décoration simple','1 serveur professionnel','Tables & chaises incluses']],
        ['argent','fa-gem','Argent','فضية','80–150 invités · 7h',['Restauration complète','Buffet marocain & international','Décoration florale','3 serveurs professionnels','Gâteau inclus','Sonorisation de base']],
        ['or featured','fa-crown','Or','ذهبية','120–250 invités · 9h',['Repas gastronomique premium','Buffet complet 20 plats','Décoration florale premium','5 serveurs en tenue','Gâteau personnalisé','DJ + son/lumière','Photographe professionnel']],
        ['platine','fa-star','Platine','بلاتينية','200–500 invités · 12h',['Tout en Formule Or','Tente de réception 500 places','Limousine décorée + cortège','Photographe + Vidéaste HD','Animateur live','Invitations imprimées (500)','Coordinateur dédié']],
      ];
      foreach ($staticPkgs as $p): [$cls,$icon,$nom,$nomAr,$guests,$items] = $p;
      $isFeat = str_contains($cls, 'featured');
      ?>
      <div class="pkg-card-full <?= $cls ?>" data-aos="fade-up">
        <div class="pkg-top">
          <?php if ($isFeat): ?><div class="recommended-badge">⭐ RECOMMANDÉ</div><?php endif; ?>
          <div class="pkg-medal"><i class="fas <?= $icon ?>"></i></div>
          <h3><?= $nom ?></h3>
          <div style="font-family:'Amiri',serif;font-size:.9rem;color:var(--gold);opacity:.7;margin-top:4px"><?= $nomAr ?></div>
          <div class="pkg-devis-badge"><i class="fas fa-file-invoice"></i> Sur devis personnalisé</div>
          <div class="pkg-guests"><i class="fas fa-users"></i> <?= $guests ?></div>
        </div>
        <ul class="pkg-list">
          <?php foreach ($items as $it): ?><li><i class="fas fa-check"></i> <span><?= $it ?></span></li><?php endforeach; ?>
        </ul>
        <a href="reservation.php" class="btn-pkg-full <?= $isFeat ? 'gold' : '' ?>">Demander un devis <i class="fas fa-arrow-right" style="margin-left:5px"></i></a>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- CTA devis -->
    <div data-aos="fade-up" style="text-align:center;margin-bottom:60px">
      <div style="background:linear-gradient(135deg,rgba(212,175,55,.08),rgba(212,175,55,.02));border:1px solid rgba(212,175,55,.2);border-radius:16px;padding:40px;max-width:600px;margin:0 auto">
        <i class="fas fa-calculator" style="font-size:2.5rem;color:var(--gold);display:block;margin-bottom:14px"></i>
        <h3 style="color:var(--white);font-size:1.4rem;margin-bottom:10px" data-fr="Quel est le prix pour votre événement ?" data-ar="ما هو السعر لمناسبتك؟">Quel est le prix pour votre événement ?</h3>
        <p style="color:var(--text-muted);font-size:.85rem;margin-bottom:20px"
           data-fr="En quelques étapes, configurez votre événement et obtenez un devis détaillé instantanément, avec le coût exact de chaque service."
           data-ar="في بضع خطوات، قم بتكوين مناسبتك واحصل على عرض أسعار مفصل فوراً مع التكلفة الدقيقة لكل خدمة.">
          En quelques étapes, configurez votre événement et obtenez un devis détaillé instantanément, avec le coût exact de chaque service.
        </p>
        <a href="reservation.php" class="btn-primary large" style="text-decoration:none;display:inline-flex;align-items:center;gap:8px;padding:14px 32px;font-size:.95rem">
          <i class="fas fa-file-invoice"></i>
          <span data-fr="Obtenir mon devis gratuit" data-ar="احصل على عرض أسعاري المجاني">Obtenir mon devis gratuit</span>
        </a>
        <div style="margin-top:14px;font-size:.75rem;color:#555" data-fr="✓ Gratuit · ✓ Immédiat · ✓ Sans engagement" data-ar="✓ مجاني · ✓ فوري · ✓ بدون التزام">✓ Gratuit · ✓ Immédiat · ✓ Sans engagement</div>
      </div>
    </div>

    <!-- Tableau comparatif -->
    <div data-aos="fade-up">
      <h3 style="font-family:var(--ff-display);font-size:1.8rem;color:var(--white);text-align:center;margin-bottom:28px"
          data-fr="Tableau comparatif des formules" data-ar="جدول مقارنة الباقات">Tableau comparatif des formules</h3>
      <div style="overflow-x:auto">
        <table class="compare-table">
          <thead>
            <tr>
              <th data-fr="Inclus dans la formule" data-ar="المشمول في الباقة">Inclus dans la formule</th>
              <th data-fr="🥉 Bronze" data-ar="🥉 برونز">🥉 Bronze</th>
              <th data-fr="🥈 Argent" data-ar="🥈 فضي">🥈 Argent</th>
              <th data-fr="🥇 Or" data-ar="🥇 ذهبي">🥇 Or</th>
              <th data-fr="✨ Platine" data-ar="✨ بلاتين">✨ Platine</th>
            </tr>
          </thead>
          <tbody>
            <?php
            $compareRows = [
              ['Restauration de base',       'تموين أساسي',              true,  true,  true,  true],
              ['Buffet marocain complet',    'بوفيه مغربي كامل',          false, true,  true,  true],
              ['Décoration florale',         'زينة زهرية',                false, true,  true,  true],
              ['Animation musicale / DJ',    'موسيقى / DJ',               false, false, true,  true],
              ['Photographe professionnel',  'مصور فوتوغرافي محترف',      false, false, true,  true],
              ['Tente de réception',         'خيمة الاستقبال',            false, false, false, true],
              ['Vidéaste HD',                'مصور فيديو HD',              false, false, false, true],
              ['Coordinateur dédié',         'منسق مخصص',                 false, false, false, true],
              ['Limousine',                  'ليموزين مزينة',             false, false, false, true],
            ];
            foreach ($compareRows as $row):
              [$label, $labelAr, $b, $ar, $or, $pl] = $row;
              $cells = [$b, $ar, $or, $pl];
            ?>
            <tr>
              <td data-fr="<?= htmlspecialchars($label) ?>" data-ar="<?= htmlspecialchars($labelAr) ?>"><?= htmlspecialchars($label) ?></td>
              <?php foreach ($cells as $ok): ?>
              <td><i class="fas <?= $ok ? 'fa-check' : 'fa-times' ?>"></i></td>
              <?php endforeach; ?>
            </tr>
            <?php endforeach; ?>
            <tr style="font-weight:700;border-top:2px solid var(--border)">
              <td data-fr="Tarification" data-ar="التسعير" style="color:var(--gold)">Tarification</td>
              <td colspan="4" style="color:var(--text-muted);font-weight:400;font-size:.8rem" data-fr="Sur devis personnalisé selon vos besoins — gratuit & sans engagement" data-ar="بعرض أسعار مخصص حسب احتياجاتك — مجاني وبدون التزام">
                Sur devis personnalisé selon vos besoins — gratuit & sans engagement
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

  </div>
</section>

<section class="section-cta">
  <div class="cta-bg"></div>
  <div class="container">
    <div class="cta-content" data-aos="zoom-in">
      <h2 data-fr="Prêt à organiser votre<br>événement de rêve ?" data-ar="مستعد لتنظيم<br>حدثك المثالي؟" data-html>Prêt à organiser votre<br>événement de rêve ?</h2>
      <div class="cta-actions">
        <a href="reservation.php" class="btn-primary large" data-fr="Demander un devis gratuit" data-ar="طلب عرض أسعار مجاني" data-html>
          <i class="fas fa-file-invoice"></i> Demander un devis gratuit
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
