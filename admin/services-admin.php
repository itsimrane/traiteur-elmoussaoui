<?php
require_once __DIR__ . '/../includes/config.php';
requireAdmin();

// Actions POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add' || $action === 'edit') {
        $data = [
            sanitize($_POST['nom']            ?? ''),
            sanitize($_POST['nom_ar']         ?? ''),
            sanitize($_POST['slug']           ?? strtolower(str_replace(' ','-',$_POST['nom']??''))),
            sanitize($_POST['description']    ?? ''),
            sanitize($_POST['description_ar'] ?? ''),
            (float)($_POST['prix_base']       ?? 0) ?: null,
            sanitize($_POST['unite']          ?? ''),
            sanitize($_POST['icone']          ?? 'fa-star'),
            (int)($_POST['ordre']             ?? 0),
            isset($_POST['actif']) ? 1 : 0,
        ];

        try {
            if ($action === 'add') {
                $pdo->prepare("
                    INSERT INTO services (nom,nom_ar,slug,description,description_ar,prix_base,unite,icone,ordre,actif)
                    VALUES (?,?,?,?,?,?,?,?,?,?)
                ")->execute($data);
                $msg = 'Service ajouté avec succès !';
            } else {
                $id = (int)($_POST['id'] ?? 0);
                $data[] = $id;
                $pdo->prepare("
                    UPDATE services SET nom=?,nom_ar=?,slug=?,description=?,description_ar=?,prix_base=?,unite=?,icone=?,ordre=?,actif=?,updated_at=NOW()
                    WHERE id=?
                ")->execute($data);
                $msg = 'Service mis à jour !';
            }
            $msgType = 'success';
        } catch(Exception $e) {
            $msg = 'Erreur : ' . $e->getMessage();
            $msgType = 'error';
        }
        header('Location: services-admin.php?msg='.urlencode($msg).'&type='.$msgType);
        exit;
    }

    if ($action === 'toggle') {
        $id = (int)($_POST['id'] ?? 0);
        $pdo->prepare("UPDATE services SET actif = NOT actif WHERE id=?")->execute([$id]);
        header('Location: services-admin.php');
        exit;
    }

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $pdo->prepare("DELETE FROM services WHERE id=?")->execute([$id]);
        header('Location: services-admin.php?msg=Service+supprimé&type=success');
        exit;
    }

    if ($action === 'reorder') {
        $ids = json_decode($_POST['ids'] ?? '[]', true);
        foreach ($ids as $ordre => $id) {
            $pdo->prepare("UPDATE services SET ordre=? WHERE id=?")->execute([$ordre+1, (int)$id]);
        }
        echo json_encode(['success' => true]);
        exit;
    }
}

$services = $pdo->query("SELECT * FROM services ORDER BY ordre ASC, id ASC")->fetchAll();
$total    = count($services);
$actifs   = count(array_filter($services, fn($s) => $s['actif']));

$msg     = $_GET['msg']  ?? '';
$msgType = $_GET['type'] ?? 'success';

// Icônes disponibles
$icones = [
    'fa-utensils'      => 'Restauration',
    'fa-paint-brush'   => 'Décoration',
    'fa-music'         => 'Animation',
    'fa-camera'        => 'Photo/Vidéo',
    'fa-car'           => 'Transport',
    'fa-birthday-cake' => 'Gâteaux',
    'fa-tent'          => 'Tentes',
    'fa-ring'          => 'Fiançailles',
    'fa-heart'         => 'Mariage',
    'fa-baby'          => 'Circoncision',
    'fa-briefcase'     => 'Événements Pro',
    'fa-star'          => 'Autre',
    'fa-concierge-bell'=> 'Service',
    'fa-glass-cheers'  => 'Réception',
    'fa-flower'        => 'Fleurs',
    'fa-microphone'    => 'Sonorisation',
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <link rel="icon" type="image/png" href="../assets/img/favicon-32.png">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Services — Admin EL MOUSSAOUI</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600;700&family=Jost:wght@300;400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="../css/style.css">
  <style>
    body{overflow-x:hidden}
    .sidebar-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.6);z-index:999}
    .sidebar-overlay.show{display:block}
    @media(max-width:768px){.sidebar{position:fixed;left:0;top:0;bottom:0;z-index:1000;transform:translateX(-100%);transition:var(--transition)}.sidebar.open{transform:translateX(0)}}

    /* Services grid */
    .services-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(320px,1fr));gap:18px}
    .service-card-admin{background:var(--dark-card);border:1px solid var(--border);border-radius:var(--radius);overflow:hidden;transition:var(--transition);cursor:grab}
    .service-card-admin:hover{border-color:rgba(212,175,55,.3);transform:translateY(-2px)}
    .service-card-admin.inactive{opacity:.5}
    .service-card-admin.dragging{opacity:.4;border-style:dashed;border-color:var(--gold)}
    .sc-header{padding:18px 20px;display:flex;align-items:center;gap:14px;border-bottom:1px solid var(--border)}
    .sc-icon{width:46px;height:46px;border-radius:12px;background:rgba(212,175,55,.12);display:flex;align-items:center;justify-content:center;font-size:1.2rem;color:var(--gold);flex-shrink:0}
    .sc-name{font-size:.95rem;font-weight:700;color:var(--white)}
    .sc-name-ar{font-size:.8rem;color:var(--gold);opacity:.7;margin-top:2px}
    .sc-drag{color:#333;margin-left:auto;font-size:.9rem;cursor:grab}
    .sc-body{padding:16px 20px}
    .sc-desc{font-size:.78rem;color:var(--text-muted);line-height:1.6;margin-bottom:12px;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
    .sc-meta{display:flex;gap:10px;flex-wrap:wrap;margin-bottom:14px}
    .sc-tag{background:var(--dark-3);padding:3px 10px;border-radius:6px;font-size:.72rem;color:var(--text-muted)}
    .sc-tag.price{color:var(--gold);border:1px solid rgba(212,175,55,.2)}
    .sc-actions{display:flex;gap:8px;padding-top:12px;border-top:1px solid var(--border)}
    .sc-btn{flex:1;padding:7px;border-radius:8px;border:1px solid var(--border);background:none;cursor:pointer;font-size:.75rem;color:var(--text-muted);transition:var(--transition);font-family:var(--ff-body);display:flex;align-items:center;justify-content:center;gap:5px}
    .sc-btn:hover{border-color:var(--gold);color:var(--gold)}
    .sc-btn.danger:hover{border-color:rgba(239,68,68,.4);color:#EF5350}
    .sc-btn.toggle-on{background:rgba(37,211,102,.1);border-color:rgba(37,211,102,.3);color:#25D366}
    .sc-btn.toggle-off{background:rgba(136,136,136,.1);border-color:rgba(136,136,136,.3);color:#888}

    /* Modal */
    .modal-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.75);z-index:2000;align-items:center;justify-content:center;padding:20px}
    .modal-overlay.show{display:flex}
    .modal-box{background:var(--dark-card);border:1px solid var(--border);border-radius:14px;width:100%;max-width:600px;max-height:90vh;overflow-y:auto}
    .modal-header{padding:18px 22px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;background:var(--dark-card);z-index:1}
    .modal-header h3{color:var(--white);font-size:.95rem}
    .modal-close{width:30px;height:30px;border-radius:7px;border:1px solid var(--border);background:none;color:var(--text-muted);cursor:pointer;display:flex;align-items:center;justify-content:center}
    .modal-close:hover{border-color:var(--gold);color:var(--gold)}
    .modal-body{padding:22px}
    .modal-footer{padding:14px 22px;border-top:1px solid var(--border);display:flex;gap:10px;justify-content:flex-end}
    .form-grid{display:grid;grid-template-columns:1fr 1fr;gap:12px}
    .form-full{grid-column:1/-1}
    .icon-grid{display:grid;grid-template-columns:repeat(6,1fr);gap:8px;margin-top:6px}
    .icon-opt{display:flex;flex-direction:column;align-items:center;gap:4px;padding:8px;border-radius:8px;border:1px solid var(--border);cursor:pointer;transition:var(--transition);font-size:.65rem;color:var(--text-muted)}
    .icon-opt:hover,.icon-opt.selected{border-color:var(--gold);background:rgba(212,175,55,.08);color:var(--gold)}
    .icon-opt i{font-size:1.1rem}

    /* Empty */
    .empty-state{text-align:center;padding:60px 20px;color:var(--text-muted)}
    .empty-state i{font-size:2.5rem;opacity:.2;display:block;margin-bottom:12px}

    /* Aperçu icône */
    .icon-preview{width:50px;height:50px;border-radius:12px;background:rgba(212,175,55,.12);display:flex;align-items:center;justify-content:center;font-size:1.3rem;color:var(--gold);margin:0 auto 8px}
  </style>
</head>
<body>
<div class="sidebar-overlay" id="sidebarOverlay"></div>
<div class="admin-layout">

  <?php $activePage = 'services'; include_once __DIR__ . '/../includes/admin-sidebar.php'; ?>

  <main class="admin-main">
    <div class="admin-topbar">
      <div style="display:flex;align-items:center;gap:12px">
        <button id="sidebarToggle" class="topbar-btn"><i class="fas fa-bars"></i></button>
        <div class="topbar-title"><h2 data-fr="Gestion Services" data-ar="إدارة الخدمات">Gestion Services</h2><p data-fr="Gérez les services proposés sur le site" data-ar="إدارة الخدمات المعروضة على الموقع">Gérez les services proposés sur le site</p></div>
      </div>
      <div class="topbar-actions">
        <button class="topbar-btn" onclick="location.reload()"><i class="fas fa-sync-alt"></i></button>
        <a href="../pages/services.php" target="_blank" class="topbar-btn" title="Voir les services publics"><i class="fas fa-external-link-alt"></i></a>
        <button class="btn-primary" style="padding:8px 18px;font-size:.82rem" onclick="openAdd()">
          <i class="fas fa-plus"></i> <span data-fr="Nouveau service" data-ar="خدمة جديدة">Nouveau service</span>
        </button>
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

      <!-- Stats -->
      <div class="stats-grid" style="margin-bottom:24px">
        <div class="stat-card">
          <div class="stat-card-header"><div class="stat-card-icon gold"><i class="fas fa-concierge-bell"></i></div></div>
          <div class="stat-card-value"><?= $total ?></div>
          <div class="stat-card-label" data-fr="Total services" data-ar="إجمالي الخدمات">Total services</div>
        </div>
        <div class="stat-card">
          <div class="stat-card-header"><div class="stat-card-icon" style="background:rgba(37,211,102,.1);color:#25D366"><i class="fas fa-check-circle"></i></div></div>
          <div class="stat-card-value"><?= $actifs ?></div>
          <div class="stat-card-label" data-fr="Actifs" data-ar="نشطة">Actifs</div>
        </div>
        <div class="stat-card">
          <div class="stat-card-header"><div class="stat-card-icon" style="background:rgba(239,68,68,.1);color:#EF5350"><i class="fas fa-eye-slash"></i></div></div>
          <div class="stat-card-value"><?= $total - $actifs ?></div>
          <div class="stat-card-label" data-fr="Inactifs" data-ar="غير نشطة">Inactifs</div>
        </div>
        <div class="stat-card">
          <div class="stat-card-header"><div class="stat-card-icon" style="background:rgba(59,130,246,.1);color:#60A5FA"><i class="fas fa-info-circle"></i></div></div>
          <div class="stat-card-value" style="font-size:.85rem;color:var(--text-muted)" data-fr="Glisser" data-ar="اسحب">Glisser</div>
          <div class="stat-card-label" data-fr="pour réordonner" data-ar="لإعادة الترتيب">pour réordonner</div>
        </div>
      </div>

      <!-- Info réordonnancement -->
      <div style="background:rgba(212,175,55,.06);border:1px dashed rgba(212,175,55,.2);border-radius:10px;padding:12px 16px;margin-bottom:20px;font-size:.8rem;color:var(--text-muted);display:flex;align-items:center;gap:8px">
        <i class="fas fa-grip-vertical" style="color:var(--gold)"></i>
        <span data-fr="Glissez-déposez les cartes pour changer l'ordre d'affichage sur le site public." data-ar="اسحب البطاقات لتغيير ترتيب العرض على الموقع العام.">Glissez-déposez les cartes pour changer l'ordre d'affichage sur le site public.</span>
        <button id="saveOrderBtn" class="btn-primary" style="padding:5px 14px;font-size:.75rem;margin-left:auto;display:none" onclick="saveOrder()">
          <i class="fas fa-save"></i> Sauvegarder l'ordre
        </button>
      </div>

      <!-- Grille services -->
      <?php if (empty($services)): ?>
      <div class="empty-state">
        <i class="fas fa-concierge-bell"></i>
        <p><span data-fr="Aucun service" data-ar="لا توجد خدمات">Aucun service</span> pour l'instant.</p>
        <button class="btn-primary" style="margin-top:16px" onclick="openAdd()">
          <i class="fas fa-plus"></i> Ajouter le premier service
        </button>
      </div>
      <?php else: ?>
      <div class="services-grid" id="servicesGrid">
        <?php foreach ($services as $s): ?>
        <div class="service-card-admin <?= $s['actif'] ? '' : 'inactive' ?>"
             data-id="<?= $s['id'] ?>"
             draggable="true">
          <div class="sc-header">
            <div class="sc-icon"><i class="fas <?= htmlspecialchars($s['icone'] ?? 'fa-star') ?>"></i></div>
            <div>
              <div class="sc-name"><?= htmlspecialchars($s['nom']) ?></div>
              <div class="sc-name-ar"><?= htmlspecialchars($s['nom_ar'] ?? '') ?></div>
            </div>
            <div class="sc-drag"><i class="fas fa-grip-vertical"></i></div>
          </div>
          <div class="sc-body">
            <div class="sc-desc"><?= htmlspecialchars($s['description'] ?? 'Aucune description') ?></div>
            <div class="sc-meta">
              <?php if ($s['prix_base']): ?>
              <span class="sc-tag price">
                <i class="fas fa-tag"></i> <?= number_format($s['prix_base'],0,',',' ') ?> MAD
                <?= $s['unite'] ? '/ ' . htmlspecialchars($s['unite']) : '' ?>
              </span>
              <?php endif; ?>
              <span class="sc-tag">Ordre : <?= $s['ordre'] ?></span>
              <span class="sc-tag" style="color:<?= $s['actif'] ? '#25D366' : '#EF5350' ?>">
                <?= $s['actif'] ? '<span data-fr="● Actif" data-ar="● نشط">● Actif</span>' : '<span data-fr="○ Inactif" data-ar="○ غير نشط">○ Inactif</span>' ?>
              </span>
            </div>
            <div class="sc-actions">
              <button class="sc-btn" onclick='openEdit(<?= json_encode($s) ?>)'>
                <i class="fas fa-edit"></i> <span data-fr="Modifier" data-ar="تعديل">Modifier</span>
              </button>
              <form method="POST" style="flex:1">
                <input type="hidden" name="action" value="toggle">
                <input type="hidden" name="id" value="<?= $s['id'] ?>">
                <button type="submit" class="sc-btn <?= $s['actif'] ? 'toggle-on' : 'toggle-off' ?>" style="width:100%">
                  <i class="fas fa-<?= $s['actif'] ? 'eye' : 'eye-slash' ?>"></i>
                  <span data-fr="<?= $s['actif'] ? 'Visible' : 'Masqué' ?>" data-ar="<?= $s['actif'] ? 'مرئي' : 'مخفي' ?>"><?= $s['actif'] ? 'Visible' : 'Masqué' ?></span>
                </button>
              </form>
              <form method="POST" onsubmit="return confirm('Supprimer ce service ?')">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?= $s['id'] ?>">
                <button type="submit" class="sc-btn danger"><i class="fas fa-trash"></i></button>
              </form>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>

    </div>
  </main>
</div>

<!-- Modal Ajouter / Modifier -->
<div class="modal-overlay" id="serviceModal">
  <div class="modal-box">
    <div class="modal-header">
      <h3 id="modalTitle"><i class="fas fa-plus-circle" style="color:var(--gold);margin-right:8px"></i><span data-fr="Nouveau service" data-ar="خدمة جديدة">Nouveau service</span></h3>
      <button class="modal-close" onclick="closeModal()"><i class="fas fa-times"></i></button>
    </div>
    <form method="POST">
      <input type="hidden" name="action" id="formAction" value="add">
      <input type="hidden" name="id" id="serviceId">
      <div class="modal-body">

        <!-- Aperçu icône -->
        <div style="text-align:center;margin-bottom:20px">
          <div class="icon-preview"><i class="fas fa-star" id="iconPreview"></i></div>
          <div style="font-size:.72rem;color:var(--text-muted)">Aperçu de l'icône</div>
        </div>

        <div class="form-grid" style="margin-bottom:14px">
          <div class="form-group">
            <label class="form-label">Nom FR *</label>
            <input type="text" name="nom" id="f_nom" class="form-control" required placeholder="Ex: Animation Musicale">
          </div>
          <div class="form-group">
            <label class="form-label">Nom AR</label>
            <input type="text" name="nom_ar" id="f_nom_ar" class="form-control" placeholder="مثال: الموسيقى" dir="rtl">
          </div>
          <div class="form-group form-full">
            <label class="form-label">Description FR</label>
            <textarea name="description" id="f_desc" class="form-control" rows="2" placeholder="Description du service..."></textarea>
          </div>
          <div class="form-group form-full">
            <label class="form-label">Description AR</label>
            <textarea name="description_ar" id="f_desc_ar" class="form-control" rows="2" dir="rtl" placeholder="وصف الخدمة..."></textarea>
          </div>
          <div class="form-group">
            <label class="form-label" data-fr="Prix de base (MAD)" data-ar="السعر الأساسي (MAD)">Prix de base (MAD)</label>
            <input type="number" name="prix_base" id="f_prix" class="form-control" placeholder="2000" min="0" step="50">
          </div>
          <div class="form-group">
            <label class="form-label">Unité</label>
            <select name="unite" id="f_unite" class="form-control">
              <option value="forfait">Forfait</option>
              <option value="par personne">Par personne</option>
              <option value="par heure">Par heure</option>
              <option value="par jour">Par jour</option>
              <option value="">Sans unité</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label" data-fr="Ordre d'affichage" data-ar="ترتيب العرض">Ordre d'affichage</label>
            <input type="number" name="ordre" id="f_ordre" class="form-control" value="0" min="0">
          </div>
          <div class="form-group" style="display:flex;align-items:center;gap:10px;padding-top:24px">
            <label class="toggle-row" style="margin:0">
              <label class="sw">
                <input type="checkbox" name="actif" id="f_actif" value="1" checked>
                <span class="sw-slider"></span>
              </label>
              <span class="sw-label" style="margin-left:8px">Service actif</span>
            </label>
          </div>
          <input type="hidden" name="icone" id="f_icone" value="fa-star">
        </div>

        <!-- Choix icône -->
        <div class="form-group form-full">
          <label class="form-label" data-fr="Icône Font Awesome" data-ar="أيقونة Font Awesome">Icône Font Awesome</label>
          <div class="icon-grid" id="iconGrid">
            <?php foreach ($icones as $cls => $lbl): ?>
            <div class="icon-opt" data-icon="<?= $cls ?>" onclick="selectIcon('<?= $cls ?>', this)" title="<?= $lbl ?>">
              <i class="fas <?= $cls ?>"></i>
              <span><?= $lbl ?></span>
            </div>
            <?php endforeach; ?>
          </div>
        </div>

      </div>
      <div class="modal-footer">
        <button type="button" class="btn-secondary" onclick="closeModal()">Annuler</button>
        <button type="submit" class="btn-primary"><i class="fas fa-save"></i> <span id="saveBtnTxt">Ajouter</span></button>
      </div>
    </form>
  </div>
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

// Sélection icône
function selectIcon(cls, el) {
  document.querySelectorAll('.icon-opt').forEach(o => o.classList.remove('selected'));
  el.classList.add('selected');
  document.getElementById('f_icone').value = cls;
  document.getElementById('iconPreview').className = 'fas ' + cls;
}

// Ouvrir modal ajouter
function openAdd() {
  document.getElementById('modalTitle').innerHTML = '<i class="fas fa-plus-circle" style="color:var(--gold);margin-right:8px"></i><span data-fr="Nouveau service" data-ar="خدمة جديدة">Nouveau service</span>';
  document.getElementById('formAction').value = 'add';
  document.getElementById('serviceId').value  = '';
  document.getElementById('saveBtnTxt').textContent = 'Ajouter';
  ['nom','nom_ar','desc','desc_ar','prix','ordre'].forEach(f => {
    const el = document.getElementById('f_' + f);
    if (el) el.value = f === 'ordre' ? '0' : '';
  });
  document.getElementById('f_unite').value = 'forfait';
  document.getElementById('f_actif').checked = true;
  selectIcon('fa-star', document.querySelector('[data-icon="fa-star"]'));
  document.getElementById('serviceModal').classList.add('show');
}

// Ouvrir modal modifier
function openEdit(s) {
  document.getElementById('modalTitle').innerHTML = '<i class="fas fa-edit" style="color:var(--gold);margin-right:8px"></i><span data-fr="Modifier le service" data-ar="تعديل الخدمة">Modifier le service</span>';
  document.getElementById('formAction').value = 'edit';
  document.getElementById('serviceId').value  = s.id;
  document.getElementById('saveBtnTxt').textContent = 'Enregistrer';
  document.getElementById('f_nom').value      = s.nom       || '';
  document.getElementById('f_nom_ar').value   = s.nom_ar    || '';
  document.getElementById('f_desc').value     = s.description    || '';
  document.getElementById('f_desc_ar').value  = s.description_ar || '';
  document.getElementById('f_prix').value     = s.prix_base || '';
  document.getElementById('f_unite').value    = s.unite     || 'forfait';
  document.getElementById('f_ordre').value    = s.ordre     || '0';
  document.getElementById('f_actif').checked  = s.actif == 1;
  const ico = s.icone || 'fa-star';
  const icoEl = document.querySelector(`[data-icon="${ico}"]`);
  if (icoEl) selectIcon(ico, icoEl);
  document.getElementById('serviceModal').classList.add('show');
}

function closeModal() { document.getElementById('serviceModal').classList.remove('show'); }

// Drag & drop réordonnancement
const grid = document.getElementById('servicesGrid');
if (grid) {
  let dragging = null;
  grid.querySelectorAll('.service-card-admin').forEach(card => {
    card.addEventListener('dragstart', () => {
      dragging = card;
      setTimeout(() => card.classList.add('dragging'), 0);
    });
    card.addEventListener('dragend', () => {
      card.classList.remove('dragging');
      dragging = null;
      document.getElementById('saveOrderBtn').style.display = 'inline-flex';
    });
    card.addEventListener('dragover', e => {
      e.preventDefault();
      if (!dragging || dragging === card) return;
      const rect = card.getBoundingClientRect();
      const mid  = rect.left + rect.width / 2;
      grid.insertBefore(dragging, e.clientX < mid ? card : card.nextSibling);
    });
  });
}

function saveOrder() {
  const ids = [...document.querySelectorAll('.service-card-admin')].map(c => c.dataset.id);
  fetch('services-admin.php', {
    method:'POST',
    headers:{'Content-Type':'application/x-www-form-urlencoded'},
    body: 'action=reorder&ids=' + encodeURIComponent(JSON.stringify(ids))
  }).then(r => r.json()).then(res => {
    if (res.success) {
      document.getElementById('saveOrderBtn').style.display = 'none';
      location.reload();
    }
  });
}
</script>
<script src="../js/admin-lang.js"></script>
</body>
</html>
