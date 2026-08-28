<?php
require_once __DIR__ . '/../includes/config.php';

$editMode = isset($_GET['edit']) && isAdmin();

$categories = $pdo->query("
    SELECT c.*, COUNT(g.id) AS nb
    FROM categories_galerie c
    LEFT JOIN galerie g ON g.categorie_id = c.id AND g.actif = 1
    GROUP BY c.id HAVING nb > 0
    ORDER BY c.ordre
")->fetchAll();

$medias = $pdo->query("
    SELECT g.*, c.nom AS cat_nom, c.nom_ar AS cat_nom_ar, c.slug AS cat_slug
    FROM galerie g
    LEFT JOIN categories_galerie c ON g.categorie_id = c.id
    WHERE g.actif = 1
    ORDER BY g.en_vedette DESC, g.ordre ASC, g.created_at DESC
")->fetchAll();

$photos = array_values(array_filter($medias, fn($m) => $m['type'] === 'photo'));
$videos = array_values(array_filter($medias, fn($m) => $m['type'] === 'video'));
$lbData = array_map(fn($m) => [
    'src'   => $m['fichier'] ? UPLOAD_URL . '/' . $m['fichier'] : null,
    'titre' => $m['titre'] ?? '',
    'cat'   => $m['cat_nom'] ?? '',
], $photos);

$allCats = $pdo->query("SELECT * FROM categories_galerie WHERE actif=1 ORDER BY ordre")->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr" dir="ltr">
<head>
  <meta charset="UTF-8">
  <link rel="icon" type="image/png" href="../assets/img/favicon-32.png">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Galerie — Traiteur EL MOUSSAOUI | Errachidia</title>
  <meta name="description" content="Galerie photos et vidéos des événements organisés par Traiteur EL MOUSSAOUI à Errachidia.">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,600;0,700;1,400&family=Jost:wght@300;400;500;600&family=Amiri:wght@400;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.css">
  <link rel="stylesheet" href="../css/style.css">
  <style>
    .gallery-filters{display:flex;gap:10px;flex-wrap:wrap;justify-content:center;margin-bottom:48px}
    .filter-btn{padding:9px 22px;border-radius:30px;border:1.5px solid var(--border);background:none;color:var(--text-muted);font-family:var(--ff-body);font-size:.82rem;letter-spacing:.5px;cursor:pointer;transition:var(--transition)}
    .filter-btn:hover{border-color:var(--gold);color:var(--gold)}
    .filter-btn.active{background:linear-gradient(135deg,var(--gold),var(--gold-dark));color:var(--dark);border-color:transparent;font-weight:600}
    .masonry-grid{columns:4;column-gap:14px}
    .masonry-item{break-inside:avoid;margin-bottom:14px;border-radius:12px;overflow:hidden;position:relative;cursor:pointer;transition:var(--transition)}
    .masonry-item:hover{transform:scale(1.02);box-shadow:0 12px 40px rgba(0,0,0,.5)}
    .masonry-item img{width:100%;display:block;object-fit:cover}
    .masonry-placeholder{width:100%;background:var(--dark-3);display:flex;align-items:center;justify-content:center;flex-direction:column;gap:10px;padding:60px 20px}
    .masonry-placeholder i{font-size:2.5rem;color:rgba(212,175,55,.3)}
    .masonry-overlay{position:absolute;inset:0;background:linear-gradient(to top,rgba(0,0,0,.85) 0%,transparent 50%);opacity:0;transition:var(--transition);display:flex;align-items:flex-end;padding:16px}
    .masonry-item:hover .masonry-overlay{opacity:1}
    .masonry-overlay-content{flex:1}
    .masonry-overlay-content span{display:block;font-family:var(--ff-display);color:var(--gold);font-size:1.05rem;font-style:italic;margin-bottom:4px}
    .masonry-overlay-content small{font-size:.72rem;color:var(--text-muted)}
    .masonry-zoom{width:34px;height:34px;border-radius:8px;background:rgba(212,175,55,.2);border:1px solid var(--gold);display:flex;align-items:center;justify-content:center;color:var(--gold);font-size:.85rem;flex-shrink:0}
    .masonry-item.hidden{display:none}
    .lightbox{display:none;position:fixed;inset:0;z-index:3000;background:rgba(0,0,0,.95);align-items:center;justify-content:center;padding:20px}
    .lightbox.open{display:flex}
    .lightbox-inner{position:relative;max-width:900px;width:100%;display:flex;flex-direction:column;align-items:center}
    .lightbox-img{max-width:100%;max-height:75vh;border-radius:12px;object-fit:contain}
    .lightbox-video{width:100%;max-width:800px;border-radius:12px}
    .lightbox-caption{margin-top:16px;text-align:center}
    .lightbox-caption strong{font-family:var(--ff-display);font-size:1.4rem;color:var(--white);display:block}
    .lightbox-caption small{font-size:.8rem;color:var(--text-muted)}
    .lightbox-close{position:absolute;top:-50px;right:0;width:40px;height:40px;border-radius:10px;background:rgba(255,255,255,.1);border:1px solid var(--border);color:var(--white);font-size:1rem;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:var(--transition)}
    .lightbox-close:hover{background:var(--gold);color:var(--dark)}
    .lightbox-prev,.lightbox-next{position:fixed;top:50%;transform:translateY(-50%);width:48px;height:48px;border-radius:50%;background:rgba(212,175,55,.15);border:1px solid var(--border);color:var(--gold);font-size:1rem;cursor:pointer;transition:var(--transition);display:flex;align-items:center;justify-content:center}
    .lightbox-prev{left:20px}.lightbox-next{right:20px}
    .lightbox-prev:hover,.lightbox-next:hover{background:var(--gold);color:var(--dark)}
    .lightbox-counter{position:fixed;top:20px;left:50%;transform:translateX(-50%);background:rgba(0,0,0,.6);border:1px solid var(--border);border-radius:20px;padding:6px 18px;font-size:.8rem;color:var(--text-muted)}
    .videos-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:20px}
    .video-card{border-radius:var(--radius);overflow:hidden;border:1px solid var(--border);background:var(--dark-card);transition:var(--transition)}
    .video-card:hover{border-color:var(--gold);transform:translateY(-4px)}
    .video-thumb{aspect-ratio:16/9;background:var(--dark-3);display:flex;align-items:center;justify-content:center;position:relative;overflow:hidden;cursor:pointer}
    .video-thumb img{width:100%;height:100%;object-fit:cover;position:absolute;inset:0}
    .video-play-overlay{position:absolute;inset:0;display:flex;align-items:center;justify-content:center;background:rgba(0,0,0,.35)}
    .video-play-btn{width:56px;height:56px;border-radius:50%;background:linear-gradient(135deg,var(--gold),var(--gold-dark));display:flex;align-items:center;justify-content:center;color:var(--dark);font-size:1.2rem;transition:var(--transition);position:relative;z-index:1}
    .video-thumb:hover .video-play-btn{transform:scale(1.15)}
    .video-card-body{padding:16px}
    .video-card-body h4{font-size:.95rem;color:var(--white);margin-bottom:6px}
    .video-card-body p{font-size:.8rem;color:var(--text-muted)}
    .gallery-stats{display:grid;grid-template-columns:repeat(4,1fr);gap:0;border:1px solid var(--border);border-radius:var(--radius-lg);overflow:hidden;margin-bottom:60px}
    .gallery-stat{padding:28px;text-align:center;border-right:1px solid var(--border)}
    .gallery-stat:last-child{border-right:none}
    .gallery-stat .num{font-family:var(--ff-display);font-size:2.5rem;color:var(--gold);font-weight:700}
    .gallery-stat .lbl{font-size:.72rem;letter-spacing:1px;text-transform:uppercase;color:var(--text-muted);margin-top:4px}
    .empty-gal{text-align:center;padding:80px 20px;color:var(--text-muted)}
    .empty-gal i{font-size:3rem;margin-bottom:16px;opacity:.3;display:block}
    @media(max-width:1100px){.masonry-grid{columns:3}.videos-grid{grid-template-columns:repeat(2,1fr)}.gallery-stats{grid-template-columns:repeat(2,1fr)}}
    @media(max-width:768px){.masonry-grid{columns:2}.videos-grid{grid-template-columns:1fr}}
    @media(max-width:480px){.masonry-grid{columns:1}}
  </style>
</head>
<body>

<?php if ($editMode): ?>
<!-- ═══ BARRE ADMIN MODE ÉDITION ══════════════════════════════════ -->
<div id="adminEditBar" style="
  position:fixed;top:0;left:0;right:0;z-index:99999;
  background:rgba(10,10,15,.97);backdrop-filter:blur(12px);
  border-bottom:2px solid #D4AF37;
  display:flex;align-items:center;justify-content:space-between;
  padding:0 24px;height:52px;font-family:'Jost',sans-serif;
">
  <div style="display:flex;align-items:center;gap:16px">
    <span style="font-size:.7rem;letter-spacing:3px;color:#666">ADMIN</span>
    <span style="font-family:'Cormorant Garamond',serif;font-size:1.1rem;font-weight:700;color:#D4AF37">MODE ÉDITION GALERIE</span>
    <span style="background:rgba(37,211,102,.15);color:#25D366;padding:3px 10px;border-radius:12px;font-size:.68rem;font-weight:700">
      ● ACTIF — Cliquez sur une photo pour la remplacer
    </span>
  </div>
  <div style="display:flex;align-items:center;gap:10px">
    <button onclick="document.getElementById('uploadPanel').classList.toggle('show')"
            style="background:rgba(212,175,55,.15);border:1px solid rgba(212,175,55,.3);color:#D4AF37;padding:7px 16px;border-radius:8px;font-size:.78rem;font-weight:700;cursor:pointer;font-family:inherit">
      <i class="fas fa-plus"></i> Ajouter une photo
    </button>
    <a href="../admin/dashboard.php"
       style="background:rgba(212,175,55,.1);border:1px solid rgba(212,175,55,.2);color:#D4AF37;padding:7px 14px;border-radius:8px;font-size:.78rem;text-decoration:none;font-weight:600">
      <i class="fas fa-tachometer-alt"></i> Dashboard
    </a>
    <a href="galerie.php"
       style="background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.2);color:#EF5350;padding:7px 14px;border-radius:8px;font-size:.78rem;text-decoration:none;font-weight:600">
      <i class="fas fa-times"></i> Quitter l'édition
    </a>
  </div>
</div>

<!-- Panneau upload -->
<div id="uploadPanel" style="
  position:fixed;top:52px;left:0;right:0;z-index:99998;
  background:rgba(13,13,20,.98);border-bottom:1px solid rgba(212,175,55,.3);
  padding:20px 24px;display:none;
">
  <div style="max-width:900px;margin:0 auto">
    <h3 style="color:#FFF;font-size:.9rem;margin-bottom:16px;font-family:'Jost',sans-serif">
      <i class="fas fa-cloud-upload-alt" style="color:#D4AF37;margin-right:8px"></i>
      Ajouter une photo à la galerie
    </h3>
    <form id="quickUploadForm" enctype="multipart/form-data"
          style="display:grid;grid-template-columns:2fr 1fr 1fr auto;gap:12px;align-items:end">
      <div>
        <label style="display:block;font-size:.68rem;color:#888;text-transform:uppercase;letter-spacing:.5px;margin-bottom:5px">Titre</label>
        <input type="text" name="titre" placeholder="Ex : Mariage 2025" required
               style="background:#111;border:1px solid #2A2A3E;border-radius:8px;padding:9px 13px;color:#FFF;font-size:.85rem;outline:none;width:100%;font-family:'Jost',sans-serif">
      </div>
      <div>
        <label style="display:block;font-size:.68rem;color:#888;text-transform:uppercase;letter-spacing:.5px;margin-bottom:5px">Catégorie</label>
        <select name="categorie_id"
                style="background:#111;border:1px solid #2A2A3E;border-radius:8px;padding:9px 13px;color:#FFF;font-size:.85rem;outline:none;width:100%;font-family:'Jost',sans-serif">
          <?php foreach ($allCats as $cat): ?>
          <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['nom']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label style="display:block;font-size:.68rem;color:#888;text-transform:uppercase;letter-spacing:.5px;margin-bottom:5px">Photo (JPG/PNG/WEBP max 5Mo)</label>
        <input type="file" name="fichier" accept="image/*" required id="quickFile"
               style="background:#111;border:1px solid #2A2A3E;border-radius:8px;padding:7px 13px;color:#888;font-size:.78rem;width:100%">
      </div>
      <div>
        <input type="hidden" name="type" value="photo">
        <button type="submit" id="quickSubmitBtn"
                style="background:linear-gradient(135deg,#D4AF37,#B8860B);color:#0D0D0D;border:none;border-radius:8px;padding:10px 20px;font-weight:700;cursor:pointer;font-size:.85rem;font-family:'Jost',sans-serif;white-space:nowrap">
          <i class="fas fa-upload"></i> Uploader
        </button>
      </div>
    </form>
    <div id="quickProgress" style="display:none;margin-top:10px">
      <div style="background:#2A2A3E;border-radius:20px;height:6px;overflow:hidden">
        <div id="quickProgressFill" style="height:100%;background:linear-gradient(90deg,#D4AF37,#B8860B);width:0;transition:width .3s;border-radius:20px"></div>
      </div>
      <div id="quickProgressText" style="font-size:.72rem;color:#888;margin-top:4px;text-align:center">Envoi en cours...</div>
    </div>
    <div id="quickMsg" style="display:none;margin-top:10px;padding:8px 14px;border-radius:8px;font-size:.8rem"></div>
  </div>
</div>

<style>
#uploadPanel.show { display:block !important; }
/* Barre admin 52px + navbar fixe du site */
body { padding-top:52px !important; }
#header { top:52px !important; }

/* Overlays édition */
.masonry-item .edit-overlay {
  position:absolute;inset:0;background:rgba(0,0,0,.65);
  display:flex;align-items:center;justify-content:center;gap:8px;
  opacity:0;transition:.2s;border-radius:inherit;pointer-events:none;
}
.masonry-item:hover .edit-overlay { opacity:1;pointer-events:auto; }
.edit-btn { padding:8px 14px;border-radius:8px;font-size:.75rem;font-weight:700;cursor:pointer;border:none;font-family:'Jost',sans-serif;transition:.2s; }
.edit-btn.replace { background:#D4AF37;color:#0D0D0D; }
.edit-btn.replace:hover { background:#B8860B; }
.edit-btn.del { background:rgba(239,68,68,.2);color:#EF5350;border:1px solid rgba(239,68,68,.4); }
.edit-btn.del:hover { background:rgba(239,68,68,.4); }
/* Toast */
.admin-toast{position:fixed;bottom:24px;right:24px;z-index:999999;padding:12px 20px;border-radius:10px;font-family:'Jost',sans-serif;font-size:.85rem;font-weight:600;display:flex;align-items:center;gap:10px;transform:translateY(80px);opacity:0;transition:.3s;box-shadow:0 8px 32px rgba(0,0,0,.5)}
.admin-toast.show{transform:translateY(0);opacity:1}
.admin-toast.s{background:#1A2E1A;border:1px solid rgba(102,187,106,.4);color:#66BB6A}
.admin-toast.e{background:#2E1A1A;border:1px solid rgba(239,68,68,.4);color:#EF5350}
.admin-toast.l{background:#1A1A2E;border:1px solid rgba(212,175,55,.4);color:#D4AF37}
</style>
<?php endif; ?>

<div id="loader"><div class="loader-inner"><div class="loader-ring"></div><div class="loader-logo"><span class="loader-em">EL</span><span class="loader-moussaoui">MOUSSAOUI</span></div></div></div>


<?php $navActive = "galerie"; include_once __DIR__ . "/../includes/navbar.php"; ?>


<div class="page-hero">
  <div class="container">
    <span class="section-tag" data-aos="fade-down" data-fr="Nos réalisations" data-ar="أعمالنا">Nos réalisations</span>
    <h1 data-aos="fade-up" data-fr="Galerie d'Événements" data-ar="معرض المناسبات">Galerie d'Événements</h1>
    <p data-aos="fade-up" data-aos-delay="200"
       data-fr="Chaque photo raconte une histoire. Découvrez les moments magiques que nous avons créés pour nos clients à Errachidia et dans la région."
       data-ar="كل صورة تحكي قصة. اكتشفوا اللحظات الساحرة التي صنعناها لعملائنا في الراشيدية والمنطقة.">
      Chaque photo raconte une histoire. Découvrez les moments magiques que nous avons créés pour nos clients à Errachidia et dans la région.
    </p>
  </div>
</div>

<section class="section">
  <div class="container">

    <div class="gallery-stats" data-aos="fade-up">
      <div class="gallery-stat"><div class="num" dir="ltr">500+</div><div class="lbl" data-fr="Événements organisés" data-ar="مناسبة منظمة">Événements organisés</div></div>
      <div class="gallery-stat"><div class="num" dir="ltr"><?= count($photos) ?>+</div><div class="lbl" data-fr="Photos en galerie" data-ar="صورة في المعرض">Photos en galerie</div></div>
      <div class="gallery-stat"><div class="num" dir="ltr"><?= count($videos) ?></div><div class="lbl" data-fr="Vidéos" data-ar="فيديوهات">Vidéos</div></div>
      <div class="gallery-stat"><div class="num" dir="ltr">98%</div><div class="lbl" data-fr="Clients satisfaits" data-ar="عملاء راضون">Clients satisfaits</div></div>
    </div>

    <div class="gallery-filters" data-aos="fade-up">
      <button class="filter-btn active" data-filter="all" data-fr="Tous" data-ar="الكل">Tous</button>
      <?php foreach ($categories as $cat): ?>
      <button class="filter-btn" data-filter="<?= htmlspecialchars($cat['slug']) ?>"
              data-fr="<?= htmlspecialchars($cat['nom']) ?>"
              data-ar="<?= htmlspecialchars($cat['nom_ar'] ?? $cat['nom']) ?>">
        <?= htmlspecialchars($cat['nom']) ?>
      </button>
      <?php endforeach; ?>
    </div>

    <?php if (empty($photos)): ?>
    <div class="empty-gal">
      <i class="fas fa-images"></i>
      <p data-fr="Aucune photo disponible pour le moment." data-ar="لا توجد صور متاحة حالياً.">
        Aucune photo disponible pour le moment.
      </p>
    </div>
    <?php else: ?>
    <div class="masonry-grid" id="galleryGrid">
      <?php foreach ($photos as $idx => $m):
        $src    = $m['fichier'] ? UPLOAD_URL . '/' . $m['fichier'] : null;
        $titre  = htmlspecialchars($m['titre'] ?? 'Photo');
        $cat    = htmlspecialchars($m['cat_slug'] ?? 'divers');
        $catNom = htmlspecialchars($m['cat_nom'] ?? '');
        $alt    = htmlspecialchars($m['alt_text'] ?? $titre);
      ?>
      <div class="masonry-item"
           data-cat="<?= $cat ?>"
           data-id="<?= $m['id'] ?>"
           onclick="<?= $editMode ? 'null' : "openLightbox($idx)" ?>">
        <?php if ($src): ?>
          <img src="<?= $src ?>" alt="<?= $alt ?>" loading="lazy"
               data-zone="galerie" data-id="<?= $m['id'] ?>" data-titre="<?= $titre ?>">
        <?php else: ?>
          <div class="masonry-placeholder" style="aspect-ratio:4/3"><i class="fas fa-image"></i></div>
        <?php endif; ?>
        <?php if ($editMode): ?>
        <div class="edit-overlay" onclick="event.stopPropagation()">
          <button class="edit-btn replace" onclick="replacePhoto(<?= $m['id'] ?>, this)">
            <i class="fas fa-camera"></i> Remplacer
          </button>
          <button class="edit-btn del" onclick="deletePhoto(<?= $m['id'] ?>, this.closest('.masonry-item'))">
            <i class="fas fa-trash"></i>
          </button>
        </div>
        <?php else: ?>
        <div class="masonry-overlay">
          <div class="masonry-overlay-content"><span><?= $titre ?></span><small><?= $catNom ?></small></div>
          <div class="masonry-zoom"><i class="fas fa-search-plus"></i></div>
        </div>
        <?php endif; ?>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

  </div>
</section>

<?php if (!empty($videos)): ?>
<section class="section section-dark">
  <div class="container">
    <div class="section-header" data-aos="fade-up">
      <span class="section-tag light" data-fr="Reportages vidéo" data-ar="تقارير فيديو">Reportages vidéo</span>
      <h2 class="section-title light" data-fr="Nos Vidéos d'Événements" data-ar="فيديوهات مناسباتنا">Nos Vidéos d'Événements</h2>
    </div>
    <div class="videos-grid">
      <?php foreach ($videos as $v):
        $vidSrc   = $v['fichier'] ? UPLOAD_URL . '/' . $v['fichier'] : null;
        $thumbSrc = $v['miniature'] ? UPLOAD_URL . '/' . $v['miniature'] : null;
        $titre    = htmlspecialchars($v['titre'] ?? 'Vidéo');
        $catNom   = htmlspecialchars($v['cat_nom'] ?? '');
      ?>
      <div class="video-card" data-aos="fade-up">
        <div class="video-thumb" onclick="playVideo('<?= addslashes($vidSrc) ?>')">
          <?php if ($thumbSrc): ?><img src="<?= $thumbSrc ?>" alt="<?= $titre ?>"><?php endif; ?>
          <div class="video-play-overlay"><div class="video-play-btn"><i class="fas fa-play"></i></div></div>
        </div>
        <div class="video-card-body"><h4><?= $titre ?></h4><p><?= $catNom ?></p></div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<section class="section-cta">
  <div class="cta-bg"></div>
  <div class="container">
    <div class="cta-content" data-aos="zoom-in">
      <h2 data-fr="Votre événement mérite<br>les meilleures photos" data-ar="مناسبتكم تستحق<br>أفضل الصور" data-html>Votre événement mérite<br>les meilleures photos</h2>
      <div class="cta-actions">
        <a href="reservation.php" class="btn-primary large" data-fr="Réserver maintenant" data-ar="احجز الآن" data-html><i class="fas fa-calendar-check"></i> Réserver maintenant</a>
        <a href="https://wa.me/212626986533" target="_blank" class="btn-whatsapp large"><i class="fab fa-whatsapp"></i> <span dir="ltr">0626 986 533</span></a>
      </div>
    </div>
  </div>
</section>

<footer id="footer">
  <div class="footer-bottom"><div class="container">
    <p data-fr="© 2025 Traiteur EL MOUSSAOUI — Errachidia, Maroc" data-ar="© 2025 مطعم المساوي - الراشيدية، المغرب">© 2025 Traiteur EL MOUSSAOUI — Errachidia, Maroc</p>
    <p><a href="../index.php" data-fr="Accueil" data-ar="الرئيسية">Accueil</a></p>
  </div></div>
</footer>

<div class="lightbox" id="lightbox">
  <div class="lightbox-counter" id="lbCounter"></div>
  <div class="lightbox-inner">
    <button class="lightbox-close" onclick="closeLightbox()"><i class="fas fa-times"></i></button>
    <div id="lbContent"></div>
    <div class="lightbox-caption" id="lbCaption"></div>
  </div>
  <button class="lightbox-prev" onclick="lbNav(-1)"><i class="fas fa-chevron-left"></i></button>
  <button class="lightbox-next" onclick="lbNav(1)"><i class="fas fa-chevron-right"></i></button>
</div>

<div class="lightbox" id="videoModal">
  <div class="lightbox-inner">
    <button class="lightbox-close" onclick="closeVideo()"><i class="fas fa-times"></i></button>
    <video id="videoPlayer" class="lightbox-video" controls playsinline></video>
  </div>
</div>

<a href="https://wa.me/212626986533" class="whatsapp-float" target="_blank"><i class="fab fa-whatsapp"></i></a>
<button class="scroll-top" id="scrollTop"><i class="fas fa-chevron-up"></i></button>

<script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js"></script>
<script src="../js/main.js"></script>
<script src="../js/lang.js"></script>
<script>
// Filtres
document.querySelectorAll('.filter-btn').forEach(btn => {
  btn.addEventListener('click', () => {
    document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    const cat = btn.dataset.filter;
    document.querySelectorAll('.masonry-item').forEach(item => {
      item.classList.toggle('hidden', cat !== 'all' && item.dataset.cat !== cat);
    });
  });
});

// Lightbox
const lbData = <?= json_encode($lbData, JSON_UNESCAPED_UNICODE) ?>;
let lbIdx = 0;
function openLightbox(idx) { lbIdx=idx; renderLb(); document.getElementById('lightbox').classList.add('open'); document.body.style.overflow='hidden'; }
function renderLb() {
  const d=lbData[lbIdx]||{};
  document.getElementById('lbCounter').textContent=`${lbIdx+1} / ${lbData.length}`;
  document.getElementById('lbContent').innerHTML=d.src?`<img src="${d.src}" alt="${d.titre}" class="lightbox-img">`:`<div style="width:500px;max-width:90vw;aspect-ratio:4/3;background:#111;border-radius:12px;display:flex;align-items:center;justify-content:center"><i class="fas fa-image" style="font-size:4rem;color:#333"></i></div>`;
  document.getElementById('lbCaption').innerHTML=`<strong>${d.titre}</strong><small>${d.cat}</small>`;
}
function closeLightbox() { document.getElementById('lightbox').classList.remove('open'); document.body.style.overflow=''; }
function lbNav(dir) { lbIdx=(lbIdx+dir+lbData.length)%lbData.length; renderLb(); }
document.getElementById('lightbox').addEventListener('click',e=>{if(e.target===e.currentTarget)closeLightbox();});
document.addEventListener('keydown',e=>{if(!document.getElementById('lightbox').classList.contains('open'))return;if(e.key==='ArrowLeft')lbNav(-1);if(e.key==='ArrowRight')lbNav(1);if(e.key==='Escape')closeLightbox();});

// Vidéo
function playVideo(src){if(!src)return;const p=document.getElementById('videoPlayer');p.src=src;document.getElementById('videoModal').classList.add('open');document.body.style.overflow='hidden';p.play();}
function closeVideo(){const p=document.getElementById('videoPlayer');p.pause();p.src='';document.getElementById('videoModal').classList.remove('open');document.body.style.overflow='';}
document.getElementById('videoModal').addEventListener('click',e=>{if(e.target===e.currentTarget)closeVideo();});
</script>

<?php if ($editMode): ?>
<div id="adminToast" class="admin-toast"></div>
<script>
const UPLOAD_URL = '<?= SITE_URL ?>/api/upload_media.php';
const DELETE_URL = '<?= SITE_URL ?>/api/delete_media.php';

let toastTimer;
function toast(msg,type='s'){
  const el=document.getElementById('adminToast');
  el.className='admin-toast '+type;
  el.innerHTML=(type==='l'?'<i class="fas fa-spinner fa-spin"></i>':type==='s'?'<i class="fas fa-check-circle"></i>':'<i class="fas fa-exclamation-circle"></i>')+' '+msg;
  clearTimeout(toastTimer);
  setTimeout(()=>el.classList.add('show'),10);
  if(type!=='l') toastTimer=setTimeout(()=>el.classList.remove('show'),3500);
}

// Upload rapide
document.getElementById('quickUploadForm').addEventListener('submit',function(e){
  e.preventDefault();
  const fd=new FormData(this);
  const btn=document.getElementById('quickSubmitBtn');
  const prog=document.getElementById('quickProgress');
  const fill=document.getElementById('quickProgressFill');
  const msg=document.getElementById('quickMsg');
  btn.disabled=true;btn.innerHTML='<i class="fas fa-spinner fa-spin"></i> Envoi...';
  prog.style.display='block';msg.style.display='none';
  const xhr=new XMLHttpRequest();
  xhr.open('POST',UPLOAD_URL);
  xhr.upload.onprogress=e=>{if(e.lengthComputable){const pct=Math.round(e.loaded/e.total*100);fill.style.width=pct+'%';}};
  xhr.onload=()=>{
    prog.style.display='none';btn.disabled=false;btn.innerHTML='<i class="fas fa-upload"></i> Uploader';
    try{
      const res=JSON.parse(xhr.responseText);
      if(res.success){
        msg.style.cssText='display:block;padding:8px 14px;border-radius:8px;font-size:.8rem;background:rgba(37,211,102,.1);border:1px solid rgba(37,211,102,.3);color:#66BB6A';
        msg.innerHTML='✅ '+res.message+' — Rechargement...';
        toast('Photo ajoutée !','s');
        setTimeout(()=>location.reload(),1500);
      }else{
        msg.style.cssText='display:block;padding:8px 14px;border-radius:8px;font-size:.8rem;background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.3);color:#EF5350';
        msg.innerHTML='❌ '+res.message;
        toast(res.message,'e');
      }
    }catch(err){toast('Erreur serveur','e');}
  };
  xhr.onerror=()=>{toast('Erreur réseau','e');btn.disabled=false;btn.innerHTML='<i class="fas fa-upload"></i> Uploader';};
  xhr.send(fd);
});

// Remplacer photo
function replacePhoto(id,btn){
  const input=document.createElement('input');input.type='file';input.accept='image/jpeg,image/png,image/webp';
  document.body.appendChild(input);
  input.addEventListener('change',()=>{
    if(!input.files[0])return;
    toast('Upload en cours...','l');
    const fd=new FormData();
    fd.append('image',input.files[0]);fd.append('zone','galerie');fd.append('item_id',id);fd.append('titre','Photo galerie');fd.append('categorie_id',1);
    fetch('<?= SITE_URL ?>/api/inline_upload.php',{method:'POST',body:fd})
      .then(r=>r.json()).then(res=>{
        if(res.success){toast('Photo remplacée !','s');const img=btn.closest('.masonry-item').querySelector('img');if(img)img.src=res.url+'?t='+Date.now();}
        else toast(res.message,'e');
      }).catch(()=>toast('Erreur réseau','e'));
    document.body.removeChild(input);
  });input.click();
}

// Supprimer photo
function deletePhoto(id,card){
  if(!confirm('Supprimer cette photo ?'))return;
  toast('Suppression...','l');
  fetch(DELETE_URL,{method:'POST',body:new URLSearchParams({id})})
    .then(r=>r.json()).then(res=>{
      if(res.success){toast('Photo supprimée','s');card.remove();}
      else toast(res.message,'e');
    });
}
</script>
<?php endif; ?>
<?php include_once __DIR__ . '/../includes/admin-bar.php'; ?>
</body>
</html>
