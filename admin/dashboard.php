<?php
require_once __DIR__ . '/../includes/config.php';
requireAdmin();

// ── Variables initialisées à 0 par sécurité ──────────────
$statsDevis        = 0;
$statsDevisAttente = 0;
$statsDevisConf    = 0;
$statsClients      = 0;
$totalClients      = 0;
$statsMessages     = 0;
$statsReservations = 0;
$statsCA           = 0;
$statsFactures     = 0;
$recentDevis       = [];
$recentMessages    = [];

try {
    $statsDevis        = (int)$pdo->query("SELECT COUNT(*) FROM devis_generes")->fetchColumn();
    $statsDevisAttente = (int)$pdo->query("SELECT COUNT(*) FROM devis_generes WHERE statut='en_attente'")->fetchColumn();
    $statsDevisConf    = (int)$pdo->query("SELECT COUNT(*) FROM devis_generes WHERE statut='confirme'")->fetchColumn();
    $statsMessages     = (int)$pdo->query("SELECT COUNT(*) FROM contacts WHERE statut='nouveau'")->fetchColumn();
    $statsReservations = $statsDevisAttente;

    try { $statsClients = (int)$pdo->query("SELECT COUNT(*) FROM clients")->fetchColumn(); } catch(Exception $e2){}
    try { $statsFactures = (float)$pdo->query("SELECT COALESCE(SUM(montant_ttc),0) FROM factures")->fetchColumn(); } catch(Exception $e2){}
    try {
        $statsCA = (float)$pdo->query("SELECT COALESCE(SUM(montant_total),0) FROM devis_generes WHERE statut='confirme'")->fetchColumn();
    } catch(Exception $e2){}
    try {
        $extra = (int)$pdo->query("SELECT COUNT(DISTINCT telephone) FROM devis_generes WHERE telephone IS NOT NULL AND telephone NOT IN (SELECT telephone FROM clients WHERE telephone IS NOT NULL)")->fetchColumn();
        $totalClients = $statsClients + $extra;
    } catch(Exception $e2){ $totalClients = $statsClients; }

    $recentDevis = $pdo->query("
        SELECT id, numero, nom_client, telephone, email,
               type_evenement, date_evenement, montant_total, statut, created_at
        FROM devis_generes ORDER BY created_at DESC LIMIT 6
    ")->fetchAll();

    $recentMessages = $pdo->query("
        SELECT id, CONCAT(COALESCE(prenom,''),' ',nom) as nom_client,
               email, telephone, sujet, message, statut, created_at
        FROM contacts ORDER BY created_at DESC LIMIT 5
    ")->fetchAll();

} catch(Exception $e) {
    error_log('Dashboard error: ' . $e->getMessage());
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
          <h2>Tableau de bord</h2>
          <p>Bienvenue, <?= htmlspecialchars($_SESSION['admin_nom'] ?? 'Admin') ?> — <?= date('d/m/Y H:i') ?></p>
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
          <div class="stat-card-value" dir="ltr"><?= $statsDevis ?></div>
          <div class="stat-card-label">Devis générés</div>
        </div>
        <div class="stat-card">
          <div class="stat-card-header"><div class="stat-card-icon" style="background:rgba(37,211,102,.1);color:#25D366"><i class="fas fa-calendar-check"></i></div></div>
          <div class="stat-card-value"><?= $statsDevisAttente ?></div>
          <div class="stat-card-label">Réservations</div>
        </div>
        <div class="stat-card">
          <div class="stat-card-header"><div class="stat-card-icon" style="background:rgba(59,130,246,.1);color:#60A5FA"><i class="fas fa-users"></i></div></div>
          <div class="stat-card-value"><?= $totalClients ?? $statsClients ?></div>
          <div class="stat-card-label">Clients</div>
        </div>
        <div class="stat-card">
          <div class="stat-card-header"><div class="stat-card-icon" style="background:rgba(239,68,68,.1);color:#EF5350"><i class="fas fa-envelope"></i></div></div>
          <div class="stat-card-value"><?= $statsMessages ?></div>
          <div class="stat-card-label">Messages non lus</div>
        </div>
      </div>

      <!-- Actions rapides -->
      <div class="quick-actions">
        <a href="../pages/galerie.php?edit=1" class="quick-btn">
          <i class="fas fa-images"></i><span>Galerie</span>
        </a>
        <a href="services-admin.php" class="quick-btn">
          <i class="fas fa-concierge-bell"></i><span>Services & Prix</span>
        </a>
        <a href="devis.php" class="quick-btn">
          <i class="fas fa-file-invoice"></i><span>Devis reçus</span>
        </a>
        <a href="messages.php" class="quick-btn">
          <i class="fas fa-envelope"></i><span>Messages<?= $statsMessages>0?" ($statsMessages)":'' ?></span>
        </a>
      </div>

      <!-- Derniers devis + messages -->
      <div class="dashboard-grid">
        <div class="dash-card">
          <div class="dash-card-header">
            <h3><i class="fas fa-file-invoice" style="color:var(--gold);margin-right:6px"></i>Derniers devis</h3>
            <a href="devis.php" style="font-size:.75rem;color:var(--gold);text-decoration:none">Voir tout →</a>
          </div>
          <?php if (empty($recentDevis)): ?>
          <div style="padding:30px;text-align:center;color:#555;font-size:.82rem">Aucun devis pour l'instant</div>
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
            <h3><i class="fas fa-envelope" style="color:var(--gold);margin-right:6px"></i>Derniers messages</h3>
            <a href="messages.php" style="font-size:.75rem;color:var(--gold);text-decoration:none">Voir tout →</a>
          </div>
          <?php if (empty($recentMessages)): ?>
          <div style="padding:30px;text-align:center;color:#555;font-size:.82rem">Aucun message pour l'instant</div>
          <?php else: foreach ($recentMessages as $m):
            $nom = trim(($m['prenom']??'').' '.($m['nom']??''));
          ?>
          <div class="dash-item">
            <div class="dash-item-left">
              <strong><?= htmlspecialchars($nom) ?></strong>
              <span><?= htmlspecialchars(mb_substr($m['message']??'',0,50)) ?>...</span>
            </div>
            <?php if ($m['statut']==='nouveau'): ?>
            <span class="badge-small" style="background:rgba(239,68,68,.15);color:#EF5350">Nouveau</span>
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
</body>
</html>
