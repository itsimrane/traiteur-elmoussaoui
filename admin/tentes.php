<?php
require_once __DIR__ . '/../includes/config.php';
requireAdmin();

// ── Vérification table (au cas où la migration n'a pas encore tourné) ──
try { $pdo->query("SELECT 1 FROM tentes LIMIT 1"); }
catch (Exception $e) {
    die('La table `tentes` n\'existe pas encore. Lance d\'abord admin/migrate_tentes.php?confirm=TENTES2026');
}

$msg = $_GET['msg'] ?? ''; $msgType = $_GET['type'] ?? 'success';

// ── Actions ────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'save') {
        $id          = (int)($_POST['id'] ?? 0);
        $nom         = sanitize($_POST['nom'] ?? '');
        $longueur    = $_POST['longueur'] !== '' ? (float)$_POST['longueur'] : null;
        $largeur     = $_POST['largeur']  !== '' ? (float)$_POST['largeur']  : null;
        $capacite    = $_POST['capacite_max'] !== '' ? (int)$_POST['capacite_max'] : null;
        $description = sanitize($_POST['description'] ?? '');
        $actif       = isset($_POST['actif']) ? 1 : 0;
        $ordre       = (int)($_POST['ordre'] ?? 0);

        if (!$nom) { header('Location: tentes.php?msg=Le+nom+est+obligatoire&type=error'); exit; }

        // Upload photo (optionnel)
        $photoPath = null;
        if (!empty($_FILES['photo']['name']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['photo'];
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->file($file['tmp_name']);
            $allowed = ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp','image/gif'=>'gif'];
            if (!array_key_exists($mime, $allowed)) {
                header('Location: tentes.php?msg=Format+image+non+autorisé&type=error'); exit;
            }
            if ($file['size'] > MAX_FILE_SIZE) {
                header('Location: tentes.php?msg=Image+trop+lourde+(max+5+Mo)&type=error'); exit;
            }
            $dir = UPLOAD_PATH . DIRECTORY_SEPARATOR . 'tentes';
            if (!is_dir($dir)) mkdir($dir, 0755, true);
            $filename = 'tente_' . uniqid() . '.' . $allowed[$mime];
            if (move_uploaded_file($file['tmp_name'], $dir . DIRECTORY_SEPARATOR . $filename)) {
                $photoPath = 'tentes/' . $filename;
            }
        }

        try {
            if ($id > 0) {
                if ($photoPath) {
                    // Supprimer l'ancienne photo si remplacée
                    $old = $pdo->prepare("SELECT photo FROM tentes WHERE id=?");
                    $old->execute([$id]);
                    $oldPhoto = $old->fetchColumn();
                    if ($oldPhoto && file_exists(UPLOAD_PATH . DIRECTORY_SEPARATOR . $oldPhoto)) {
                        @unlink(UPLOAD_PATH . DIRECTORY_SEPARATOR . $oldPhoto);
                    }
                    $pdo->prepare("UPDATE tentes SET nom=?,photo=?,longueur=?,largeur=?,capacite_max=?,description=?,actif=?,ordre=?,updated_at=NOW() WHERE id=?")
                        ->execute([$nom,$photoPath,$longueur,$largeur,$capacite,$description,$actif,$ordre,$id]);
                } else {
                    $pdo->prepare("UPDATE tentes SET nom=?,longueur=?,largeur=?,capacite_max=?,description=?,actif=?,ordre=?,updated_at=NOW() WHERE id=?")
                        ->execute([$nom,$longueur,$largeur,$capacite,$description,$actif,$ordre,$id]);
                }
                $msg = 'Tente mise à jour.';
            } else {
                $pdo->prepare("INSERT INTO tentes (nom,photo,longueur,largeur,capacite_max,description,actif,ordre,created_at) VALUES (?,?,?,?,?,?,?,?,NOW())")
                    ->execute([$nom,$photoPath,$longueur,$largeur,$capacite,$description,$actif,$ordre]);
                $msg = 'Tente ajoutée.';
            }
            header('Location: tentes.php?msg='.urlencode($msg).'&type=success'); exit;
        } catch (Exception $e) {
            header('Location: tentes.php?msg='.urlencode('Erreur : '.$e->getMessage()).'&type=error'); exit;
        }
    }

    if ($action === 'toggle') {
        $pdo->prepare("UPDATE tentes SET actif = NOT actif WHERE id=?")->execute([(int)$_POST['id']]);
        header('Location: tentes.php'); exit;
    }

    if ($action === 'delete') {
        $id = (int)$_POST['id'];
        $old = $pdo->prepare("SELECT photo FROM tentes WHERE id=?"); $old->execute([$id]);
        $oldPhoto = $old->fetchColumn();
        if ($oldPhoto && file_exists(UPLOAD_PATH . DIRECTORY_SEPARATOR . $oldPhoto)) {
            @unlink(UPLOAD_PATH . DIRECTORY_SEPARATOR . $oldPhoto);
        }
        $pdo->prepare("UPDATE reservations SET tente_id=NULL WHERE tente_id=?")->execute([$id]);
        $pdo->prepare("DELETE FROM tentes WHERE id=?")->execute([$id]);
        header('Location: tentes.php?msg=Tente+supprimée&type=success'); exit;
    }
}

$tentes = $pdo->query("SELECT * FROM tentes ORDER BY ordre ASC, id ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="<?= adminLang() ?>" dir="<?= adminDir() ?>">
<head>
  <meta charset="UTF-8">
  <link rel="icon" type="image/png" href="../assets/img/favicon-32.png">
  <meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title><?= t('tentes') ?> — Admin EL MOUSSAOUI</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600;700&family=Jost:wght@300;400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="../css/style.css">
  <style>
    body{overflow-x:hidden}
    .sidebar-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.6);z-index:999}
    .sidebar-overlay.show{display:block}
    @media(max-width:768px){.sidebar{position:fixed;left:0;top:0;bottom:0;z-index:1000;transform:translateX(-100%);transition:var(--transition)}.sidebar.open{transform:translateX(0)}}
    .tentes-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:18px}
    .tente-card{background:var(--dark-card);border:1px solid var(--border);border-radius:var(--radius);overflow:hidden}
    .tente-card.inactive{opacity:.5}
    .tente-photo{width:100%;height:160px;background:var(--dark-3) center/cover no-repeat;display:flex;align-items:center;justify-content:center;color:#555;font-size:2rem}
    .tente-body{padding:16px 18px}
    .tente-name{font-size:1rem;font-weight:700;color:var(--white);margin-bottom:8px}
    .tente-meta{display:flex;gap:10px;flex-wrap:wrap;margin-bottom:10px}
    .tente-tag{background:var(--dark-3);padding:3px 10px;border-radius:6px;font-size:.72rem;color:var(--text-muted)}
    .tente-desc{font-size:.78rem;color:var(--text-muted);line-height:1.5;margin-bottom:12px;max-height:3.2em;overflow:hidden}
    .tente-actions{display:flex;gap:8px}
    .tente-actions button,.tente-actions a{flex:1;text-align:center;padding:8px;border-radius:8px;border:1px solid var(--border);background:transparent;color:var(--text-muted);cursor:pointer;font-size:.78rem;text-decoration:none}
    .tente-actions button:hover,.tente-actions a:hover{border-color:var(--gold);color:var(--gold)}
    .modal-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.7);z-index:2000;align-items:center;justify-content:center;padding:20px}
    .modal-overlay.show{display:flex}
    .modal-box{background:var(--dark-card);border:1px solid var(--border);border-radius:var(--radius);padding:26px;max-width:520px;width:100%;max-height:90vh;overflow-y:auto}
    .modal-box h3{color:var(--white);margin-bottom:18px}
    .form-row{display:grid;grid-template-columns:1fr 1fr;gap:14px}
  </style>
</head>
<body>
<div class="sidebar-overlay" id="sidebarOverlay"></div>
<div class="admin-layout <?= adminRtlClass() ?>">
  <?php $activePage = 'tentes'; include_once __DIR__ . '/../includes/admin-sidebar.php'; ?>
  <main class="admin-main">
    <div class="admin-topbar">
      <div style="display:flex;align-items:center;gap:12px">
        <button id="sidebarToggle" class="topbar-btn"><i class="fas fa-bars"></i></button>
        <div class="topbar-title"><h2><?= t('tentes') ?></h2><p><?= tt('Gestion des fiches tentes proposées lors du traitement des réservations','إدارة بطاقات الخيام المقترحة عند معالجة الحجوزات') ?></p></div>
      </div>
      <button class="topbar-btn" onclick="openModal()"><i class="fas fa-plus"></i> <?= tt('Nouvelle tente','خيمة جديدة') ?></button>
    </div>

    <div class="admin-content">
      <?php if ($msg): ?>
      <div class="alert alert-<?= $msgType ?>" style="margin-bottom:20px"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($msg) ?></div>
      <?php endif; ?>

      <?php if (count($tentes) < 4): ?>
      <div class="alert alert-error" style="margin-bottom:20px">
        <i class="fas fa-exclamation-circle"></i> <?= tt('Il n\'y a que','يوجد فقط') ?> <?= count($tentes) ?> <?= tt('tente(s) configurée(s) sur les 4 attendues. Ajoute les fiches manquantes.','خيمة/خيام مهيأة من أصل 4 متوقعة. أضف البطاقات الناقصة.') ?>
      </div>
      <?php endif; ?>

      <div class="tentes-grid">
        <?php foreach ($tentes as $t): ?>
        <div class="tente-card <?= $t['actif'] ? '' : 'inactive' ?>">
          <div class="tente-photo" style="<?= $t['photo'] ? "background-image:url('../assets/uploads/".htmlspecialchars($t['photo'])."')" : '' ?>">
            <?php if (!$t['photo']): ?><i class="fas fa-campground"></i><?php endif; ?>
          </div>
          <div class="tente-body">
            <div class="tente-name"><?= htmlspecialchars($t['nom']) ?></div>
            <div class="tente-meta">
              <span class="tente-tag" dir="ltr"><?= tt('Long.','الطول') ?> <?= $t['longueur'] !== null ? $t['longueur'].' m' : '—' ?></span>
              <span class="tente-tag" dir="ltr"><?= tt('Larg.','العرض') ?> <?= $t['largeur'] !== null ? $t['largeur'].' m' : '—' ?></span>
              <span class="tente-tag"><?= tt('Cap.','السعة') ?> <?= $t['capacite_max'] !== null ? $t['capacite_max'].' '.tt('pers.','شخص') : '—' ?></span>
            </div>
            <div class="tente-desc"><?= $t['description'] ? htmlspecialchars($t['description']) : '<em style="color:#555">' . tt('Aucune description','لا يوجد وصف') . '</em>' ?></div>
            <div class="tente-actions">
              <a href="#" onclick='openModal(<?= json_encode($t, JSON_HEX_APOS|JSON_HEX_QUOT) ?>);return false;'><i class="fas fa-edit"></i> <?= t('modifier') ?></a>
              <form method="POST" style="flex:1;display:contents">
                <input type="hidden" name="action" value="toggle">
                <input type="hidden" name="id" value="<?= $t['id'] ?>">
                <button type="submit"><i class="fas <?= $t['actif']?'fa-eye-slash':'fa-eye' ?>"></i> <?= $t['actif']?tt('Désactiver','تعطيل'):tt('Activer','تفعيل') ?></button>
              </form>
              <form method="POST" style="flex:1;display:contents" onsubmit="return confirm('<?= tt('Supprimer cette tente ?','هل تريد حذف هذه الخيمة؟') ?>')">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?= $t['id'] ?>">
                <button type="submit"><i class="fas fa-trash"></i></button>
              </form>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </main>
</div>

<!-- Modal ajout/édition -->
<div class="modal-overlay" id="modalOverlay">
  <div class="modal-box">
    <h3 id="modalTitle"><?= tt('Nouvelle tente','خيمة جديدة') ?></h3>
    <form method="POST" enctype="multipart/form-data">
      <input type="hidden" name="action" value="save">
      <input type="hidden" name="id" id="f_id" value="">
      <div class="form-group"><label class="form-label"><?= tt('Nom de la tente','اسم الخيمة') ?> *</label>
        <input type="text" name="nom" id="f_nom" class="form-control" required></div>
      <div class="form-row">
        <div class="form-group"><label class="form-label"><?= tt('Longueur (m)','الطول (م)') ?></label>
          <input type="number" step="0.1" name="longueur" id="f_longueur" class="form-control"></div>
        <div class="form-group"><label class="form-label"><?= tt('Largeur (m)','العرض (م)') ?></label>
          <input type="number" step="0.1" name="largeur" id="f_largeur" class="form-control"></div>
      </div>
      <div class="form-group"><label class="form-label"><?= tt('Capacité maximale (personnes)','السعة القصوى (أشخاص)') ?></label>
        <input type="number" name="capacite_max" id="f_capacite" class="form-control"></div>
      <div class="form-group"><label class="form-label"><?= tt('Description','الوصف') ?></label>
        <textarea name="description" id="f_description" class="form-control" rows="3"></textarea></div>
      <div class="form-group"><label class="form-label"><?= tt('Photo','الصورة') ?></label>
        <input type="file" name="photo" accept="image/*" class="form-control"></div>
      <div class="form-row">
        <div class="form-group"><label class="form-label"><?= tt("Ordre d'affichage",'ترتيب العرض') ?></label>
          <input type="number" name="ordre" id="f_ordre" class="form-control" value="0"></div>
        <div class="form-group" style="display:flex;align-items:end;gap:8px">
          <label style="display:flex;align-items:center;gap:8px;font-size:.85rem;color:var(--text-muted)">
            <input type="checkbox" name="actif" id="f_actif" checked> <?= tt('Tente active','خيمة نشطة') ?></label>
        </div>
      </div>
      <div style="display:flex;gap:10px;margin-top:16px">
        <button type="button" class="btn-back" onclick="closeModal()" style="flex:1"><?= t('annuler') ?></button>
        <button type="submit" class="btn-primary" style="flex:1"><?= t('enregistrer') ?></button>
      </div>
    </form>
  </div>
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
function openModal(t) {
  document.getElementById('modalTitle').textContent = t ? 'Modifier ' + t.nom : 'Nouvelle tente';
  document.getElementById('f_id').value = t ? t.id : '';
  document.getElementById('f_nom').value = t ? t.nom : '';
  document.getElementById('f_longueur').value = t ? (t.longueur ?? '') : '';
  document.getElementById('f_largeur').value = t ? (t.largeur ?? '') : '';
  document.getElementById('f_capacite').value = t ? (t.capacite_max ?? '') : '';
  document.getElementById('f_description').value = t ? (t.description ?? '') : '';
  document.getElementById('f_ordre').value = t ? t.ordre : 0;
  document.getElementById('f_actif').checked = t ? !!parseInt(t.actif) : true;
  document.getElementById('modalOverlay').classList.add('show');
}
function closeModal(){ document.getElementById('modalOverlay').classList.remove('show'); }
</script>
</body>
</html>
