<?php
require_once __DIR__ . '/../includes/config.php';
requireAdmin();

$categories = $pdo->query("SELECT * FROM categories_galerie WHERE actif = 1 ORDER BY ordre")->fetchAll();
$medias = $pdo->query("
    SELECT g.*, c.nom AS categorie_nom
    FROM galerie g
    LEFT JOIN categories_galerie c ON g.categorie_id = c.id
    ORDER BY g.created_at DESC
")->fetchAll();

$totalPhotos  = count(array_filter($medias, fn($m) => $m['type'] === 'photo'));
$totalVideos  = count(array_filter($medias, fn($m) => $m['type'] === 'video'));
$totalVedette = count(array_filter($medias, fn($m) => $m['en_vedette'] == 1));
$totalAll     = count($medias);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Galerie — Admin EL MOUSSAOUI</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600;700&family=Jost:wght@300;400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="../css/style.css">
  <style>
    body { overflow-x: hidden; }
    .upload-zone { border: 2px dashed var(--border); border-radius: var(--radius); padding: 40px 20px; text-align: center; cursor: pointer; transition: var(--transition); position: relative; }
    .upload-zone:hover, .upload-zone.dragover { border-color: var(--gold); background: rgba(212,175,55,0.04); }
    .upload-zone i { font-size: 2.2rem; color: var(--gold); display: block; margin-bottom: 12px; }
    .upload-zone p { color: var(--text-muted); font-size: 0.85rem; }
    .upload-zone input[type=file] { position: absolute; inset: 0; opacity: 0; cursor: pointer; width: 100%; height: 100%; }
    .upload-zone .file-chosen { margin-top: 8px; font-size: 0.8rem; color: var(--gold); font-weight: 600; }
    .tab-switch { display: flex; gap: 4px; background: var(--dark-3); padding: 4px; border-radius: 10px; width: fit-content; margin-bottom: 24px; }
    .tab-switch button { padding: 8px 20px; border-radius: 7px; border: none; cursor: pointer; font-size: 0.82rem; font-weight: 600; color: var(--text-muted); background: none; transition: var(--transition); }
    .tab-switch button.active { background: var(--gold); color: var(--dark); }
    .progress-upload { display: none; margin-top: 12px; }
    .progress-upload.show { display: block; }
    .progress-bar-bg { background: var(--dark-3); border-radius: 20px; height: 8px; overflow: hidden; }
    .progress-bar-fill { height: 100%; background: linear-gradient(90deg, var(--gold-dark), var(--gold)); width: 0; transition: width .3s; border-radius: 20px; }
    .progress-pct { font-size: 0.75rem; color: var(--text-muted); margin-top: 4px; text-align: center; }
    .media-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(190px, 1fr)); gap: 16px; }
    .media-card { background: var(--dark-card); border: 1px solid var(--border); border-radius: var(--radius); overflow: hidden; transition: var(--transition); }
    .media-card:hover { border-color: var(--gold); transform: translateY(-2px); box-shadow: var(--shadow-gold); }
    .media-thumb { aspect-ratio: 4/3; background: var(--dark-3); display: flex; align-items: center; justify-content: center; position: relative; overflow: hidden; }
    .media-thumb img { width: 100%; height: 100%; object-fit: cover; }
    .media-thumb .no-img { color: #333; font-size: 2rem; }
    .media-thumb .vid-badge { position: absolute; top: 8px; left: 8px; background: rgba(0,0,0,.75); color: #FFF; font-size: .65rem; padding: 3px 8px; border-radius: 4px; font-weight: 700; }
    .media-thumb .star-badge { position: absolute; top: 8px; right: 8px; background: var(--gold); color: var(--dark); font-size: .65rem; padding: 3px 8px; border-radius: 4px; }
    .media-body { padding: 12px; }
    .media-title { font-size: .82rem; color: var(--white); font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin-bottom: 2px; }
    .media-cat { font-size: .7rem; color: var(--gold); margin-bottom: 4px; }
    .media-date { font-size: .68rem; color: #555; margin-bottom: 10px; }
    .media-btns { display: flex; gap: 6px; }
    .btn-star { flex: 1; padding: 5px; border-radius: 6px; border: 1px solid var(--border); background: none; color: #666; cursor: pointer; font-size: .72rem; transition: var(--transition); }
    .btn-star.on, .btn-star:hover { border-color: var(--gold); color: var(--gold); background: rgba(212,175,55,.08); }
    .btn-del-m { padding: 5px 10px; border-radius: 6px; border: 1px solid rgba(239,68,68,.3); background: none; color: #EF5350; cursor: pointer; font-size: .72rem; transition: var(--transition); }
    .btn-del-m:hover { background: rgba(239,68,68,.15); }
    .filter-bar { display: flex; gap: 10px; flex-wrap: wrap; align-items: center; margin-bottom: 20px; }
    .filter-bar input { background: var(--dark-card); border: 1px solid var(--border); border-radius: 8px; padding: 8px 14px; color: var(--white); font-size: .82rem; outline: none; width: 220px; }
    .filter-bar input:focus { border-color: var(--gold); }
    .ftag { padding: 6px 14px; border-radius: 20px; border: 1px solid var(--border); background: none; color: #888; cursor: pointer; font-size: .75rem; transition: var(--transition); }
    .ftag.active, .ftag:hover { border-color: var(--gold); color: var(--gold); }
    .empty-state { text-align: center; padding: 60px 20px; color: var(--text-muted); }
    .empty-state i { font-size: 3rem; opacity: .2; display: block; margin-bottom: 16px; }
    .modal-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,.7); z-index: 2000; align-items: center; justify-content: center; }
    .modal-overlay.show { display: flex; }
    .modal-box { background: var(--dark-card); border: 1px solid var(--border); border-radius: 14px; padding: 28px; max-width: 400px; width: 90%; }
    .modal-box h3 { color: var(--white); margin-bottom: 10px; }
    .modal-box p { color: var(--text-muted); font-size: .88rem; margin-bottom: 20px; }
    .modal-btns { display: flex; gap: 10px; justify-content: flex-end; }
    @media(max-width:768px){ .sidebar { position:fixed; left:0; top:0; bottom:0; z-index:1000; transform:translateX(-100%); transition:var(--transition); } .sidebar.open { transform:translateX(0); } }
    .sidebar-overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,.6); z-index:999; }
    .sidebar-overlay.show { display:block; }
  </style>
</head>
<body>
<div class="sidebar-overlay" id="sidebarOverlay"></div>
<div class="admin-layout">

  <!-- SIDEBAR -->
  <?php $activePage = 'galerie'; include_once __DIR__ . '/../includes/admin-sidebar.php'; ?>

  <!-- MAIN -->
  <main class="admin-main">
    <!-- Topbar -->
    <div class="admin-topbar">
      <div style="display:flex;align-items:center;gap:12px">
        <button id="sidebarToggle" class="topbar-btn"><i class="fas fa-bars"></i></button>
        <div class="topbar-title">
          <h2>Gestion Galerie</h2>
          <p>Ajoutez et gérez vos photos et vidéos</p>
        </div>
      </div>
      <div class="topbar-actions">
        <button class="topbar-btn" onclick="location.reload()" title="Actualiser"><i class="fas fa-sync-alt"></i></button>
        <a href="../pages/galerie.php" target="_blank" class="topbar-btn" title="Voir la galerie publique"><i class="fas fa-external-link-alt"></i></a>
        <div class="admin-avatar">A</div>
      </div>
    </div>

    <div class="admin-content">

      <!-- Stats -->
      <div class="stats-grid" style="margin-bottom:24px">
        <div class="stat-card">
          <div class="stat-card-header">
            <div class="stat-card-icon gold"><i class="fas fa-images"></i></div>
          </div>
          <div class="stat-card-value"><?= $totalAll ?></div>
          <div class="stat-card-label">Total médias</div>
        </div>
        <div class="stat-card">
          <div class="stat-card-header">
            <div class="stat-card-icon" style="background:rgba(59,130,246,.1);color:#60A5FA"><i class="fas fa-camera"></i></div>
          </div>
          <div class="stat-card-value"><?= $totalPhotos ?></div>
          <div class="stat-card-label">Photos</div>
        </div>
        <div class="stat-card">
          <div class="stat-card-header">
            <div class="stat-card-icon" style="background:rgba(168,85,247,.1);color:#C084FC"><i class="fas fa-video"></i></div>
          </div>
          <div class="stat-card-value"><?= $totalVideos ?></div>
          <div class="stat-card-label">Vidéos</div>
        </div>
        <div class="stat-card">
          <div class="stat-card-header">
            <div class="stat-card-icon" style="background:rgba(37,211,102,.1);color:#25D366"><i class="fas fa-star"></i></div>
          </div>
          <div class="stat-card-value"><?= $totalVedette ?></div>
          <div class="stat-card-label">En vedette</div>
        </div>
      </div>

      <!-- Alertes -->
      <div class="alert alert-success" id="alertSuccess" style="display:none;margin-bottom:16px"><i class="fas fa-check-circle"></i> <span id="alertSuccessMsg"></span></div>
      <div class="alert alert-error"   id="alertError"   style="display:none;margin-bottom:16px"><i class="fas fa-exclamation-circle"></i> <span id="alertErrorMsg"></span></div>

      <!-- Upload -->
      <div class="chart-card" style="margin-bottom:24px">
        <h3 style="margin-bottom:4px"><i class="fas fa-cloud-upload-alt" style="color:var(--gold);margin-right:8px"></i>Ajouter un média</h3>
        <p style="font-size:.75rem;color:var(--text-muted);margin-bottom:20px">Uploadez une photo ou une vidéo dans la galerie</p>

        <div class="tab-switch" style="margin-bottom:20px">
          <button class="active" onclick="switchTab('photo',this)">
            <i class="fas fa-camera"></i> Photo
          </button>
          <button onclick="switchTab('video',this)">
            <i class="fas fa-video"></i> Vidéo
          </button>
        </div>
        <div id="tabInfo" style="background:rgba(212,175,55,.06);border:1px solid rgba(212,175,55,.2);border-radius:8px;padding:10px 14px;margin-bottom:20px;font-size:.8rem;color:var(--text-muted)">
          <i class="fas fa-camera" style="color:var(--gold);margin-right:6px"></i>
          <span id="tabInfoText">Mode photo — JPG, PNG, WEBP (max 5 Mo)</span>
        </div>

        <form id="uploadForm" enctype="multipart/form-data">
          <input type="hidden" name="type" id="mediaType" value="photo">
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px">
            <div class="form-group">
              <label class="form-label">Titre *</label>
              <input type="text" name="titre" class="form-control" placeholder="Ex : Mariage — Errachidia 2025" required>
            </div>
            <div class="form-group">
              <label class="form-label">Catégorie</label>
              <select name="categorie_id" class="form-control">
                <?php foreach ($categories as $cat): ?>
                <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['nom']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="form-group">
              <label class="form-label">Texte alternatif (SEO)</label>
              <input type="text" name="alt_text" class="form-control" placeholder="Description pour les moteurs de recherche">
            </div>
            <div class="form-group">
              <label class="form-label"><i class="fas fa-map-marker-alt" style="color:var(--gold);margin-right:4px"></i> Où afficher cette photo ?</label>
              <select name="emplacement" class="form-control" id="emplacementSelect">
                <option value="galerie">📸 Galerie publique uniquement</option>
                <option value="accueil">🏠 Page d'accueil uniquement (vedette)</option>
                <option value="les_deux">✨ Les deux (galerie + accueil)</option>
                <option value="services">🍽️ Section Services</option>
                <option value="packages">📦 Section Packages</option>
              </select>
            </div>
            <input type="hidden" name="en_vedette" id="enVedetteHidden" value="0">
          </div>

          <!-- Zone photo -->
          <div class="upload-zone" id="photoZone">
            <i class="fas fa-cloud-upload-alt"></i>
            <p>Glissez votre photo ici ou cliquez pour sélectionner</p>
            <p style="font-size:.72rem;color:#444;margin-top:4px">JPG, PNG, WEBP — max 5 Mo</p>
            <input type="file" name="fichier" accept="image/*" onchange="showFile(this,'photoZone','photoName')">
            <div class="file-chosen" id="photoName"></div>
          </div>

          <!-- Zone vidéo -->
          <div class="upload-zone" id="videoZone" style="display:none;border-color:rgba(192,132,252,.4);background:rgba(192,132,252,.03)">
            <i class="fas fa-film" style="color:#C084FC"></i>
            <p style="color:var(--white);font-weight:600">Glissez votre vidéo ici ou cliquez</p>
            <p style="font-size:.72rem;color:#555;margin-top:4px">MP4, WEBM, MOV — max 100 Mo</p>
            <input type="file" name="fichier" accept="video/mp4,video/webm,video/quicktime" onchange="showFile(this,'videoZone','videoName')">
            <div class="file-chosen" id="videoName"></div>
          </div>

          <!-- Miniature vidéo -->
          <div id="thumbZone" style="display:none;margin-top:12px">
            <label class="form-label" style="margin-bottom:8px;display:block">
              <i class="fas fa-image" style="color:var(--gold);margin-right:6px"></i>
              Miniature de la vidéo <span style="color:#555;font-weight:400">(optionnelle — image qui s'affiche avant lecture)</span>
            </label>
            <div class="upload-zone" style="border-style:dashed">
              <i class="fas fa-image"></i>
              <p>Sélectionner une image de couverture</p>
              <p style="font-size:.72rem;color:#444;margin-top:4px">JPG, PNG — max 2 Mo</p>
              <input type="file" name="miniature" accept="image/*" onchange="showFile(this,'thumbZone','thumbName')">
              <div class="file-chosen" id="thumbName"></div>
            </div>
          </div>

          <div class="progress-upload" id="progressUpload">
            <div class="progress-bar-bg"><div class="progress-bar-fill" id="progressFill"></div></div>
            <div class="progress-pct" id="progressPct">0%</div>
          </div>

          <div style="display:flex;align-items:center;justify-content:space-between;margin-top:20px;padding-top:16px;border-top:1px solid var(--border)">
            <div id="emplacementInfo" style="font-size:.8rem;color:var(--text-muted)">
              <i class="fas fa-info-circle" style="color:var(--gold)"></i>
              <span id="emplacementDesc">La photo sera visible dans la galerie publique</span>
            </div>
            <button type="submit" class="btn-primary" id="submitBtn" style="padding:10px 24px">
              <i class="fas fa-upload"></i> Ajouter le média
            </button>
          </div>
        </form>
      </div>

      <!-- Filtres -->
      <div class="filter-bar">
        <input type="text" id="searchInput" placeholder="🔍 Rechercher..." oninput="filterMedia()">
        <button class="ftag active" onclick="setFilter('all',this)">Tous (<?= $totalAll ?>)</button>
        <button class="ftag" onclick="setFilter('photo',this)">Photos (<?= $totalPhotos ?>)</button>
        <button class="ftag" onclick="setFilter('video',this)">Vidéos (<?= $totalVideos ?>)</button>
        <button class="ftag" onclick="setFilter('vedette',this)">⭐ Vedette (<?= $totalVedette ?>)</button>
      </div>

      <!-- Grille médias -->
      <?php if (empty($medias)): ?>
      <div class="empty-state">
        <i class="fas fa-images"></i>
        <p>Aucun média pour l'instant. Ajoutez votre première photo ci-dessus.</p>
      </div>
      <?php else: ?>
      <div class="media-grid" id="mediaGrid">
        <?php foreach ($medias as $m):
          $isVid = $m['type'] === 'video';
          $src   = '';
          if (!$isVid && $m['fichier']) $src = UPLOAD_URL.'/'.$m['fichier'];
          elseif ($isVid && $m['miniature']) $src = UPLOAD_URL.'/'.$m['miniature'];
          $date = date('d/m/Y', strtotime($m['created_at']));
        ?>
        <div class="media-card"
             data-type="<?= $m['type'] ?>"
             data-vedette="<?= $m['en_vedette'] ?>"
             data-title="<?= strtolower(htmlspecialchars($m['titre'] ?? '')) ?>">
          <div class="media-thumb">
            <?php if ($src): ?>
              <img src="<?= htmlspecialchars($src) ?>" alt="" loading="lazy">
            <?php else: ?>
              <i class="fas <?= $isVid ? 'fa-film' : 'fa-image' ?> no-img"></i>
            <?php endif; ?>
            <?php if ($isVid): ?><span class="vid-badge"><i class="fas fa-play"></i> VIDÉO</span><?php endif; ?>
            <?php if ($m['en_vedette']): ?><span class="star-badge"><i class="fas fa-star"></i></span><?php endif; ?>
          </div>
          <div class="media-body">
            <div class="media-title" title="<?= htmlspecialchars($m['titre'] ?? '') ?>"><?= htmlspecialchars($m['titre'] ?? 'Sans titre') ?></div>
            <div class="media-cat"><?= htmlspecialchars($m['categorie_nom'] ?? '—') ?></div>
            <div class="media-date"><?= $date ?></div>
            <div class="media-btns">
              <button class="btn-star <?= $m['en_vedette'] ? 'on' : '' ?>"
                      onclick="toggleVedette(<?= $m['id'] ?>,this)">
                <i class="fas fa-star"></i> Vedette
              </button>
              <button class="btn-del-m"
                      onclick="confirmDel(<?= $m['id'] ?>,'<?= addslashes($m['titre'] ?? '') ?>')">
                <i class="fas fa-trash"></i>
              </button>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>

    </div>
  </main>
</div>

<!-- Modal suppression -->
<div class="modal-overlay" id="delModal">
  <div class="modal-box">
    <h3><i class="fas fa-exclamation-triangle" style="color:#EF5350;margin-right:8px"></i>Confirmer la suppression</h3>
    <p id="delMsg">Voulez-vous vraiment supprimer ce média ? Action irréversible.</p>
    <div class="modal-btns">
      <button class="btn-secondary" onclick="closeDel()">Annuler</button>
      <button class="btn-primary" id="confirmDelBtn" style="background:linear-gradient(135deg,#C62828,#b71c1c)">
        <i class="fas fa-trash"></i> Supprimer
      </button>
    </div>
  </div>
</div>

<script>
const UPLOAD_URL  = '<?= SITE_URL ?>/api/upload_media.php';
const DELETE_URL  = '<?= SITE_URL ?>/api/delete_media.php';
const VEDETTE_URL = '<?= SITE_URL ?>/api/toggle_vedette.php';

// Sidebar toggle
document.getElementById('sidebarToggle').addEventListener('click', () => {
  document.getElementById('sidebar').classList.toggle('open');
  document.getElementById('sidebarOverlay').classList.toggle('show');
});
document.getElementById('sidebarOverlay').addEventListener('click', () => {
  document.getElementById('sidebar').classList.remove('open');
  document.getElementById('sidebarOverlay').classList.remove('show');
});

// Onglets
function switchTab(type, btn) {
  const info = document.getElementById('tabInfoText');
  const icon = document.querySelector('#tabInfo i');
  if (type === 'video') {
    info.textContent = 'Mode vidéo — MP4, WEBM (max 100 Mo) + miniature optionnelle';
    icon.className = 'fas fa-video';
    icon.style.color = 'var(--gold)';
    icon.style.marginRight = '6px';
  } else {
    info.textContent = 'Mode photo — JPG, PNG, WEBP (max 5 Mo)';
    icon.className = 'fas fa-camera';
    icon.style.color = 'var(--gold)';
    icon.style.marginRight = '6px';
  }
  document.getElementById('mediaType').value = type;
  document.querySelectorAll('.tab-switch button').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');
  document.getElementById('photoZone').style.display = type === 'photo' ? 'block' : 'none';
  document.getElementById('videoZone').style.display = type === 'video' ? 'block' : 'none';
  document.getElementById('thumbZone').style.display = type === 'video' ? 'block' : 'none';
}

function showFile(input, zoneId, nameId) {
  const name = input.files[0]?.name || '';
  document.getElementById(nameId).textContent = name ? '✅ ' + name : '';
  document.getElementById(zoneId).classList.toggle('dragover', !!name);
}

// Gestion emplacement
const emplacementDescs = {
  'galerie'   : '📸 Visible dans la galerie publique',
  'accueil'   : '🏠 Visible en vedette sur la page d\'accueil',
  'les_deux'  : '✨ Visible dans la galerie ET en vedette sur l\'accueil',
  'services'  : '🍽️ Visible dans la section Services',
  'packages'  : '📦 Visible dans la section Packages',
};
document.getElementById('emplacementSelect').addEventListener('change', function() {
  document.getElementById('emplacementDesc').textContent = emplacementDescs[this.value] || '';
  // Vedette = accueil ou les_deux
  document.getElementById('enVedetteHidden').value =
    (this.value === 'accueil' || this.value === 'les_deux') ? '1' : '0';
});

// Upload
document.getElementById('uploadForm').addEventListener('submit', function(e) {
  e.preventDefault();
  const btn  = document.getElementById('submitBtn');
  const prog = document.getElementById('progressUpload');
  const fill = document.getElementById('progressFill');
  const pct  = document.getElementById('progressPct');

  // Vérifier qu'un fichier est sélectionné
  const fileInput = document.getElementById('mediaType').value === 'photo'
    ? document.querySelector('#photoZone input[type=file]')
    : document.querySelector('#videoZone input[type=file]');

  if (!fileInput || !fileInput.files.length) {
    showAlert('error', 'Veuillez sélectionner un fichier avant de cliquer sur Ajouter.');
    return;
  }

  btn.disabled = true;
  btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Envoi...';
  prog.classList.add('show');

  const fd = new FormData(this);
  // S'assurer que en_vedette est bien dans le FormData
  fd.set('en_vedette', document.getElementById('enVedetteHidden').value);

  const xhr = new XMLHttpRequest();
  xhr.open('POST', UPLOAD_URL);
  xhr.upload.onprogress = e => {
    if (e.lengthComputable) {
      const p = Math.round(e.loaded / e.total * 100);
      fill.style.width = p + '%';
      pct.textContent  = p + '%';
    }
  };
  xhr.onload = () => {
    prog.classList.remove('show');
    fill.style.width = '0';
    btn.disabled = false;
    btn.innerHTML = '<i class="fas fa-upload"></i> Ajouter le média';
    try {
      const res = JSON.parse(xhr.responseText);
      if (res.success) {
        showAlert('success', res.message);
        setTimeout(() => location.reload(), 1500);
      } else {
        showAlert('error', '❌ ' + res.message);
      }
    } catch(err) {
      showAlert('error', 'Réponse inattendue du serveur : ' + xhr.responseText.substring(0, 200));
    }
  };
  xhr.onerror = () => {
    prog.classList.remove('show');
    btn.disabled = false;
    btn.innerHTML = '<i class="fas fa-upload"></i> Ajouter le média';
    showAlert('error', 'Erreur réseau — vérifiez que Apache est bien démarré.');
  };
  xhr.send(fd);
});

// Alertes
function showAlert(type, msg) {
  const id  = type === 'success' ? 'alertSuccess' : 'alertError';
  const mid = type === 'success' ? 'alertSuccessMsg' : 'alertErrorMsg';
  document.getElementById(mid).textContent = msg;
  document.getElementById(id).style.display = 'flex';
  setTimeout(() => document.getElementById(id).style.display = 'none', 5000);
}

// Vedette
function toggleVedette(id, btn) {
  fetch(VEDETTE_URL, {method:'POST', body: new URLSearchParams({id})})
    .then(r => r.json()).then(res => {
      if (!res.success) return;
      btn.classList.toggle('on', res.en_vedette);
      const card = btn.closest('.media-card');
      card.dataset.vedette = res.en_vedette ? '1' : '0';
      const badge = card.querySelector('.star-badge');
      if (res.en_vedette && !badge) card.querySelector('.media-thumb').insertAdjacentHTML('beforeend','<span class="star-badge"><i class="fas fa-star"></i></span>');
      else if (!res.en_vedette && badge) badge.remove();
    });
}

// Suppression
let delId = null;
function confirmDel(id, titre) { delId = id; document.getElementById('delMsg').textContent = `Supprimer "${titre}" ? Action irréversible.`; document.getElementById('delModal').classList.add('show'); }
function closeDel() { document.getElementById('delModal').classList.remove('show'); delId = null; }
document.getElementById('confirmDelBtn').addEventListener('click', () => {
  if (!delId) return;
  fetch(DELETE_URL, {method:'POST', body: new URLSearchParams({id: delId})})
    .then(r => r.json()).then(res => {
      closeDel();
      if (res.success) { showAlert('success', res.message); setTimeout(() => location.reload(), 1200); }
      else showAlert('error', res.message);
    });
});

// Filtres
let currentFilter = 'all';
function setFilter(f, btn) {
  currentFilter = f;
  document.querySelectorAll('.ftag').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');
  filterMedia();
}
function filterMedia() {
  const s = document.getElementById('searchInput').value.toLowerCase();
  document.querySelectorAll('.media-card').forEach(c => {
    const ok = (currentFilter==='all') || (currentFilter==='photo'&&c.dataset.type==='photo') || (currentFilter==='video'&&c.dataset.type==='video') || (currentFilter==='vedette'&&c.dataset.vedette==='1');
    c.style.display = (ok && (!s || c.dataset.title.includes(s))) ? '' : 'none';
  });
}

// Drag & drop visuel
document.querySelectorAll('.upload-zone').forEach(z => {
  z.addEventListener('dragover', e => { e.preventDefault(); z.classList.add('dragover'); });
  z.addEventListener('dragleave', () => z.classList.remove('dragover'));
  z.addEventListener('drop', e => { e.preventDefault(); z.classList.remove('dragover'); });
});
</script>
<script src="../js/admin-lang.js"></script>
</body>
</html>
