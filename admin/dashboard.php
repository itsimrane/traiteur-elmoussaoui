<?php
require_once __DIR__ . '/../includes/config.php';
requireAdmin();

// Stats rapides
try {
    $statsReservations = $pdo->query("SELECT COUNT(*) FROM reservations")->fetchColumn();
    $statsClients      = $pdo->query("SELECT COUNT(*) FROM clients WHERE deleted_at IS NULL")->fetchColumn();
    $statsMessages     = $pdo->query("SELECT COUNT(*) FROM contacts WHERE statut='nouveau'")->fetchColumn();
    $statsDevis        = $pdo->query("SELECT COUNT(*) FROM devis_generes")->fetchColumn();
    $statsFactures     = $pdo->query("SELECT COALESCE(SUM(montant_ttc),0) FROM factures")->fetchColumn();
    $recentDevis       = $pdo->query("SELECT * FROM devis_generes ORDER BY created_at DESC LIMIT 5")->fetchAll();
    $recentMessages    = $pdo->query("SELECT * FROM contacts ORDER BY created_at DESC LIMIT 5")->fetchAll();
} catch(Exception $e) {
    $statsReservations=$statsClients=$statsMessages=$statsDevis=$statsFactures=0;
    $recentDevis=$recentMessages=[];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title>Dashboard — Admin EL MOUSSAOUI</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600;700&family=Jost:wght@300;400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="../css/style.css">
  <style>
    body{overflow-x:hidden}
    .sidebar-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.6);z-index:999}
    .sidebar-overlay.show{display:block}
    @media(max-width:768px){.sidebar{position:fixed;left:0;top:0;bottom:0;z-index:1000;transform:translateX(-100%);transition:var(--transition)}.sidebar.open{transform:translateX(0)}}
    .quick-actions{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:28px}
    .quick-btn{background:var(--dark-card);border:1px solid var(--border);border-radius:12px;padding:18px;text-align:center;text-decoration:none;transition:var(--transition);display:flex;flex-direction:column;align-items:center;gap:8px}
    .quick-btn:hover{border-color:var(--gold);transform:translateY(-2px)}
    .quick-btn i{font-size:1.4rem;color:var(--gold)}
    .quick-btn span{font-size:.78rem;color:var(--text-muted)}
    .dashboard-grid{display:grid;grid-template-columns:1fr 1fr;gap:20px}
    .dash-card{background:var(--dark-card);border:1px solid var(--border);border-radius:var(--radius);overflow:hidden}
    .dash-card-header{padding:14px 18px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between}
    .dash-card-header h3{font-size:.85rem;font-weight:700;color:var(--white)}
    .dash-item{padding:12px 18px;border-bottom:1px solid rgba(255,255,255,.04);display:flex;align-items:center;justify-content:space-between;font-size:.82rem}
    .dash-item:last-child{border-bottom:none}
    .dash-item-left{display:flex;flex-direction:column;gap:2px}
    .dash-item-left strong{color:var(--white);font-size:.84rem}
    .dash-item-left span{color:#555;font-size:.72rem}
    .badge-small{padding:3px 9px;border-radius:12px;font-size:.68rem;font-weight:700}
    @media(max-width:900px){.dashboard-grid{grid-template-columns:1fr}.quick-actions{grid-template-columns:repeat(2,1fr)}}
  </style>
</head>
<body>
<div class="sidebar-overlay" id="sidebarOverlay"></div>
<div class="admin-layout">
<?php $activePage = 'dashboard'; include_once __DIR__ . '/../includes/admin-sidebar.php'; ?>
  <main class="admin-main">
    <div class="admin-topbar">
      <div style="display:flex;align-items:center;gap:12px">
        <button id="sidebarToggle" class="topbar-btn"><i class="fas fa-bars"></i></button>
        <div class="topbar-title">
          <h2 data-fr="Tableau de bord" data-ar="لوحة التحكم">Tableau de bord</h2>
          <p><span data-fr="Bienvenue," data-ar="مرحباً،">Bienvenue,</span> <?= htmlspecialchars($_SESSION['admin_nom'] ?? 'Admin') ?> — <?= date('d/m/Y H:i') ?></p>
        </div>
      </div>
      <div class="topbar-actions">
        <button class="topbar-btn" onclick="location.reload()"><i class="fas fa-sync-alt"></i></button>
        <a href="../pages/reservation.php" target="_blank" class="topbar-btn" title="Voir le formulaire devis"><i class="fas fa-external-link-alt"></i></a>
        <div class="admin-avatar"><?= strtoupper(substr($_SESSION['admin_nom']??'A',0,1)) ?></div>
      </div>
    </div>
    <div class="admin-content">

      <!-- Stats -->
      <div class="stats-grid" style="margin-bottom:24px">
        <div class="stat-card">
          <div class="stat-card-header"><div class="stat-card-icon gold"><i class="fas fa-file-invoice"></i></div></div>
          <div class="stat-card-value"><?= $statsDevis ?></div>
          <div class="stat-card-label" data-fr="Devis générés" data-ar="عروض الأسعار">Devis générés</div>
        </div>
        <div class="stat-card">
          <div class="stat-card-header"><div class="stat-card-icon" style="background:rgba(37,211,102,.1);color:#25D366"><i class="fas fa-calendar-check"></i></div></div>
          <div class="stat-card-value"><?= $statsReservations ?></div>
          <div class="stat-card-label" data-fr="Réservations" data-ar="الحجوزات">Réservations</div>
        </div>
        <div class="stat-card">
          <div class="stat-card-header"><div class="stat-card-icon" style="background:rgba(59,130,246,.1);color:#60A5FA"><i class="fas fa-users"></i></div></div>
          <div class="stat-card-value"><?= $statsClients ?></div>
          <div class="stat-card-label" data-fr="Clients" data-ar="العملاء">Clients</div>
        </div>
        <div class="stat-card">
          <div class="stat-card-header"><div class="stat-card-icon" style="background:rgba(239,68,68,.1);color:#EF5350"><i class="fas fa-envelope"></i></div></div>
          <div class="stat-card-value"><?= $statsMessages ?></div>
          <div class="stat-card-label" data-fr="Messages non lus" data-ar="رسائل غير مقروءة">Messages non lus</div>
        </div>
      </div>

      <!-- Actions rapides -->
      <div class="quick-actions">
        <a href="../pages/galerie.php?edit=1" class="quick-btn">
          <i class="fas fa-images"></i><span data-fr="Galerie" data-ar="معرض الصور">Galerie</span>
        </a>
        <a href="services-admin.php" class="quick-btn">
          <i class="fas fa-concierge-bell"></i><span data-fr="Services & Prix" data-ar="الخدمات والأسعار">Services & Prix</span>
        </a>
        <a href="devis.php" class="quick-btn">
          <i class="fas fa-file-invoice"></i><span data-fr="Devis reçus" data-ar="عروض الأسعار">Devis reçus</span>
        </a>
        <a href="messages.php" class="quick-btn">
          <i class="fas fa-envelope"></i><span>Messages<?= $statsMessages>0?" ($statsMessages)":'' ?></span>
        </a>
      </div>

      <!-- Derniers devis + messages -->
      <div class="dashboard-grid">
        <div class="dash-card">
          <div class="dash-card-header">
            <h3><i class="fas fa-file-invoice" style="color:var(--gold);margin-right:6px"></i><span data-fr="Derniers devis" data-ar="آخر عروض الأسعار">Derniers devis</span></h3>
            <a href="devis.php" style="font-size:.75rem;color:var(--gold);text-decoration:none" data-fr="Voir tout →" data-ar="← عرض الكل">Voir tout →</a>
          </div>
          <?php if (empty($recentDevis)): ?>
          <div style="padding:30px;text-align:center;color:#555;font-size:.82rem" data-fr="Aucun devis pour l'instant" data-ar="لا توجد عروض أسعار حالياً">Aucun devis pour l'instant</div>
          <?php else: foreach ($recentDevis as $d): ?>
          <div class="dash-item">
            <div class="dash-item-left">
              <strong><?= htmlspecialchars($d['nom_client']) ?></strong>
              <span><?= htmlspecialchars($d['type_evenement']??'') ?> · <?= date('d/m/Y',strtotime($d['created_at'])) ?></span>
            </div>
            <span style="color:var(--gold);font-weight:700;font-size:.85rem" dir="ltr">
              <?= number_format($d['montant_total'],0,',',' ') ?> MAD
            </span>
          </div>
          <?php endforeach; endif; ?>
        </div>

        <div class="dash-card">
          <div class="dash-card-header">
            <h3><i class="fas fa-envelope" style="color:var(--gold);margin-right:6px"></i><span data-fr="Derniers messages" data-ar="آخر الرسائل">Derniers messages</span></h3>
            <a href="messages.php" style="font-size:.75rem;color:var(--gold);text-decoration:none" data-fr="Voir tout →" data-ar="← عرض الكل">Voir tout →</a>
          </div>
          <?php if (empty($recentMessages)): ?>
          <div style="padding:30px;text-align:center;color:#555;font-size:.82rem" data-fr="Aucun message pour l'instant" data-ar="لا توجد رسائل حالياً">Aucun message pour l'instant</div>
          <?php else: foreach ($recentMessages as $m):
            $nom = trim(($m['prenom']??'').' '.($m['nom']??''));
          ?>
          <div class="dash-item">
            <div class="dash-item-left">
              <strong><?= htmlspecialchars($nom) ?></strong>
              <span><?= htmlspecialchars(mb_substr($m['message']??'',0,50)) ?>...</span>
            </div>
            <?php if ($m['statut']==='nouveau'): ?>
            <span class="badge-small" style="background:rgba(239,68,68,.15);color:#EF5350" data-fr="Nouveau" data-ar="جديد">Nouveau</span>
            <?php endif; ?>
          </div>
          <?php endforeach; endif; ?>
        </div>
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
<script src="../js/admin-lang.js"></script>
</body>
</html>
