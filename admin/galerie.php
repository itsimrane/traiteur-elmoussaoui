<?php
/**
 * admin/galerie.php
 * Gestion complète de la galerie photos/vidéos.
 * Réutilise les mêmes endpoints d'upload/suppression que le site
 * public (api/upload_media.php, api/delete_media.php) pour ne rien
 * casser côté fichiers déjà en place.
 */
require_once __DIR__ . '/../includes/config.php';
requireAdmin();

$msg = ''; $msgType = '';

// ── Actions POST ────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id     = (int)($_POST['id'] ?? 0);

    if ($action === 'update' && $id) {
        $pdo->prepare("
            UPDATE galerie SET
                titre=?, description=?, alt_text=?, categorie_id=?, ordre=?, updated_at=NOW()
            WHERE id=?
        ")->execute([
            sanitize($_POST['titre'] ?? ''),
            sanitize($_POST['description'] ?? ''),
            sanitize($_POST['alt_text'] ?? ''),
            (int)($_POST['categorie_id'] ?? 0),
            (int)($_POST['ordre'] ?? 0),
            $id
        ]);
        $msg = 'Élément mis à jour avec succès.'; $msgType = 'success';
    }

    if ($action === 'toggle_actif' && $id) {
        $pdo->prepare("UPDATE galerie SET actif = 1 - actif WHERE id=?")->execute([$id]);
        $msg = 'Statut mis à jour.'; $msgType = 'success';
    }

    if ($action === 'toggle_vedette' && $id) {
        $pdo->prepare("UPDATE galerie SET en_vedette = 1 - en_vedette WHERE id=?")->execute([$id]);
        $msg = 'Mise en avant mise à jour.'; $msgType = 'success';
    }

    if ($action === 'add_video') {
        $pdo->prepare("
            INSERT INTO galerie (categorie_id, type, titre, description, url_video, miniature, alt_text, ordre, actif)
            VALUES (?, 'video', ?, ?, ?, ?, ?, ?, 1)
        ")->execute([
            (int)($_POST['categorie_id'] ?? 1),
            sanitize($_POST['titre'] ?? ''),
            sanitize($_POST['description'] ?? ''),
            sanitize($_POST['url_video'] ?? ''),
            sanitize($_POST['miniature'] ?? ''),
            sanitize($_POST['titre'] ?? ''),
            (int)($_POST['ordre'] ?? 0),
        ]);
        $msg = 'Vidéo ajoutée avec succès.'; $msgType = 'success';
    }

    if ($msg) { header('Location: galerie.php?msg=' . urlencode($msg) . '&type=' . $msgType); exit; }
}

if (!empty($_GET['msg'])) { $msg = $_GET['msg']; $msgType = $_GET['type'] ?? 'success'; }

// ── Filtres ────────────────────────────────────────────────────
$filtreCat   = (int)($_GET['cat'] ?? 0);
$filtreType  = $_GET['type_media'] ?? '';
$showInactif = isset($_GET['inactif']);
$recherche   = trim($_GET['q'] ?? '');

$where = ['1=1'];
$params = [];
if (!$showInactif) { $where[] = 'g.actif = 1'; }
if ($filtreCat) { $where[] = 'g.categorie_id = ?'; $params[] = $filtreCat; }
if ($filtreType && in_array($filtreType, ['photo','video'], true)) { $where[] = 'g.type = ?'; $params[] = $filtreType; }
if ($recherche !== '') { $where[] = '(g.titre LIKE ? OR g.description LIKE ?)'; $like = '%'.$recherche.'%'; array_push($params, $like, $like); }
$whereSql = implode(' AND ', $where);

$items = [];
try {
    $stmt = $pdo->prepare("
        SELECT g.*, c.nom AS cat_nom
        FROM galerie g
        LEFT JOIN categories_galerie c ON c.id = g.categorie_id
        WHERE $whereSql
        ORDER BY g.en_vedette DESC, g.ordre ASC, g.id DESC
    ");
    $stmt->execute($params);
    $items = $stmt->fetchAll();
} catch (Exception $e) { $erreurBdd = $e->getMessage(); }

$categories = [];
try { $categories = $pdo->query("SELECT * FROM categories_galerie WHERE actif=1 ORDER BY ordre ASC")->fetchAll(); } catch (Exception $e) {}

// Compteurs
$totalPhotos = 0; $totalVideos = 0; $totalInactifs = 0; $totalVedette = 0;
try {
    $totalPhotos   = (int)$pdo->query("SELECT COUNT(*) FROM galerie WHERE type='photo' AND actif=1")->fetchColumn();
    $totalVideos   = (int)$pdo->query("SELECT COUNT(*) FROM galerie WHERE type='video' AND actif=1")->fetchColumn();
    $totalInactifs = (int)$pdo->query("SELECT COUNT(*) FROM galerie WHERE actif=0")->fetchColumn();
    $totalVedette  = (int)$pdo->query("SELECT COUNT(*) FROM galerie WHERE en_vedette=1 AND actif=1")->fetchColumn();
} catch (Exception $e) {}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <link rel="icon" type="image/png" href="../assets/img/favicon-32.png">
  <meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title>Galerie — Admin EL MOUSSAOUI</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600;700&family=Jost:wght@300;400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="../css/style.css">
  <style>
    body{overflow-x:hidden}
    .sidebar-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.6);z-index:999}
    .sidebar-overlay.show{display:block}
    @media(max-width:768px){.sidebar{position:fixed;left:0;top:0;bottom:0;z-index:1000;transform:translateX(-100%);transition:var(--transition)}.sidebar.open{transform:translateX(0)}}

    .upload-panel{background:var(--dark-card);border:1px solid var(--border);border-radius:var(--radius);padding:20px;margin-bottom:20px}
    .upload-panel h3{font-size:.9rem;color:var(--white);margin-bottom:14px}
    .upload-row{display:grid;grid-template-columns:2fr 1fr 1fr auto;gap:12px;align-items:end}
    @media(max-width:800px){.upload-row{grid-template-columns:1fr}}
    .upload-row label{display:block;font-size:.68rem;color:#888;text-transform:uppercase;letter-spacing:.5px;margin-bottom:5px}
    .upload-row input,.upload-row select{background:var(--dark-3);border:1px solid var(--border);border-radius:8px;padding:9px 13px;color:var(--white);font-size:.85rem;width:100%;font-family:inherit}
    .upload-row button{background:var(--gold);color:var(--dark);border:none;border-radius:8px;padding:10px 22px;font-weight:700;cursor:pointer;font-size:.85rem;white-space:nowrap}
    .progress-bar{background:var(--dark-3);border-radius:20px;height:6px;overflow:hidden;margin-top:10px;display:none}
    .progress-fill{height:100%;background:var(--gold);width:0;transition:.3s}

    .filters-bar{display:flex;flex-wrap:wrap;gap:8px;align-items:center;margin-bottom:20px}
    .tfilter{padding:6px 14px;border-radius:20px;border:1px solid var(--border);background:transparent;color:var(--text-muted);font-size:.75rem;cursor:pointer;text-decoration:none;display:inline-block}
    .tfilter.active{background:rgba(212,175,55,.12);border-color:var(--gold);color:var(--gold)}
    .filters-bar input[type=search]{background:var(--dark-3);border:1px solid var(--border);border-radius:8px;padding:7px 12px;color:var(--white);font-size:.8rem;min-width:180px}

    .gal-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(230px,1fr));gap:18px}
    .gal-card{background:var(--dark-card);border:1px solid var(--border);border-radius:12px;overflow:hidden;transition:var(--transition)}
    .gal-card.inactive{opacity:.45}
    .gal-thumb{height:150px;background:var(--dark-3);position:relative;overflow:hidden}
    .gal-thumb img{width:100%;height:100%;object-fit:cover}
    .gal-thumb .vid-icon{position:absolute;inset:0;display:flex;align-items:center;justify-content:center;font-size:2rem;color:rgba(212,175,55,.5);background:rgba(0,0,0,.3)}
    .gal-badge{position:absolute;top:8px;left:8px;background:rgba(10,10,15,.85);color:var(--gold);font-size:.65rem;padding:3px 9px;border-radius:12px;font-weight:700}
    .gal-star{position:absolute;top:8px;right:8px;width:28px;height:28px;border-radius:50%;background:rgba(10,10,15,.85);border:none;color:#555;cursor:pointer;display:flex;align-items:center;justify-content:center}
    .gal-star.active{color:var(--gold)}
    .gal-body{padding:12px 14px}
    .gal-title{font-size:.85rem;color:var(--white);font-weight:600;margin-bottom:2px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
    .gal-cat{font-size:.7rem;color:var(--gold)}
    .gal-actions{display:flex;gap:6px;margin-top:10px}
    .gal-actions button,.gal-actions a{flex:1;padding:6px;border-radius:7px;border:1px solid var(--border);background:transparent;color:var(--text-muted);cursor:pointer;font-size:.72rem;display:flex;align-items:center;justify-content:center;gap:4px;text-decoration:none}
    .gal-actions button:hover,.gal-actions a:hover{border-color:var(--gold);color:var(--gold)}
    .gal-actions .danger:hover{border-color:#EF5350;color:#EF5350}
    .gal-actions .ok:hover{border-color:#25D366;color:#25D366}

    .edit-panel{display:none;padding:14px;border-top:1px solid var(--border);background:var(--dark-3)}
    .edit-panel.show{display:block}
    .edit-panel input,.edit-panel select,.edit-panel textarea{width:100%;background:var(--dark);border:1px solid var(--border);border-radius:6px;padding:7px 10px;color:var(--white);font-size:.78rem;margin-bottom:8px;font-family:inherit}
    .edit-panel label{font-size:.65rem;color:#888;text-transform:uppercase}
    .edit-panel textarea{resize:vertical;min-height:50px}

    .video-toggle-panel{display:none;margin-top:14px;padding-top:14px;border-top:1px dashed var(--border)}
    .video-toggle-panel.show{display:block}
  </style>
</head>
<body>
<div class="sidebar-overlay" id="sidebarOverlay"></div>
<div class="admin-layout">
  <?php $activePage = 'galerie'; include_once __DIR__ . '/../includes/admin-sidebar.php'; ?>
  <main class="admin-main">
    <div class="admin-topbar">
      <div style="display:flex;align-items:center;gap:12px">
        <button id="sidebarToggle" class="topbar-btn"><i class="fas fa-bars"></i></button>
        <div class="topbar-title"><h2>Galerie</h2><p>Gestion complète des photos et vidéos</p></div>
      </div>
      <a href="../pages/galerie.php" target="_blank" class="topbar-btn" title="Voir sur le site"><i class="fas fa-external-link-alt"></i></a>
    </div>

    <div class="admin-content">
      <?php if ($msg): ?>
      <div class="alert alert-<?= $msgType === 'success' ? 'success' : 'error' ?>" style="margin-bottom:20px">
        <i class="fas fa-check-circle"></i> <?= htmlspecialchars($msg) ?>
      </div>
      <?php endif; ?>

      <!-- Stats -->
      <div class="stats-grid" style="margin-bottom:20px">
        <div class="stat-card"><div class="stat-card-value"><?= $totalPhotos ?></div><div class="stat-card-label">Photos actives</div></div>
        <div class="stat-card"><div class="stat-card-value"><?= $totalVideos ?></div><div class="stat-card-label">Vidéos actives</div></div>
        <div class="stat-card"><div class="stat-card-value" style="color:var(--gold)"><?= $totalVedette ?></div><div class="stat-card-label">Mises en avant</div></div>
        <div class="stat-card"><div class="stat-card-value" style="color:#888"><?= $totalInactifs ?></div><div class="stat-card-label">Masquées</div></div>
      </div>

      <!-- Upload rapide -->
      <div class="upload-panel">
        <h3><i class="fas fa-upload" style="color:var(--gold);margin-right:8px"></i>Ajouter une photo</h3>
        <form id="uploadForm" enctype="multipart/form-data">
          <div class="upload-row">
            <div>
              <label>Titre</label>
              <input type="text" name="titre" placeholder="Ex : Mariage à Errachidia" required>
            </div>
            <div>
              <label>Catégorie</label>
              <select name="categorie_id">
                <?php foreach ($categories as $c): ?>
                <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['nom']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div>
              <label>Photo (JPG/PNG/WEBP, max 5 Mo)</label>
              <input type="file" name="fichier" accept="image/*" required>
            </div>
            <div>
              <input type="hidden" name="type" value="photo">
              <button type="submit" id="uploadBtn"><i class="fas fa-upload"></i> Uploader</button>
            </div>
          </div>
        </form>
        <div class="progress-bar" id="progressBar"><div class="progress-fill" id="progressFill"></div></div>
        <div id="uploadMsg" style="display:none;margin-top:10px;padding:8px 14px;border-radius:8px;font-size:.8rem"></div>

        <div>
          <button type="button" onclick="document.getElementById('videoPanel').classList.toggle('show')"
                  style="background:none;border:none;color:var(--gold);font-size:.78rem;cursor:pointer;margin-top:10px">
            <i class="fas fa-video"></i> + Ajouter une vidéo (lien YouTube/Vimeo)
          </button>
          <div class="video-toggle-panel" id="videoPanel">
            <form method="POST" class="upload-row">
              <input type="hidden" name="action" value="add_video">
              <div>
                <label>Titre</label>
                <input type="text" name="titre" required>
              </div>
              <div>
                <label>Catégorie</label>
                <select name="categorie_id">
                  <?php foreach ($categories as $c): ?>
                  <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['nom']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div>
                <label>URL YouTube / Vimeo</label>
                <input type="url" name="url_video" placeholder="https://youtube.com/..." required>
              </div>
              <div>
                <button type="submit"><i class="fas fa-plus"></i> Ajouter</button>
              </div>
            </form>
          </div>
        </div>
      </div>

      <!-- Filtres -->
      <div class="filters-bar">
        <a href="?" class="tfilter <?= !$filtreCat && !$filtreType ? 'active':'' ?>">Toutes</a>
        <?php foreach ($categories as $c): ?>
        <a href="?cat=<?= $c['id'] ?>" class="tfilter <?= $filtreCat == $c['id'] ? 'active':'' ?>"><?= htmlspecialchars($c['nom']) ?></a>
        <?php endforeach; ?>
        <a href="?type_media=video" class="tfilter <?= $filtreType === 'video' ? 'active':'' ?>"><i class="fas fa-video"></i> Vidéos</a>
        <a href="?inactif=1" class="tfilter <?= $showInactif ? 'active':'' ?>"><i class="fas fa-eye-slash"></i> Voir les masquées</a>
        <form method="GET" style="display:inline-flex;gap:6px;margin-left:auto">
          <input type="search" name="q" placeholder="🔍 Rechercher..." value="<?= htmlspecialchars($recherche) ?>">
        </form>
      </div>

      <!-- Grille -->
      <?php if (empty($items)): ?>
      <div style="padding:60px 20px;text-align:center;color:#555;background:var(--dark-card);border:1px solid var(--border);border-radius:var(--radius)">
        <i class="fas fa-images" style="font-size:2.5rem;opacity:.2;display:block;margin-bottom:14px"></i>
        Aucun élément ne correspond à ces critères.
      </div>
      <?php else: ?>
      <div class="gal-grid">
        <?php foreach ($items as $it):
          $src = $it['type'] === 'video' ? ($it['miniature'] ? UPLOAD_URL.'/'.$it['miniature'] : null) : ($it['fichier'] ? UPLOAD_URL.'/'.$it['fichier'] : null);
        ?>
        <div class="gal-card <?= $it['actif'] ? '' : 'inactive' ?>" id="card-<?= $it['id'] ?>">
          <div class="gal-thumb">
            <?php if ($src): ?>
            <img src="<?= htmlspecialchars($src) ?>" alt="<?= htmlspecialchars($it['alt_text'] ?: $it['titre']) ?>" loading="lazy">
            <?php elseif ($it['type'] === 'video'): ?>
            <div class="vid-icon"><i class="fas fa-play-circle"></i></div>
            <?php endif; ?>
            <span class="gal-badge"><?= htmlspecialchars($it['cat_nom'] ?: '—') ?></span>
            <form method="POST" style="display:contents">
              <input type="hidden" name="action" value="toggle_vedette">
              <input type="hidden" name="id" value="<?= $it['id'] ?>">
              <button type="submit" class="gal-star <?= $it['en_vedette'] ? 'active' : '' ?>" title="Mettre en avant">
                <i class="fas fa-star"></i>
              </button>
            </form>
          </div>
          <div class="gal-body">
            <div class="gal-title"><?= htmlspecialchars($it['titre'] ?: 'Sans titre') ?></div>
            <div class="gal-cat"><?= $it['type'] === 'video' ? '🎬 Vidéo' : '📷 Photo' ?></div>
            <div class="gal-actions">
              <button type="button" onclick="document.getElementById('edit-<?= $it['id'] ?>').classList.toggle('show')" title="Modifier">
                <i class="fas fa-edit"></i>
              </button>
              <form method="POST" style="display:contents">
                <input type="hidden" name="action" value="toggle_actif">
                <input type="hidden" name="id" value="<?= $it['id'] ?>">
                <button type="submit" class="<?= $it['actif'] ? '' : 'ok' ?>" title="<?= $it['actif'] ? 'Masquer' : 'Réactiver' ?>">
                  <i class="fas fa-<?= $it['actif'] ? 'eye-slash' : 'eye' ?>"></i>
                </button>
              </form>
              <button type="button" class="danger" onclick="deleteItem(<?= $it['id'] ?>, '<?= $it['type'] ?>')" title="Supprimer">
                <i class="fas fa-trash"></i>
              </button>
            </div>
          </div>
          <div class="edit-panel" id="edit-<?= $it['id'] ?>">
            <form method="POST">
              <input type="hidden" name="action" value="update">
              <input type="hidden" name="id" value="<?= $it['id'] ?>">
              <label>Titre</label>
              <input type="text" name="titre" value="<?= htmlspecialchars($it['titre'] ?? '') ?>">
              <label>Description</label>
              <textarea name="description"><?= htmlspecialchars($it['description'] ?? '') ?></textarea>
              <label>Texte alternatif (SEO)</label>
              <input type="text" name="alt_text" value="<?= htmlspecialchars($it['alt_text'] ?? '') ?>">
              <label>Catégorie</label>
              <select name="categorie_id">
                <?php foreach ($categories as $c): ?>
                <option value="<?= $c['id'] ?>" <?= $it['categorie_id'] == $c['id'] ? 'selected':'' ?>><?= htmlspecialchars($c['nom']) ?></option>
                <?php endforeach; ?>
              </select>
              <label>Ordre d'affichage</label>
              <input type="number" name="ordre" value="<?= (int)$it['ordre'] ?>">
              <button type="submit" style="background:var(--gold);color:var(--dark);border:none;border-radius:6px;padding:8px;width:100%;font-weight:700;font-size:.78rem;cursor:pointer;margin-top:4px">
                <i class="fas fa-save"></i> Enregistrer
              </button>
            </form>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>

    </div>
  </main>
</div>
<script>
document.getElementById('sidebarToggle').addEventListener('click',()=>{
  document.getElementById('sidebar').classList.toggle('open');
  document.getElementById('sidebarOverlay').classList.toggle('show');
});
document.getElementById('sidebarOverlay').addEventListener('click',()=>{
  document.getElementById('sidebar').classList.remove('open');
  document.getElementById('sidebarOverlay').classList.remove('show');
});

// Upload (même endpoint que le site public, pour rester cohérent)
const UPLOAD_URL = '<?= rtrim(SITE_URL, "/") ?>/api/upload_media.php';
const DELETE_URL = '<?= rtrim(SITE_URL, "/") ?>/api/delete_media.php';

document.getElementById('uploadForm').addEventListener('submit', function(e){
  e.preventDefault();
  const fd = new FormData(this);
  const btn = document.getElementById('uploadBtn');
  const bar = document.getElementById('progressBar');
  const fill = document.getElementById('progressFill');
  const msg = document.getElementById('uploadMsg');
  btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Envoi...';
  bar.style.display = 'block'; msg.style.display = 'none';
  const xhr = new XMLHttpRequest();
  xhr.open('POST', UPLOAD_URL);
  xhr.upload.onprogress = e => { if (e.lengthComputable) fill.style.width = Math.round(e.loaded/e.total*100) + '%'; };
  xhr.onload = () => {
    bar.style.display = 'none'; btn.disabled = false; btn.innerHTML = '<i class="fas fa-upload"></i> Uploader';
    try {
      const res = JSON.parse(xhr.responseText);
      msg.style.display = 'block';
      if (res.success) {
        msg.style.cssText = 'display:block;padding:8px 14px;border-radius:8px;font-size:.8rem;background:rgba(37,211,102,.1);border:1px solid rgba(37,211,102,.3);color:#66BB6A';
        msg.innerHTML = '✅ ' + res.message + ' — Rechargement...';
        setTimeout(() => location.reload(), 1200);
      } else {
        msg.style.cssText = 'display:block;padding:8px 14px;border-radius:8px;font-size:.8rem;background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.3);color:#EF5350';
        msg.innerHTML = '❌ ' + res.message;
      }
    } catch(err) { msg.style.display='block'; msg.textContent = 'Erreur serveur'; }
  };
  xhr.send(fd);
});

// Suppression (même endpoint que le site public)
function deleteItem(id, type) {
  if (!confirm('Supprimer définitivement cet élément ? Cette action est irréversible.')) return;
  fetch(DELETE_URL, { method: 'POST', body: new URLSearchParams({ id }) })
    .then(r => r.json())
    .then(res => {
      if (res.success) {
        document.getElementById('card-' + id).remove();
      } else {
        alert('Erreur : ' + (res.message || 'suppression impossible'));
      }
    })
    .catch(() => alert('Erreur serveur lors de la suppression.'));
}
</script>
</body>
</html>
