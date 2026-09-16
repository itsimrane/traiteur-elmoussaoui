<?php
require_once __DIR__ . '/../includes/config.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_max') {
    $id  = (int)($_POST['id'] ?? 0);
    $max = trim($_POST['max_invites'] ?? '');
    $max = ($max === '') ? null : max(1, (int)$max);
    $pdo->prepare("UPDATE types_evenements SET max_invites = ? WHERE id = ?")->execute([$max, $id]);
    header('Location: types-evenements-admin.php?msg=Limite+mise+à+jour&type=success');
    exit;
}

$msg = $_GET['msg'] ?? ''; $msgType = $_GET['type'] ?? 'success';

$types = [];
try {
    $types = $pdo->query("SELECT * FROM types_evenements WHERE actif=1 ORDER BY ordre ASC")->fetchAll();
} catch (Exception $e) { $erreurBdd = $e->getMessage(); }
?>
<!DOCTYPE html>
<html lang="<?= adminLang() ?>" dir="<?= adminDir() ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title><?= t('capacite_evenements') ?> — Admin EL MOUSSAOUI</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600;700&family=Jost:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="../css/style.css">
<style>
  body{overflow-x:hidden}
  .sidebar-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.6);z-index:999}
  .sidebar-overlay.show{display:block}
  @media(max-width:768px){.sidebar{position:fixed;left:0;top:0;bottom:0;z-index:1000;transform:translateX(-100%);transition:var(--transition)}.sidebar.open{transform:translateX(0)}}
  .cap-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:16px}
  .cap-card{background:var(--dark-card);border:1px solid var(--border);border-radius:var(--radius);padding:20px}
  .cap-card h3{font-size:.95rem;color:var(--white);margin-bottom:14px;display:flex;align-items:center;gap:8px}
  .cap-card h3 i{color:var(--gold)}
  .cap-form{display:flex;flex-direction:column;gap:10px}
  .cap-form input{width:100%;box-sizing:border-box;background:var(--dark-3);border:1px solid var(--border);border-radius:8px;padding:10px 12px;color:var(--white);font-size:.95rem;text-align:center;font-weight:700}
  .cap-form button{width:100%;background:var(--gold);color:var(--dark);border:none;border-radius:8px;padding:10px 16px;font-weight:700;cursor:pointer;font-size:.82rem;white-space:nowrap}
  .cap-hint{font-size:.7rem;color:var(--text-muted);margin-top:8px}
</style>
</head>
<body>
<div class="sidebar-overlay" id="sidebarOverlay"></div>
<div class="admin-layout <?= adminRtlClass() ?>">
  <?php $activePage = 'types-evenements'; include_once __DIR__ . '/../includes/admin-sidebar.php'; ?>
  <main class="admin-main">
    <div class="admin-topbar">
      <div style="display:flex;align-items:center;gap:12px">
        <button id="sidebarToggle" class="topbar-btn"><i class="fas fa-bars"></i></button>
        <div class="topbar-title"><h2><?= t('capacite_evenements') ?></h2><p><?= tt("Nombre maximal d'invités autorisé pour chaque type",'العدد الأقصى المسموح به من الضيوف لكل نوع') ?></p></div>
      </div>
    </div>
    <div class="admin-content">
      <?php if ($msg): ?>
      <div class="alert alert-<?= $msgType==='success'?'success':'error' ?>" style="margin-bottom:20px">
        <i class="fas fa-check-circle"></i> <?= htmlspecialchars($msg) ?>
      </div>
      <?php endif; ?>
      <?php if (!empty($erreurBdd)): ?>
      <div class="alert alert-error" style="margin-bottom:20px"><?= tt('Erreur','خطأ') ?> : <?= htmlspecialchars($erreurBdd) ?></div>
      <?php endif; ?>

      <div class="cap-grid">
        <?php foreach ($types as $t): ?>
        <div class="cap-card">
          <h3><i class="fas <?= htmlspecialchars($t['icone'] ?: 'fa-star') ?>"></i> <?= htmlspecialchars($t['nom']) ?></h3>
          <form method="POST" class="cap-form">
            <input type="hidden" name="action" value="update_max">
            <input type="hidden" name="id" value="<?= $t['id'] ?>">
            <input type="number" name="max_invites" min="1" placeholder="<?= tt('Illimité','غير محدود') ?>" value="<?= htmlspecialchars($t['max_invites'] ?? '') ?>">
            <button type="submit"><i class="fas fa-save"></i> <?= t('enregistrer') ?></button>
          </form>
          <div class="cap-hint"><?= tt("Laisser vide = aucune limite pour ce type d'événement.",'اتركه فارغاً = لا يوجد حد لهذا النوع من المناسبات.') ?></div>
        </div>
        <?php endforeach; ?>
      </div>
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
</script>
</body>
</html>
