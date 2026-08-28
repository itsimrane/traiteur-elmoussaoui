<?php
require_once __DIR__ . '/../includes/config.php';

try {
    $articles = $pdo->query("
        SELECT a.*, c.nom as categorie_nom
        FROM blog_articles a
        LEFT JOIN categories_blog c ON a.categorie_id = c.id
        WHERE a.statut = 'publie'
        ORDER BY a.created_at DESC
    ")->fetchAll();
} catch(Exception $e) { $articles = []; }

try {
    $categories = $pdo->query("SELECT * FROM categories_blog ORDER BY nom")->fetchAll();
} catch(Exception $e) { $categories = []; }

$cat_filter = $_GET['categorie'] ?? '';
if ($cat_filter) {
    $articles = array_values(array_filter($articles, fn($a) => $a['categorie_nom'] === $cat_filter));
}

$featured = !empty($articles) ? array_shift($articles) : null;
$img_base = '../assets/uploads/';
?>
<!DOCTYPE html>
<html lang="fr" dir="ltr">
<head>
  <meta charset="UTF-8">
  <link rel="icon" type="image/png" href="../assets/img/favicon-32.png">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Blog & Actualités — Traiteur EL MOUSSAOUI</title>
  <meta name="description" content="Conseils, tendances et inspirations pour vos événements à Errachidia — Traiteur EL MOUSSAOUI.">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,600;0,700;1,400&family=Jost:wght@300;400;500;600&family=Amiri:wght@400;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.css">
  <link rel="stylesheet" href="../css/style.css">
  <style>
    .cat-filters{display:flex;gap:10px;flex-wrap:wrap;justify-content:center;margin:40px 0}
    .cat-btn{padding:7px 18px;border-radius:30px;border:1px solid var(--border);background:none;color:var(--text-muted);cursor:pointer;font-family:var(--ff-body);font-size:.82rem;transition:var(--transition);text-decoration:none}
    .cat-btn:hover,.cat-btn.active{border-color:var(--gold);color:var(--gold);background:rgba(212,175,55,.08)}

    .blog-layout{display:grid;grid-template-columns:1fr 300px;gap:32px}

    /* Article vedette */
    .blog-featured{background:var(--dark-card);border:1px solid rgba(212,175,55,.2);border-radius:var(--radius-lg);overflow:hidden;margin-bottom:32px;display:grid;grid-template-columns:1fr 1fr;transition:var(--transition)}
    .blog-featured:hover{border-color:rgba(212,175,55,.4);box-shadow:0 20px 50px rgba(0,0,0,.3)}
    .blog-featured-img{width:100%;height:100%;min-height:280px;object-fit:cover}
    .blog-featured-img-ph{min-height:280px;background:linear-gradient(135deg,var(--dark-3),#1a1a2e);display:flex;align-items:center;justify-content:center;font-size:4rem;color:rgba(212,175,55,.15)}
    .blog-featured-body{padding:36px;display:flex;flex-direction:column;justify-content:center}
    .featured-label{display:inline-flex;align-items:center;gap:6px;color:var(--gold);font-size:.7rem;letter-spacing:2px;text-transform:uppercase;margin-bottom:14px}
    .blog-featured-body h2{font-size:1.45rem;color:var(--white);margin-bottom:12px;line-height:1.3;font-family:var(--ff-display)}
    .blog-featured-body p{color:var(--text-muted);line-height:1.7;margin-bottom:18px;font-size:.85rem}

    /* Grille articles */
    .blog-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:22px;margin-bottom:40px}
    .blog-card{background:var(--dark-card);border:1px solid var(--border);border-radius:var(--radius);overflow:hidden;transition:var(--transition);display:flex;flex-direction:column}
    .blog-card:hover{border-color:rgba(212,175,55,.35);transform:translateY(-4px);box-shadow:0 16px 40px rgba(0,0,0,.3)}
    .blog-card-img{width:100%;height:190px;object-fit:cover}
    .blog-card-img-ph{width:100%;height:190px;background:linear-gradient(135deg,var(--dark-3),#1a1a2e);display:flex;align-items:center;justify-content:center;font-size:2.5rem;color:rgba(212,175,55,.18)}
    .blog-card-body{padding:18px;flex:1;display:flex;flex-direction:column}
    .blog-meta{display:flex;align-items:center;gap:8px;margin-bottom:8px;flex-wrap:wrap}
    .blog-cat{background:rgba(212,175,55,.1);color:var(--gold);padding:2px 9px;border-radius:20px;font-size:.68rem;font-weight:600}
    .blog-date{font-size:.7rem;color:#555}
    .blog-card h3{font-size:.95rem;color:var(--white);margin-bottom:7px;line-height:1.4;font-family:var(--ff-display)}
    .blog-excerpt{font-size:.8rem;color:var(--text-muted);line-height:1.7;flex:1}

    /* Sidebar */
    .sidebar-widget{background:var(--dark-card);border:1px solid var(--border);border-radius:var(--radius);padding:22px;margin-bottom:18px}
    .widget-title{font-size:.72rem;font-weight:700;color:var(--white);text-transform:uppercase;letter-spacing:1px;margin-bottom:14px;padding-bottom:10px;border-bottom:1px solid var(--border)}
    .cta-widget{background:linear-gradient(135deg,rgba(212,175,55,.1),rgba(212,175,55,.04));border-color:rgba(212,175,55,.25);text-align:center}
    .cta-widget i{font-size:2rem;color:var(--gold);margin-bottom:10px;display:block}
    .cta-widget h4{color:var(--white);margin-bottom:8px;font-size:.9rem}
    .cta-widget p{color:var(--text-muted);font-size:.78rem;margin-bottom:14px;line-height:1.6}

    /* État vide */
    .blog-empty{text-align:center;padding:80px 20px;color:var(--text-muted)}
    .blog-empty i{font-size:3rem;opacity:.12;display:block;margin-bottom:18px}
    .blog-empty h3{color:var(--white);margin-bottom:8px}

    @media(max-width:900px){.blog-layout{grid-template-columns:1fr}.blog-featured{grid-template-columns:1fr}}
    @media(max-width:600px){.blog-grid{grid-template-columns:1fr}}
  </style>
</head>
<body>
<div id="loader"><div class="loader-inner"><div class="loader-ring"></div><div class="loader-logo"><span class="loader-em">EL</span><span class="loader-moussaoui">MOUSSAOUI</span></div></div></div>

<?php $navActive = 'blog'; include_once __DIR__ . '/../includes/navbar.php'; ?>

<div class="page-hero">
  <div class="container">
    <span class="section-tag" data-aos="fade-down" data-fr="Nos actualités" data-ar="أخبارنا">Nos actualités</span>
    <h1 data-aos="fade-up" data-fr="Blog & Inspirations" data-ar="مدونة وإلهام">Blog & Inspirations</h1>
    <p data-aos="fade-up" data-aos-delay="150"
       data-fr="Conseils, tendances et idées pour organiser l'événement de vos rêves à Errachidia."
       data-ar="نصائح واتجاهات وأفكار لتنظيم حفل أحلامك بالراشيدية.">
      Conseils, tendances et idées pour organiser l'événement de vos rêves à Errachidia.
    </p>
  </div>
</div>

<section class="section">
  <div class="container">

    <?php if (!empty($categories)): ?>
    <div class="cat-filters">
      <a href="blog.php" class="cat-btn <?= !$cat_filter ? 'active' : '' ?>" data-fr="Tous" data-ar="الكل">Tous</a>
      <?php foreach ($categories as $cat): ?>
      <a href="blog.php?categorie=<?= urlencode($cat['nom']) ?>" class="cat-btn <?= $cat_filter===$cat['nom']?'active':'' ?>">
        <?= htmlspecialchars($cat['nom']) ?>
      </a>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php if (!$featured && empty($articles)): ?>
    <!-- État vide -->
    <div class="blog-empty" data-aos="fade-up">
      <i class="fas fa-newspaper"></i>
      <h3 data-fr="Articles bientôt disponibles" data-ar="المقالات قريبًا">Articles bientôt disponibles</h3>
      <p data-fr="Nos premiers conseils et inspirations pour vos événements arrivent prochainement." data-ar="نصائحنا الأولى وإلهاماتنا لمناسباتكم قادمة قريبًا.">
        Nos premiers conseils et inspirations arrivent prochainement.
      </p>
      <a href="reservation.php" class="btn-primary" style="display:inline-flex;align-items:center;gap:8px;margin-top:24px;text-decoration:none">
        <i class="fas fa-file-invoice"></i>
        <span data-fr="Demander un devis" data-ar="طلب عرض أسعار">Demander un devis</span>
      </a>
    </div>

    <?php else: ?>
    <div class="blog-layout">
      <div>
        <?php if ($featured): ?>
        <!-- Article à la une -->
        <div class="blog-featured" data-aos="fade-up">
          <?php if (!empty($featured['image'])): ?>
          <img src="<?= $img_base . htmlspecialchars($featured['image']) ?>" alt="<?= htmlspecialchars($featured['titre']) ?>" class="blog-featured-img">
          <?php else: ?>
          <div class="blog-featured-img-ph"><i class="fas fa-utensils"></i></div>
          <?php endif; ?>
          <div class="blog-featured-body">
            <div class="featured-label"><i class="fas fa-star"></i> <span data-fr="À la une" data-ar="في الصدارة">À la une</span></div>
            <?php if (!empty($featured['categorie_nom'])): ?>
            <span class="blog-cat" style="display:inline-block;margin-bottom:10px"><?= htmlspecialchars($featured['categorie_nom']) ?></span>
            <?php endif; ?>
            <h2><?= htmlspecialchars($featured['titre']) ?></h2>
            <p><?= htmlspecialchars(mb_substr($featured['resume'] ?? $featured['contenu'] ?? '', 0, 160)) ?>...</p>
            <div class="blog-date" style="margin-bottom:18px">
              <i class="fas fa-calendar-alt" style="color:var(--gold);margin-right:4px"></i>
              <?= date('d M Y', strtotime($featured['created_at'])) ?>
            </div>
            <a href="reservation.php" class="btn-primary" style="display:inline-flex;align-items:center;gap:8px;text-decoration:none;font-size:.85rem;padding:10px 22px">
              <span data-fr="Nous contacter" data-ar="اتصل بنا">Nous contacter</span>
              <i class="fas fa-arrow-right"></i>
            </a>
          </div>
        </div>
        <?php endif; ?>

        <?php if (!empty($articles)): ?>
        <div class="blog-grid">
          <?php foreach ($articles as $idx => $art): ?>
          <article class="blog-card" data-aos="fade-up" data-aos-delay="<?= ($idx % 3) * 60 ?>">
            <?php if (!empty($art['image'])): ?>
            <img src="<?= $img_base . htmlspecialchars($art['image']) ?>" alt="<?= htmlspecialchars($art['titre']) ?>" class="blog-card-img">
            <?php else: ?>
            <div class="blog-card-img-ph"><i class="fas fa-utensils"></i></div>
            <?php endif; ?>
            <div class="blog-card-body">
              <div class="blog-meta">
                <?php if (!empty($art['categorie_nom'])): ?>
                <span class="blog-cat"><?= htmlspecialchars($art['categorie_nom']) ?></span>
                <?php endif; ?>
                <span class="blog-date"><?= date('d M Y', strtotime($art['created_at'])) ?></span>
              </div>
              <h3><?= htmlspecialchars($art['titre']) ?></h3>
              <p class="blog-excerpt"><?= htmlspecialchars(mb_substr($art['resume'] ?? $art['contenu'] ?? '', 0, 150)) ?></p>
            </div>
          </article>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>

      <!-- Sidebar -->
      <aside>
        <div class="sidebar-widget cta-widget">
          <i class="fas fa-calendar-check"></i>
          <h4 data-fr="Organiser votre événement ?" data-ar="هل تريد تنظيم مناسبتك؟">Organiser votre événement ?</h4>
          <p data-fr="Devis gratuit et personnalisé en 24h." data-ar="عرض أسعار مجاني وشخصي في 24 ساعة.">Devis gratuit et personnalisé en 24h.</p>
          <a href="reservation.php" class="btn-primary" style="display:block;text-align:center;text-decoration:none;padding:10px">
            <span data-fr="Demander un devis" data-ar="طلب عرض أسعار">Demander un devis</span>
          </a>
        </div>

        <?php if (!empty($categories)): ?>
        <div class="sidebar-widget">
          <div class="widget-title" data-fr="Catégories" data-ar="التصنيفات">Catégories</div>
          <?php foreach ($categories as $cat): ?>
          <a href="blog.php?categorie=<?= urlencode($cat['nom']) ?>"
             style="display:flex;align-items:center;justify-content:space-between;padding:8px 0;border-bottom:1px solid rgba(255,255,255,.04);text-decoration:none;color:var(--text-muted);font-size:.82rem;transition:var(--transition)"
             onmouseover="this.style.color='var(--gold)'" onmouseout="this.style.color='var(--text-muted)'">
            <?= htmlspecialchars($cat['nom']) ?>
            <i class="fas fa-chevron-right" style="font-size:.6rem;color:#555"></i>
          </a>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <div class="sidebar-widget">
          <div class="widget-title" data-fr="Contact rapide" data-ar="تواصل سريع">Contact rapide</div>
          <div style="font-size:.82rem;color:var(--text-muted);line-height:2.2">
            <div>
              <i class="fas fa-phone" style="color:var(--gold);width:18px"></i>
              <a href="tel:0626986533" style="color:var(--text-muted);text-decoration:none" dir="ltr">0626 986 533</a>
            </div>
            <div>
              <i class="fab fa-whatsapp" style="color:#25D366;width:18px"></i>
              <a href="https://wa.me/212626986533" target="_blank" style="color:var(--text-muted);text-decoration:none">WhatsApp</a>
            </div>
            <div>
              <i class="fas fa-map-marker-alt" style="color:var(--gold);width:18px"></i>
              <span data-fr="Errachidia, Maroc" data-ar="الراشيدية، المغرب">Errachidia, Maroc</span>
            </div>
          </div>
        </div>
      </aside>
    </div>
    <?php endif; ?>

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
<?php include_once __DIR__ . '/../includes/admin-bar.php'; ?>
</body>
</html>
