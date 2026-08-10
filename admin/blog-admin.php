<?php
require_once __DIR__ . '/../includes/config.php';
requireAdmin();

// Catégories
$categories = $pdo->query("SELECT * FROM categories_blog ORDER BY ordre")->fetchAll();

// Actions POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'save_article') {
        $id     = (int)($_POST['id'] ?? 0);
        $titre  = sanitize($_POST['titre'] ?? '');
        $slug   = sanitize($_POST['slug'] ?? strtolower(preg_replace('/[^a-z0-9]+/','-',$titre)));
        $statut = sanitize($_POST['statut'] ?? 'brouillon');
        $datePub= $statut === 'publie' ? date('Y-m-d H:i:s') : null;

        $data = [
            (int)($_POST['categorie_id'] ?? 1),
            $titre,
            sanitize($_POST['titre_ar']          ?? ''),
            $slug,
            sanitize($_POST['extrait']            ?? ''),
            $_POST['contenu']                     ?? '',
            $statut,
            sanitize($_POST['meta_titre']         ?? $titre),
            sanitize($_POST['meta_description']   ?? ''),
        ];

        // Image principale upload
        $imgPath = $_POST['image_existante'] ?? '';
        if (!empty($_FILES['image_principale']['tmp_name']) && $_FILES['image_principale']['error'] === 0) {
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime  = $finfo->file($_FILES['image_principale']['tmp_name']);
            $exts  = ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];
            if (isset($exts[$mime]) && $_FILES['image_principale']['size'] < 5*1024*1024) {
                $dir = UPLOAD_PATH . '/blog/';
                if (!is_dir($dir)) mkdir($dir, 0755, true);
                $fname = 'blog_' . uniqid() . '.' . $exts[$mime];
                if (move_uploaded_file($_FILES['image_principale']['tmp_name'], $dir . $fname)) {
                    $imgPath = 'blog/' . $fname;
                }
            }
        }
        $data[] = $imgPath;

        try {
            if ($id > 0) {
                $data[] = $id;
                $pdo->prepare("
                    UPDATE blog_articles SET
                        categorie_id=?,titre=?,titre_ar=?,slug=?,extrait=?,contenu=?,
                        statut=?,meta_titre=?,meta_description=?,image_principale=?,
                        date_publication=COALESCE(date_publication,".($statut==='publie'?'NOW()':'NULL')."),
                        updated_at=NOW()
                    WHERE id=?
                ")->execute($data);
                $msg = 'Article mis à jour !';
            } else {
                $pdo->prepare("
                    INSERT INTO blog_articles
                        (categorie_id,titre,titre_ar,slug,extrait,contenu,statut,meta_titre,meta_description,image_principale,date_publication)
                    VALUES (?,?,?,?,?,?,?,?,?,?,".($statut==='publie'?'NOW()':'NULL').")
                ")->execute($data);
                $id  = $pdo->lastInsertId();
                $msg = 'Article créé avec succès !';
            }
            header('Location: blog-admin.php?msg='.urlencode($msg).'&type=success');
            exit;
        } catch(Exception $e) {
            $error = $e->getMessage();
        }
    }

    if ($action === 'toggle_statut') {
        $id  = (int)($_POST['id'] ?? 0);
        $cur = $pdo->prepare("SELECT statut FROM blog_articles WHERE id=?");
        $cur->execute([$id]);
        $cur = $cur->fetchColumn();
        $new = $cur === 'publie' ? 'brouillon' : 'publie';
        $pub = $new === 'publie' ? ', date_publication=NOW()' : '';
        $pdo->prepare("UPDATE blog_articles SET statut=? $pub WHERE id=?")->execute([$new, $id]);
        header('Location: blog-admin.php');
        exit;
    }

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $row = $pdo->prepare("SELECT image_principale FROM blog_articles WHERE id=?");
        $row->execute([$id]);
        $row = $row->fetch();
        if ($row && $row['image_principale']) {
            $path = UPLOAD_PATH . '/' . $row['image_principale'];
            if (file_exists($path)) unlink($path);
        }
        $pdo->prepare("DELETE FROM blog_articles WHERE id=?")->execute([$id]);
        header('Location: blog-admin.php?msg=Article+supprimé&type=success');
        exit;
    }
}

// Récupérer articles
$articles = $pdo->query("
    SELECT a.*, c.nom AS cat_nom
    FROM blog_articles a
    LEFT JOIN categories_blog c ON a.categorie_id = c.id
    ORDER BY a.created_at DESC
")->fetchAll();

$total    = count($articles);
$publies  = count(array_filter($articles, fn($a) => $a['statut'] === 'publie'));
$brouillons = count(array_filter($articles, fn($a) => $a['statut'] === 'brouillon'));
$totalVues  = array_sum(array_column($articles, 'vues'));

$msg     = $_GET['msg']  ?? '';
$msgType = $_GET['type'] ?? 'success';

// Article à éditer
$editArticle = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM blog_articles WHERE id=?");
    $stmt->execute([(int)$_GET['edit']]);
    $editArticle = $stmt->fetch();
}
$mode = $editArticle ? 'edit' : (isset($_GET['new']) ? 'new' : 'list');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Blog — Admin EL MOUSSAOUI</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600;700&family=Jost:wght@300;400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="../css/style.css">
  <style>
    body{overflow-x:hidden}
    .sidebar-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.6);z-index:999}
    .sidebar-overlay.show{display:block}
    @media(max-width:768px){.sidebar{position:fixed;left:0;top:0;bottom:0;z-index:1000;transform:translateX(-100%);transition:var(--transition)}.sidebar.open{transform:translateX(0)}}

    /* Articles list */
    .articles-list{display:flex;flex-direction:column;gap:12px}
    .article-row{background:var(--dark-card);border:1px solid var(--border);border-radius:var(--radius);padding:16px 20px;display:flex;align-items:center;gap:16px;transition:var(--transition)}
    .article-row:hover{border-color:rgba(212,175,55,.3)}
    .article-thumb{width:72px;height:54px;border-radius:8px;background:var(--dark-3);object-fit:cover;flex-shrink:0}
    .article-thumb-placeholder{width:72px;height:54px;border-radius:8px;background:var(--dark-3);display:flex;align-items:center;justify-content:center;flex-shrink:0;color:#333;font-size:1.2rem}
    .article-info{flex:1;min-width:0}
    .article-title{font-size:.9rem;font-weight:700;color:var(--white);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
    .article-meta{display:flex;gap:10px;flex-wrap:wrap;margin-top:5px;align-items:center}
    .article-cat{background:var(--dark-3);padding:2px 8px;border-radius:6px;font-size:.7rem;color:var(--text-muted)}
    .article-date{font-size:.72rem;color:#555}
    .article-vues{font-size:.72rem;color:#555}
    .statut-pill{padding:3px 10px;border-radius:20px;font-size:.7rem;font-weight:700}
    .pill-publie{background:rgba(37,211,102,.12);color:#25D366}
    .pill-brouillon{background:rgba(136,136,136,.1);color:#888}
    .pill-archive{background:rgba(239,68,68,.1);color:#EF5350}
    .article-actions{display:flex;gap:6px;flex-shrink:0}
    .act-btn{width:30px;height:30px;border-radius:7px;border:1px solid var(--border);background:none;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:.78rem;transition:var(--transition);color:var(--text-muted);text-decoration:none}
    .act-btn:hover{border-color:var(--gold);color:var(--gold)}
    .act-btn.danger:hover{border-color:rgba(239,68,68,.4);color:#EF5350}
    .act-btn.publie{border-color:rgba(37,211,102,.3);color:#25D366}
    .act-btn.brouillon{border-color:rgba(136,136,136,.3);color:#888}
    .empty-state{text-align:center;padding:60px 20px;color:var(--text-muted)}
    .empty-state i{font-size:2.5rem;opacity:.2;display:block;margin-bottom:12px}

    /* Éditeur */
    .editor-layout{display:grid;grid-template-columns:1fr 300px;gap:20px;align-items:start}
    .editor-main{display:flex;flex-direction:column;gap:16px}
    .editor-sidebar{display:flex;flex-direction:column;gap:14px}
    .editor-card{background:var(--dark-card);border:1px solid var(--border);border-radius:var(--radius);padding:18px}
    .editor-card h4{font-size:.78rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--text-muted);margin-bottom:14px;display:flex;align-items:center;gap:6px}
    .editor-card h4 i{color:var(--gold)}
    .editor-toolbar{display:flex;gap:4px;flex-wrap:wrap;margin-bottom:8px}
    .tb-btn{padding:5px 10px;border-radius:6px;border:1px solid var(--border);background:none;color:var(--text-muted);cursor:pointer;font-size:.75rem;transition:var(--transition)}
    .tb-btn:hover{border-color:var(--gold);color:var(--gold)}
    #contentEditor{min-height:300px;background:var(--dark-3);border:1px solid var(--border);border-radius:8px;padding:14px;color:var(--white);font-size:.88rem;line-height:1.8;outline:none;font-family:var(--ff-body)}
    #contentEditor:focus{border-color:var(--gold)}
    .img-upload-zone{border:2px dashed var(--border);border-radius:8px;padding:20px;text-align:center;cursor:pointer;transition:var(--transition);position:relative}
    .img-upload-zone:hover{border-color:var(--gold);background:rgba(212,175,55,.04)}
    .img-upload-zone input{position:absolute;inset:0;opacity:0;cursor:pointer}
    .img-upload-zone i{font-size:1.5rem;color:var(--gold);display:block;margin-bottom:6px}
    .img-preview{width:100%;border-radius:8px;margin-top:8px;object-fit:cover;max-height:150px;display:none}
    .char-count{font-size:.68rem;color:#555;text-align:right;margin-top:4px}
    .search-input{background:var(--dark-3);border:1px solid var(--border);border-radius:8px;padding:8px 14px;color:var(--white);font-size:.82rem;outline:none;width:220px}
    .search-input:focus{border-color:var(--gold)}
    @media(max-width:900px){.editor-layout{grid-template-columns:1fr}}
  </style>
</head>
<body>
<div class="sidebar-overlay" id="sidebarOverlay"></div>
<div class="admin-layout">

  <?php $activePage = 'blog'; include_once __DIR__ . '/../includes/admin-sidebar.php'; ?>

  <main class="admin-main">
    <div class="admin-topbar">
      <div style="display:flex;align-items:center;gap:12px">
        <button id="sidebarToggle" class="topbar-btn"><i class="fas fa-bars"></i></button>
        <div class="topbar-title">
          <?php if ($mode === 'list'): ?>
          <h2 data-fr="Gestion Blog" data-ar="إدارة المدونة">Gestion Blog</h2><p data-fr="Articles et publications" data-ar="المقالات والمنشورات">Articles et publications</p>
          <?php elseif ($mode === 'new'): ?>
          <h2>Nouvel article</h2><p><a href="blog-admin.php" style="color:var(--gold);font-size:.8rem">← Retour à la liste</a></p>
          <?php else: ?>
          <h2>Modifier l'article</h2><p><a href="blog-admin.php" style="color:var(--gold);font-size:.8rem">← Retour à la liste</a></p>
          <?php endif; ?>
        </div>
      </div>
      <div class="topbar-actions">
        <button class="topbar-btn" onclick="location.reload()"><i class="fas fa-sync-alt"></i></button>
        <?php if ($mode === 'list'): ?>
        <a href="blog-admin.php?new=1" class="btn-primary" style="padding:8px 18px;font-size:.82rem;text-decoration:none">
          <i class="fas fa-plus"></i> <span data-fr="Nouvel article" data-ar="مقال جديد">Nouvel article</span>
        </a>
        <?php endif; ?>
        <div class="admin-avatar">A</div>
      </div>
    </div>

    <div class="admin-content">

      <?php if ($msg): ?>
      <div class="alert alert-<?= $msgType === 'success' ? 'success' : 'error' ?>" style="margin-bottom:20px">
        <i class="fas fa-<?= $msgType === 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i>
        <?= htmlspecialchars($msg) ?>
      </div>
      <?php endif; ?>

      <?php if ($mode === 'list'): ?>
      <!-- ── LISTE ──────────────────────────────────────────── -->

      <!-- Stats -->
      <div class="stats-grid" style="margin-bottom:24px">
        <div class="stat-card">
          <div class="stat-card-header"><div class="stat-card-icon gold"><i class="fas fa-pen-nib"></i></div></div>
          <div class="stat-card-value"><?= $total ?></div>
          <div class="stat-card-label" data-fr="Total articles" data-ar="إجمالي المقالات">Total articles</div>
        </div>
        <div class="stat-card">
          <div class="stat-card-header"><div class="stat-card-icon" style="background:rgba(37,211,102,.1);color:#25D366"><i class="fas fa-globe"></i></div></div>
          <div class="stat-card-value"><?= $publies ?></div>
          <div class="stat-card-label" data-fr="Publiés" data-ar="منشورة">Publiés</div>
        </div>
        <div class="stat-card">
          <div class="stat-card-header"><div class="stat-card-icon" style="background:rgba(136,136,136,.1);color:#888"><i class="fas fa-file-alt"></i></div></div>
          <div class="stat-card-value"><?= $brouillons ?></div>
          <div class="stat-card-label" data-fr="Brouillons" data-ar="مسودات">Brouillons</div>
        </div>
        <div class="stat-card">
          <div class="stat-card-header"><div class="stat-card-icon" style="background:rgba(59,130,246,.1);color:#60A5FA"><i class="fas fa-eye"></i></div></div>
          <div class="stat-card-value" dir="ltr"><?= number_format($totalVues,0,',',' ') ?></div>
          <div class="stat-card-label" data-fr="Vues totales" data-ar="إجمالي المشاهدات">Vues totales</div>
        </div>
      </div>

      <!-- Filtres -->
      <div style="display:flex;gap:10px;align-items:center;margin-bottom:20px;flex-wrap:wrap">
        <input type="text" class="search-input" id="searchArt" placeholder="🔍 Rechercher un article..." data-fr-placeholder="🔍 Rechercher un article..." data-ar-placeholder="🔍 البحث عن مقال..." oninput="filterArt()">
        <button class="tfilter active" onclick="setFilter('all',this)" style="padding:6px 14px;border-radius:20px;border:1px solid var(--border);background:none;color:#888;cursor:pointer;font-size:.75rem" data-fr="Tous" data-ar="الكل">Tous</button>
        <button class="tfilter" onclick="setFilter('publie',this)" style="padding:6px 14px;border-radius:20px;border:1px solid var(--border);background:none;color:#888;cursor:pointer;font-size:.75rem" data-fr="Publiés" data-ar="منشورة">Publiés</button>
        <button class="tfilter" onclick="setFilter('brouillon',this)" style="padding:6px 14px;border-radius:20px;border:1px solid var(--border);background:none;color:#888;cursor:pointer;font-size:.75rem" data-fr="Brouillons" data-ar="مسودات">Brouillons</button>
      </div>

      <!-- Liste -->
      <?php if (empty($articles)): ?>
      <div class="empty-state">
        <i class="fas fa-pen-nib"></i>
        <p data-fr="Aucun article pour l'instant." data-ar="لا توجد مقالات حالياً.">Aucun article pour l'instant.</p>
        <a href="blog-admin.php?new=1" class="btn-primary" style="display:inline-flex;margin-top:16px;text-decoration:none">
          <i class="fas fa-plus"></i> <span data-fr="Écrire le premier article" data-ar="كتابة أول مقال">Écrire le premier article</span>
        </a>
      </div>
      <?php else: ?>
      <div class="articles-list" id="articlesList">
        <?php foreach ($articles as $a):
          $statPill = match($a['statut']) {
            'publie'    => 'pill-publie',
            'archive'   => 'pill-archive',
            default     => 'pill-brouillon'
          };
          $statLabel = match($a['statut']) {
            'publie'  => 'Publié', 'archive' => 'Archivé', default => 'Brouillon'
          };
          $date = date('d/m/Y', strtotime($a['created_at']));
          $imgSrc = $a['image_principale'] ? UPLOAD_URL . '/' . $a['image_principale'] : null;
        ?>
        <div class="article-row"
             data-statut="<?= $a['statut'] ?>"
             data-search="<?= strtolower($a['titre'] . ' ' . ($a['cat_nom'] ?? '')) ?>">
          <?php if ($imgSrc): ?>
          <img src="<?= htmlspecialchars($imgSrc) ?>" class="article-thumb" alt="">
          <?php else: ?>
          <div class="article-thumb-placeholder"><i class="fas fa-image"></i></div>
          <?php endif; ?>
          <div class="article-info">
            <div class="article-title"><?= htmlspecialchars($a['titre']) ?></div>
            <div class="article-meta">
              <span class="article-cat"><?= htmlspecialchars($a['cat_nom'] ?? '—') ?></span>
              <span class="statut-pill <?= $statPill ?>"><?= $statLabel ?></span>
              <span class="article-date"><i class="fas fa-calendar" style="margin-right:3px"></i><?= $date ?></span>
              <span class="article-vues"><i class="fas fa-eye" style="margin-right:3px"></i><?= number_format($a['vues'],0,',',' ') ?> vues</span>
            </div>
          </div>
          <div class="article-actions">
            <a href="blog-admin.php?edit=<?= $a['id'] ?>" class="act-btn" title="Modifier"><i class="fas fa-edit"></i></a>
            <a href="../pages/blog.php" target="_blank" class="act-btn" title="Voir"><i class="fas fa-eye"></i></a>
            <form method="POST" style="display:inline">
              <input type="hidden" name="action" value="toggle_statut">
              <input type="hidden" name="id" value="<?= $a['id'] ?>">
              <button type="submit" class="act-btn <?= $a['statut'] === 'publie' ? 'publie' : 'brouillon' ?>"
                      title="<?= $a['statut'] === 'publie' ? 'Dépublier' : 'Publier' ?>">
                <i class="fas <?= $a['statut'] === 'publie' ? 'fa-toggle-on' : 'fa-toggle-off' ?>"></i>
              </button>
            </form>
            <form method="POST" style="display:inline" onsubmit="return confirm('Supprimer cet article ?')">
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="id" value="<?= $a['id'] ?>">
              <button type="submit" class="act-btn danger" title="Supprimer"><i class="fas fa-trash"></i></button>
            </form>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>

      <?php else: ?>
      <!-- ── ÉDITEUR ─────────────────────────────────────────── -->
      <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="action" value="save_article">
        <input type="hidden" name="id" value="<?= $editArticle['id'] ?? 0 ?>">
        <input type="hidden" name="image_existante" value="<?= htmlspecialchars($editArticle['image_principale'] ?? '') ?>">

        <div class="editor-layout">

          <!-- Colonne principale -->
          <div class="editor-main">

            <!-- Titre -->
            <div class="editor-card">
              <h4><i class="fas fa-heading"></i> Titre de l'article</h4>
              <div class="form-group" style="margin-bottom:12px">
                <input type="text" name="titre" class="form-control"
                       value="<?= htmlspecialchars($editArticle['titre'] ?? '') ?>"
                       placeholder="Titre en français *" required
                       oninput="genSlug(this.value)">
              </div>
              <div class="form-group">
                <input type="text" name="titre_ar" class="form-control" dir="rtl"
                       value="<?= htmlspecialchars($editArticle['titre_ar'] ?? '') ?>"
                       placeholder="عنوان المقال بالعربية">
              </div>
            </div>

            <!-- Extrait -->
            <div class="editor-card">
              <h4><i class="fas fa-align-left"></i> Extrait / Résumé</h4>
              <textarea name="extrait" class="form-control" rows="2"
                        placeholder="Résumé court affiché dans la liste des articles (max 300 caractères)..."
                        maxlength="300" oninput="countChars(this,'excerptCount')"><?= htmlspecialchars($editArticle['extrait'] ?? '') ?></textarea>
              <div class="char-count"><span id="excerptCount"><?= strlen($editArticle['extrait'] ?? '') ?></span>/300</div>
            </div>

            <!-- Contenu -->
            <div class="editor-card">
              <h4><i class="fas fa-pen"></i> Contenu de l'article</h4>
              <div class="editor-toolbar">
                <button type="button" class="tb-btn" onclick="fmt('bold')" title="Gras"><i class="fas fa-bold"></i></button>
                <button type="button" class="tb-btn" onclick="fmt('italic')" title="Italique"><i class="fas fa-italic"></i></button>
                <button type="button" class="tb-btn" onclick="fmt('underline')" title="Souligné"><i class="fas fa-underline"></i></button>
                <button type="button" class="tb-btn" onclick="insHtml('<h2>','</h2>')" title="Titre H2">H2</button>
                <button type="button" class="tb-btn" onclick="insHtml('<h3>','</h3>')" title="Titre H3">H3</button>
                <button type="button" class="tb-btn" onclick="insHtml('<ul><li>','</li></ul>')" title="Liste"><i class="fas fa-list-ul"></i></button>
                <button type="button" class="tb-btn" onclick="insHtml('<blockquote>','</blockquote>')" title="Citation"><i class="fas fa-quote-left"></i></button>
                <button type="button" class="tb-btn" onclick="insHtml('<strong>','</strong>')" title="Important"><i class="fas fa-exclamation"></i></button>
              </div>
              <div id="contentEditor"
                   contenteditable="true"><?= $editArticle['contenu'] ?? '' ?></div>
              <input type="hidden" name="contenu" id="contenHidden">
            </div>

            <!-- SEO -->
            <div class="editor-card">
              <h4><i class="fas fa-search"></i> SEO & Métadonnées</h4>
              <div class="form-group" style="margin-bottom:12px">
                <label class="form-label">Meta titre</label>
                <input type="text" name="meta_titre" class="form-control" maxlength="160"
                       value="<?= htmlspecialchars($editArticle['meta_titre'] ?? '') ?>"
                       placeholder="Titre SEO (max 160 caractères)"
                       oninput="countChars(this,'metaTitleCount')">
                <div class="char-count"><span id="metaTitleCount"><?= strlen($editArticle['meta_titre'] ?? '') ?></span>/160</div>
              </div>
              <div class="form-group">
                <label class="form-label">Meta description</label>
                <textarea name="meta_description" class="form-control" rows="2" maxlength="320"
                          placeholder="Description SEO (max 320 caractères)"
                          oninput="countChars(this,'metaDescCount')"><?= htmlspecialchars($editArticle['meta_description'] ?? '') ?></textarea>
                <div class="char-count"><span id="metaDescCount"><?= strlen($editArticle['meta_description'] ?? '') ?></span>/320</div>
              </div>
            </div>
          </div>

          <!-- Colonne latérale -->
          <div class="editor-sidebar">

            <!-- Publication -->
            <div class="editor-card">
              <h4><i class="fas fa-globe"></i> Publication</h4>
              <div class="form-group" style="margin-bottom:14px">
                <label class="form-label" data-fr="Statut" data-ar="الحالة">Statut</label>
                <select name="statut" class="form-control">
                  <option value="brouillon" <?= ($editArticle['statut'] ?? '') === 'brouillon' ? 'selected' : '' ?>>📝 Brouillon</option>
                  <option value="publie"    <?= ($editArticle['statut'] ?? '') === 'publie'    ? 'selected' : '' ?>>\ud83c\udf10 Publié</option>
                  <option value="archive"   <?= ($editArticle['statut'] ?? '') === 'archive'   ? 'selected' : '' ?>>📦 Archivé</option>
                </select>
              </div>
              <div class="form-group" style="margin-bottom:14px">
                <label class="form-label" data-fr="Catégorie" data-ar="التصنيف">Catégorie</label>
                <select name="categorie_id" class="form-control">
                  <?php foreach ($categories as $cat): ?>
                  <option value="<?= $cat['id'] ?>" <?= ($editArticle['categorie_id'] ?? 0) == $cat['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($cat['nom']) ?>
                  </option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="form-group">
                <label class="form-label">Slug URL</label>
                <input type="text" name="slug" id="slugInput" class="form-control"
                       value="<?= htmlspecialchars($editArticle['slug'] ?? '') ?>"
                       placeholder="url-de-larticle">
              </div>
            </div>

            <!-- Image principale -->
            <div class="editor-card">
              <h4><i class="fas fa-image"></i> Image principale</h4>
              <?php if (!empty($editArticle['image_principale'])): ?>
              <img src="<?= UPLOAD_URL . '/' . $editArticle['image_principale'] ?>"
                   style="width:100%;border-radius:8px;margin-bottom:10px;object-fit:cover;max-height:140px" alt="">
              <?php endif; ?>
              <div class="img-upload-zone">
                <i class="fas fa-cloud-upload-alt"></i>
                <p style="font-size:.78rem;color:var(--text-muted)">Cliquez ou glissez une image</p>
                <p style="font-size:.68rem;color:#555;margin-top:3px">JPG, PNG, WEBP — max 5 Mo</p>
                <input type="file" name="image_principale" accept="image/*" onchange="previewImg(this)">
              </div>
              <img id="imgPreview" class="img-preview" alt="">
            </div>

            <!-- Actions -->
            <div class="editor-card">
              <h4><i class="fas fa-save"></i> Sauvegarder</h4>
              <button type="submit" class="btn-primary" style="width:100%;margin-bottom:10px" onclick="syncContent()">
                <i class="fas fa-save"></i> Enregistrer l'article
              </button>
              <a href="blog-admin.php" class="btn-secondary" style="width:100%;text-align:center;display:block;text-decoration:none">
                Annuler
              </a>
            </div>

          </div>
        </div>
      </form>
      <?php endif; ?>

    </div>
  </main>
</div>

<script>
document.getElementById('sidebarToggle').addEventListener('click', () => {
  document.getElementById('sidebar').classList.toggle('open');
  document.getElementById('sidebarOverlay').classList.toggle('show');
});
document.getElementById('sidebarOverlay').addEventListener('click', () => {
  document.getElementById('sidebar').classList.remove('open');
  document.getElementById('sidebarOverlay').classList.remove('show');
});

// Filtres liste
let curFilter = 'all';
function setFilter(f, btn) {
  curFilter = f;
  document.querySelectorAll('.tfilter').forEach(b => { b.classList.remove('active'); b.style.color='#888'; b.style.borderColor='var(--border)'; });
  btn.classList.add('active'); btn.style.color='var(--gold)'; btn.style.borderColor='var(--gold)';
  filterArt();
}
function filterArt() {
  const q = (document.getElementById('searchArt')?.value || '').toLowerCase();
  document.querySelectorAll('.article-row').forEach(row => {
    const matchF = curFilter === 'all' || row.dataset.statut === curFilter;
    const matchQ = !q || row.dataset.search.includes(q);
    row.style.display = (matchF && matchQ) ? '' : 'none';
  });
}

// Éditeur
function fmt(cmd) { document.execCommand(cmd, false); }
function insHtml(before, after) {
  const ed  = document.getElementById('contentEditor');
  const sel = window.getSelection();
  if (sel && sel.rangeCount) {
    const range = sel.getRangeAt(0);
    const txt   = sel.toString();
    const node  = document.createElement('div');
    node.innerHTML = before + (txt || 'Texte') + after;
    range.deleteContents();
    range.insertNode(node.firstChild);
  }
}
function syncContent() {
  const ed = document.getElementById('contentEditor');
  if (ed) document.getElementById('contenHidden').value = ed.innerHTML;
}
function countChars(el, countId) {
  document.getElementById(countId).textContent = el.value.length;
}
function genSlug(val) {
  const slug = val.toLowerCase()
    .normalize('NFD').replace(/[\̀-\ͯ]/g,'')
    .replace(/[^a-z0-9\s-]/g,'').replace(/\s+/g,'-').replace(/-+/g,'-');
  const el = document.getElementById('slugInput');
  if (el && !el.dataset.manual) el.value = slug;
}
document.getElementById('slugInput')?.addEventListener('input', function(){ this.dataset.manual = '1'; });

function previewImg(input) {
  const file = input.files[0];
  if (!file) return;
  const reader = new FileReader();
  reader.onload = e => {
    const prev = document.getElementById('imgPreview');
    prev.src   = e.target.result;
    prev.style.display = 'block';
  };
  reader.readAsDataURL(file);
}
</script>
<script src="../js/admin-lang.js"></script>
</body>
</html>
