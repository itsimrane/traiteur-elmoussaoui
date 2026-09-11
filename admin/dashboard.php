<?php
require_once __DIR__ . '/../includes/config.php';
requireAdmin();

// ── Réservations ────────────────────────────────────────────────
$statsResaTotal = $statsResaAttente = $statsResaConf = $statsResaRefuse = 0;
try {
    $r = $pdo->query("SELECT statut, COUNT(*) n FROM reservations WHERE deleted_at IS NULL GROUP BY statut")->fetchAll();
    foreach ($r as $row) {
        $statsResaTotal += $row['n'];
        if ($row['statut'] === 'en_attente') $statsResaAttente = $row['n'];
        if ($row['statut'] === 'confirmee')  $statsResaConf = $row['n'];
        if ($row['statut'] === 'annulee')    $statsResaRefuse = $row['n'];
    }
} catch (Exception $e) {}

// ── Devis en attente ────────────────────────────────────────────
$statsDevisAttente = 0;
try { $statsDevisAttente = (int)$pdo->query("SELECT COUNT(*) FROM devis WHERE statut IN ('recu','en_traitement','envoye')")->fetchColumn(); } catch (Exception $e) {}

// ── Messages non lus ────────────────────────────────────────────
$statsMessages = 0;
try { $statsMessages = (int)$pdo->query("SELECT COUNT(*) FROM contacts WHERE statut='nouveau'")->fetchColumn(); } catch (Exception $e) {}

// ── Chiffre d'affaires / encaissé / reste ────────────────────────
$statsCA = $statsEncaisse = $statsReste = 0;
try { $statsCA = (float)$pdo->query("SELECT COALESCE(SUM(montant_ttc),0) FROM factures WHERE statut != 'annulee'")->fetchColumn(); } catch (Exception $e) {}
try { $statsEncaisse = (float)$pdo->query("SELECT COALESCE(SUM(montant),0) FROM paiements WHERE statut != 'annule'")->fetchColumn(); } catch (Exception $e) {}
$statsReste = max(0, $statsCA - $statsEncaisse);

// ── Clients ─────────────────────────────────────────────────────
$totalClients = 0;
try { $totalClients = (int)$pdo->query("SELECT COUNT(*) FROM clients WHERE deleted_at IS NULL")->fetchColumn(); } catch (Exception $e) {}

// ── Prochains événements (réservations confirmées à venir) ──────
$prochainsEvenements = [];
try {
    $pe = $pdo->query("
        SELECT r.id, r.date_evenement, r.heure_debut, r.nbr_invites, r.statut,
               c.prenom, c.nom, te.nom AS type_nom
        FROM reservations r
        LEFT JOIN clients c ON c.id = r.client_id
        LEFT JOIN types_evenements te ON te.id = r.type_evenement_id
        WHERE r.statut = 'confirmee' AND r.date_evenement >= CURDATE() AND r.deleted_at IS NULL
        ORDER BY r.date_evenement ASC LIMIT 6
    ");
    $prochainsEvenements = $pe->fetchAll();
} catch (Exception $e) {}

// ── Activité récente (agrégée depuis les vraies tables) ─────────
$activites = [];
try {
    $rs = $pdo->query("
        SELECT r.id, r.statut, r.created_at, c.prenom, c.nom, te.nom AS type_nom
        FROM reservations r LEFT JOIN clients c ON c.id=r.client_id
        LEFT JOIN types_evenements te ON te.id=r.type_evenement_id
        ORDER BY r.created_at DESC LIMIT 8
    ")->fetchAll();
    foreach ($rs as $r) {
        $nom = trim(($r['prenom']??'').' '.($r['nom']??''));
        $libelle = match($r['statut']) {
            'confirmee' => 'Réservation confirmée',
            'annulee'   => 'Réservation refusée/annulée',
            default     => 'Nouvelle réservation',
        };
        $activites[] = ['icon'=>'fa-calendar-check','color'=>'#FBB724','titre'=>$libelle,'desc'=>$nom.' — '.($r['type_nom']??''),'time'=>$r['created_at'],'lien'=>'reservation_details.php?id='.$r['id']];
    }
} catch (Exception $e) {}
try {
    $ds = $pdo->query("SELECT id, reference, created_at, client_id FROM devis ORDER BY created_at DESC LIMIT 5")->fetchAll();
    foreach ($ds as $d) {
        $activites[] = ['icon'=>'fa-file-invoice','color'=>'#60A5FA','titre'=>'Nouveau devis','desc'=>$d['reference'],'time'=>$d['created_at'],'lien'=>'devis.php'];
    }
} catch (Exception $e) {}
try {
    $ps = $pdo->query("SELECT montant, date_paiement, created_at FROM paiements ORDER BY created_at DESC LIMIT 5")->fetchAll();
    foreach ($ps as $p) {
        $activites[] = ['icon'=>'fa-credit-card','color'=>'#25D366','titre'=>'Paiement enregistré','desc'=>number_format($p['montant'],0,',',' ').' MAD','time'=>$p['created_at'],'lien'=>'paiements.php'];
    }
} catch (Exception $e) {}
try {
    $ms = $pdo->query("SELECT id, prenom, nom, created_at FROM contacts ORDER BY created_at DESC LIMIT 5")->fetchAll();
    foreach ($ms as $m) {
        $nom = trim(($m['prenom']??'').' '.($m['nom']??''));
        $activites[] = ['icon'=>'fa-envelope','color'=>'#EF5350','titre'=>'Nouveau message','desc'=>$nom,'time'=>$m['created_at'],'lien'=>'messages.php?id='.$m['id']];
    }
} catch (Exception $e) {}
try {
    $ts = $pdo->query("SELECT id, nom_client, created_at FROM temoignages WHERE statut='en_attente' ORDER BY created_at DESC LIMIT 3")->fetchAll();
    foreach ($ts as $t) {
        $activites[] = ['icon'=>'fa-star','color'=>'#D4AF37','titre'=>'Nouveau témoignage','desc'=>$t['nom_client'],'time'=>$t['created_at'],'lien'=>'temoignages-admin.php'];
    }
} catch (Exception $e) {}
usort($activites, fn($a,$b) => strtotime($b['time']) - strtotime($a['time']));
$activites = array_slice($activites, 0, 8);

function dashTimeAgo(string $time): string {
    $diff = time() - strtotime($time);
    if ($diff < 60) return "À l'instant";
    if ($diff < 3600) return floor($diff/60) . ' min';
    if ($diff < 86400) return floor($diff/3600) . 'h';
    if ($diff < 604800) return floor($diff/86400) . 'j';
    return date('d/m/Y', strtotime($time));
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1.0">
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
          <div class="stat-card-header"><div class="stat-card-icon gold"><i class="fas fa-calendar-check"></i></div></div>
          <div class="stat-card-value"><?= $statsResaTotal ?></div>
          <div class="stat-card-label">Total réservations</div>
        </div>
        <div class="stat-card">
          <div class="stat-card-header"><div class="stat-card-icon" style="background:rgba(251,183,36,.1);color:#FBB724"><i class="fas fa-hourglass-half"></i></div></div>
          <div class="stat-card-value"><?= $statsResaAttente ?></div>
          <div class="stat-card-label">En attente</div>
        </div>
        <div class="stat-card">
          <div class="stat-card-header"><div class="stat-card-icon" style="background:rgba(37,211,102,.1);color:#25D366"><i class="fas fa-check-circle"></i></div></div>
          <div class="stat-card-value"><?= $statsResaConf ?></div>
          <div class="stat-card-label">Confirmées</div>
        </div>
        <div class="stat-card">
          <div class="stat-card-header"><div class="stat-card-icon" style="background:rgba(239,68,68,.1);color:#EF5350"><i class="fas fa-times-circle"></i></div></div>
          <div class="stat-card-value"><?= $statsResaRefuse ?></div>
          <div class="stat-card-label">Refusées</div>
        </div>
        <div class="stat-card">
          <div class="stat-card-header"><div class="stat-card-icon" style="background:rgba(167,139,250,.1);color:#A78BFA"><i class="fas fa-file-invoice"></i></div></div>
          <div class="stat-card-value"><?= $statsDevisAttente ?></div>
          <div class="stat-card-label">Devis en attente</div>
        </div>
        <div class="stat-card">
          <div class="stat-card-header"><div class="stat-card-icon" style="background:rgba(239,68,68,.1);color:#EF5350"><i class="fas fa-envelope"></i></div></div>
          <div class="stat-card-value"><?= $statsMessages ?></div>
          <div class="stat-card-label">Messages non lus</div>
        </div>
        <div class="stat-card">
          <div class="stat-card-header"><div class="stat-card-icon" style="background:rgba(212,175,55,.1);color:var(--gold)"><i class="fas fa-chart-line"></i></div></div>
          <div class="stat-card-value" style="font-size:1.15rem" dir="ltr"><?= number_format($statsCA,0,',',' ') ?></div>
          <div class="stat-card-label">CA Total (MAD)</div>
        </div>
        <div class="stat-card">
          <div class="stat-card-header"><div class="stat-card-icon" style="background:rgba(37,211,102,.1);color:#25D366"><i class="fas fa-coins"></i></div></div>
          <div class="stat-card-value" style="font-size:1.15rem" dir="ltr"><?= number_format($statsEncaisse,0,',',' ') ?></div>
          <div class="stat-card-label">Encaissé (MAD)</div>
        </div>
        <div class="stat-card">
          <div class="stat-card-header"><div class="stat-card-icon" style="background:rgba(251,183,36,.1);color:#FBB724"><i class="fas fa-hand-holding-usd"></i></div></div>
          <div class="stat-card-value" style="font-size:1.15rem" dir="ltr"><?= number_format($statsReste,0,',',' ') ?></div>
          <div class="stat-card-label">Reste à payer (MAD)</div>
        </div>
        <div class="stat-card">
          <div class="stat-card-header"><div class="stat-card-icon" style="background:rgba(59,130,246,.1);color:#60A5FA"><i class="fas fa-users"></i></div></div>
          <div class="stat-card-value"><?= $totalClients ?></div>
          <div class="stat-card-label">Clients</div>
        </div>
      </div>

      <!-- Actions rapides -->
      <div class="quick-actions">
        <a href="galerie.php" class="quick-btn">
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

      <!-- Prochains événements + Activité récente -->
      <div class="dashboard-grid">
        <div class="dash-card">
          <div class="dash-card-header">
            <h3><i class="fas fa-calendar-star" style="color:var(--gold);margin-right:6px"></i>Prochains événements</h3>
            <a href="reservations.php" style="font-size:.75rem;color:var(--gold);text-decoration:none">Voir tout →</a>
          </div>
          <?php if (empty($prochainsEvenements)): ?>
          <div style="padding:30px;text-align:center;color:#555;font-size:.82rem">Aucun événement confirmé à venir</div>
          <?php else: foreach ($prochainsEvenements as $e):
            $nom = trim(($e['prenom']??'').' '.($e['nom']??''));
          ?>
          <a href="reservation_details.php?id=<?= $e['id'] ?>" class="dash-item" style="text-decoration:none;cursor:pointer">
            <div class="dash-item-left">
              <strong><?= date('d/m/Y', strtotime($e['date_evenement'])) ?> — <?= htmlspecialchars($e['type_nom'] ?: 'Événement') ?></strong>
              <span><?= htmlspecialchars($nom) ?> · <?= (int)$e['nbr_invites'] ?> invités · <?= substr($e['heure_debut'],0,5) ?></span>
            </div>
            <span class="badge-small" style="background:rgba(37,211,102,.15);color:#25D366">Confirmé</span>
          </a>
          <?php endforeach; endif; ?>
        </div>

        <div class="dash-card">
          <div class="dash-card-header">
            <h3><i class="fas fa-history" style="color:var(--gold);margin-right:6px"></i>Activité récente</h3>
            <a href="logs.php" style="font-size:.75rem;color:var(--gold);text-decoration:none">Voir tout →</a>
          </div>
          <?php if (empty($activites)): ?>
          <div style="padding:30px;text-align:center;color:#555;font-size:.82rem">Aucune activité récente</div>
          <?php else: foreach ($activites as $a): ?>
          <a href="<?= htmlspecialchars($a['lien']) ?>" class="dash-item" style="text-decoration:none;cursor:pointer">
            <div class="dash-item-left">
              <strong><i class="fas <?= $a['icon'] ?>" style="color:<?= $a['color'] ?>;margin-right:6px;font-size:.75rem"></i><?= htmlspecialchars($a['titre']) ?></strong>
              <span><?= htmlspecialchars($a['desc']) ?></span>
            </div>
            <span style="font-size:.68rem;color:#555"><?= dashTimeAgo($a['time']) ?></span>
          </a>
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