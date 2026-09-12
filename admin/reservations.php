<?php
require_once __DIR__ . '/../includes/config.php';
requireAdmin();

// ── Actions (POST) ────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id     = (int)($_POST['id'] ?? 0);

    if ($action === 'update_statut' && $id) {
        $statut = sanitize($_POST['statut'] ?? '');
        $allowed = ['en_attente','confirmee','en_cours','terminee','annulee'];
        if (in_array($statut, $allowed, true)) {
            $pdo->prepare("UPDATE reservations SET statut=?, updated_at=NOW() WHERE id=?")->execute([$statut, $id]);
        }
        header('Location: reservations.php?msg=Statut+mis+à+jour&type=success'); exit;
    }

    if ($action === 'delete' && $id) {
        // Soft delete : on marque deleted_at au lieu de supprimer réellement,
        // pour ne jamais perdre d'historique client.
        $pdo->prepare("UPDATE reservations SET deleted_at=NOW() WHERE id=?")->execute([$id]);
        header('Location: reservations.php?msg=Réservation+supprimée&type=success'); exit;
    }
}

// ── Filtres ────────────────────────────────────────────────────
$filtreStatut = $_GET['statut'] ?? '';
$recherche    = trim($_GET['q'] ?? '');
$filtreDate   = $_GET['date'] ?? '';
$filtreType   = $_GET['evt_type'] ?? '';

$where  = ['r.deleted_at IS NULL'];
$params = [];

if ($filtreStatut && in_array($filtreStatut, ['en_attente','confirmee','en_cours','terminee','annulee'], true)) {
    $where[] = 'r.statut = ?';
    $params[] = $filtreStatut;
}
if ($recherche !== '') {
    $where[] = "(c.nom LIKE ? OR c.prenom LIKE ? OR c.telephone LIKE ? OR c.email LIKE ?)";
    $like = '%' . $recherche . '%';
    array_push($params, $like, $like, $like, $like);
}
if ($filtreDate !== '') {
    $where[] = 'r.date_evenement = ?';
    $params[] = $filtreDate;
}
if ($filtreType !== '') {
    $where[] = 'r.type_evenement_id = ?';
    $params[] = (int)$filtreType;
}

$whereSql = implode(' AND ', $where);

// ── Liste des réservations ──────────────────────────────────────
$reservations = [];
try {
    $stmt = $pdo->prepare("
        SELECT r.*, c.nom AS c_nom, c.prenom AS c_prenom, c.telephone AS c_tel, c.email AS c_email,
               te.nom AS type_nom,
               (SELECT GROUP_CONCAT(dl.designation SEPARATOR ', ')
                  FROM devis d LEFT JOIN devis_lignes dl ON dl.devis_id = d.id
                 WHERE d.reservation_id = r.id LIMIT 1) AS services_resume
        FROM reservations r
        LEFT JOIN clients c ON c.id = r.client_id
        LEFT JOIN types_evenements te ON te.id = r.type_evenement_id
        WHERE $whereSql
        ORDER BY r.created_at DESC
    ");
    $stmt->execute($params);
    $reservations = $stmt->fetchAll();
} catch (Exception $e) {
    $erreurBdd = $e->getMessage();
}

// ── Types d'événements pour le filtre ───────────────────────────
$typesEvenements = [];
try { $typesEvenements = $pdo->query("SELECT id, nom FROM types_evenements WHERE actif=1 ORDER BY ordre ASC")->fetchAll(); } catch (Exception $e) {}

// ── Compteurs (toujours sur l'ensemble, indépendants des filtres actifs) ──
$compteurs = ['total'=>0,'en_attente'=>0,'confirmee'=>0,'annulee'=>0];
try {
    $c = $pdo->query("SELECT statut, COUNT(*) n FROM reservations WHERE deleted_at IS NULL GROUP BY statut")->fetchAll();
    foreach ($c as $row) {
        $compteurs['total'] += $row['n'];
        if (isset($compteurs[$row['statut']])) $compteurs[$row['statut']] = $row['n'];
    }
} catch (Exception $e) {}

$statutConfig = [
    'en_attente' => ['label'=>'En attente', 'color'=>'#FBB724','bg'=>'rgba(251,183,36,.15)'],
    'confirmee'  => ['label'=>'Confirmée',  'color'=>'#25D366','bg'=>'rgba(37,211,102,.15)'],
    'en_cours'   => ['label'=>'En cours',   'color'=>'#60A5FA','bg'=>'rgba(59,130,246,.15)'],
    'terminee'   => ['label'=>'Terminée',   'color'=>'#888',   'bg'=>'rgba(136,136,136,.15)'],
    'annulee'    => ['label'=>'Annulée',    'color'=>'#EF5350','bg'=>'rgba(239,68,68,.15)'],
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <link rel="icon" type="image/png" href="../assets/img/favicon-32.png">
  <meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title>Réservations — Admin EL MOUSSAOUI</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600;700&family=Jost:wght@300;400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="../css/style.css">
  <style>
    body{overflow-x:hidden}
    .sidebar-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.6);z-index:999}
    .sidebar-overlay.show{display:block}
    @media(max-width:768px){.sidebar{position:fixed;left:0;top:0;bottom:0;z-index:1000;transform:translateX(-100%);transition:var(--transition)}.sidebar.open{transform:translateX(0)}}
    .filters-bar{display:flex;flex-wrap:wrap;gap:10px;align-items:center;margin-bottom:20px;background:var(--dark-card);border:1px solid var(--border);border-radius:var(--radius);padding:16px}
    .filters-bar form{display:contents}
    .filters-bar input,.filters-bar select{background:var(--dark-3);border:1px solid var(--border);border-radius:8px;padding:8px 12px;color:var(--white);font-size:.8rem;font-family:inherit}
    .filters-bar input[type=search]{min-width:180px}
    .filters-bar button{background:var(--gold);color:var(--dark);border:none;border-radius:8px;padding:8px 16px;font-weight:700;font-size:.78rem;cursor:pointer}
    .tfilter{padding:7px 14px;border-radius:20px;border:1px solid var(--border);background:transparent;color:var(--text-muted);font-size:.75rem;cursor:pointer;text-decoration:none;display:inline-block}
    .tfilter.active{background:rgba(212,175,55,.12);border-color:var(--gold);color:var(--gold)}
    .resa-table{width:100%;border-collapse:collapse}
    .resa-table th{text-align:left;padding:10px 14px;font-size:.68rem;text-transform:uppercase;letter-spacing:.5px;color:var(--text-muted);border-bottom:1px solid var(--border)}
    .resa-table td{padding:12px 14px;font-size:.82rem;border-bottom:1px solid rgba(255,255,255,.04);vertical-align:top}
    .resa-table tr:hover{background:rgba(212,175,55,.03)}
    .badge-statut{padding:4px 12px;border-radius:20px;font-size:.7rem;font-weight:700;display:inline-block}
    .act-icons{display:flex;gap:6px}
    .act-icons a,.act-icons button{width:30px;height:30px;border-radius:8px;border:1px solid var(--border);background:transparent;color:var(--text-muted);display:flex;align-items:center;justify-content:center;cursor:pointer;text-decoration:none;font-size:.78rem}
    .act-icons a:hover,.act-icons button:hover{border-color:var(--gold);color:var(--gold)}
    .act-icons .ok:hover{border-color:#25D366;color:#25D366}
    .act-icons .no:hover{border-color:#EF5350;color:#EF5350}
    .svc-resume{font-size:.72rem;color:#666;max-width:180px;display:block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
    @media(max-width:900px){.resa-table{display:block;overflow-x:auto;white-space:nowrap}}
  </style>
</head>
<body>
<div class="sidebar-overlay" id="sidebarOverlay"></div>
<div class="admin-layout">
  <?php $activePage = 'reservations'; include_once __DIR__ . '/../includes/admin-sidebar.php'; ?>
  <main class="admin-main">
    <div class="admin-topbar">
      <div style="display:flex;align-items:center;gap:12px">
        <button id="sidebarToggle" class="topbar-btn"><i class="fas fa-bars"></i></button>
        <div class="topbar-title"><h2>Réservations</h2><p>Gestion complète des demandes d'événements</p></div>
      </div>
    </div>

    <div class="admin-content">
      <?php if (!empty($_GET['msg'])): ?>
      <div class="alert alert-<?= ($_GET['type'] ?? '') === 'success' ? 'success' : 'error' ?>" style="margin-bottom:20px">
        <i class="fas fa-check-circle"></i> <?= htmlspecialchars($_GET['msg']) ?>
      </div>
      <?php endif; ?>
      <?php if (!empty($erreurBdd)): ?>
      <div class="alert alert-error" style="margin-bottom:20px">
        <i class="fas fa-exclamation-circle"></i> Erreur base de données : <?= htmlspecialchars($erreurBdd) ?>
      </div>
      <?php endif; ?>

      <!-- Compteurs -->
      <div class="stats-grid" style="margin-bottom:20px">
        <div class="stat-card"><div class="stat-card-value"><?= $compteurs['total'] ?></div><div class="stat-card-label">Total</div></div>
        <div class="stat-card"><div class="stat-card-value" style="color:#FBB724"><?= $compteurs['en_attente'] ?></div><div class="stat-card-label">En attente</div></div>
        <div class="stat-card"><div class="stat-card-value" style="color:#25D366"><?= $compteurs['confirmee'] ?></div><div class="stat-card-label">Confirmées</div></div>
        <div class="stat-card"><div class="stat-card-value" style="color:#EF5350"><?= $compteurs['annulee'] ?></div><div class="stat-card-label">Annulées</div></div>
      </div>

      <!-- Filtres statut -->
      <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:16px">
        <a href="?statut=" class="tfilter <?= $filtreStatut==='' ? 'active':'' ?>">Toutes</a>
        <a href="?statut=en_attente" class="tfilter <?= $filtreStatut==='en_attente' ? 'active':'' ?>">En attente</a>
        <a href="?statut=confirmee" class="tfilter <?= $filtreStatut==='confirmee' ? 'active':'' ?>">Confirmées</a>
        <a href="?statut=en_cours" class="tfilter <?= $filtreStatut==='en_cours' ? 'active':'' ?>">En cours</a>
        <a href="?statut=terminee" class="tfilter <?= $filtreStatut==='terminee' ? 'active':'' ?>">Terminées</a>
        <a href="?statut=annulee" class="tfilter <?= $filtreStatut==='annulee' ? 'active':'' ?>">Annulées</a>
      </div>

      <!-- Recherche / filtres avancés -->
      <div class="filters-bar">
        <form method="GET" style="display:contents">
          <input type="hidden" name="statut" value="<?= htmlspecialchars($filtreStatut) ?>">
          <input type="search" name="q" placeholder="🔍 Nom ou téléphone..." value="<?= htmlspecialchars($recherche) ?>">
          <input type="date" name="date" value="<?= htmlspecialchars($filtreDate) ?>">
          <select name="evt_type">
            <option value="">Tous types d'événement</option>
            <?php foreach ($typesEvenements as $t): ?>
            <option value="<?= $t['id'] ?>" <?= $filtreType == $t['id'] ? 'selected':'' ?>><?= htmlspecialchars($t['nom']) ?></option>
            <?php endforeach; ?>
          </select>
          <button type="submit"><i class="fas fa-filter"></i> Filtrer</button>
          <?php if ($recherche || $filtreDate || $filtreType): ?>
          <a href="?statut=<?= htmlspecialchars($filtreStatut) ?>" style="font-size:.78rem;color:var(--text-muted)">✕ Réinitialiser</a>
          <?php endif; ?>
        </form>
      </div>

      <!-- Tableau -->
      <div class="dash-card">
        <?php if (empty($reservations)): ?>
        <div style="padding:60px 20px;text-align:center;color:#555">
          <i class="fas fa-calendar-times" style="font-size:2.5rem;opacity:.2;display:block;margin-bottom:14px"></i>
          Aucune réservation ne correspond à ces critères.
        </div>
        <?php else: ?>
        <table class="resa-table">
          <thead>
            <tr>
              <th>#</th><th>Client</th><th>Événement</th><th>Date</th><th>Invités</th>
              <th>Lieu</th><th>Services</th><th>Statut</th><th>Reçu le</th><th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($reservations as $r):
              $sc = $statutConfig[$r['statut']] ?? $statutConfig['en_attente'];
              $nomClient = trim(($r['c_prenom'] ?? '').' '.($r['c_nom'] ?? '')) ?: 'Client #'.$r['client_id'];
            ?>
            <tr>
              <td>#<?= $r['id'] ?></td>
              <td>
                <strong style="color:var(--white);display:block"><?= htmlspecialchars($nomClient) ?></strong>
                <span style="font-size:.72rem;color:#666"><?= htmlspecialchars($r['c_tel'] ?? '—') ?></span>
              </td>
              <td><?= htmlspecialchars($r['type_nom'] ?? '—') ?></td>
              <td><?= $r['date_evenement'] ? date('d/m/Y', strtotime($r['date_evenement'])) : '—' ?><br><span style="font-size:.72rem;color:#666"><?= substr($r['heure_debut'],0,5) ?></span></td>
              <td><?= (int)$r['nbr_invites'] ?></td>
              <td><?= htmlspecialchars($r['lieu'] ?: '—') ?></td>
              <td><span class="svc-resume" title="<?= htmlspecialchars($r['services_resume'] ?? '') ?>"><?= htmlspecialchars($r['services_resume'] ?: '—') ?></span></td>
              <td><span class="badge-statut" style="background:<?= $sc['bg'] ?>;color:<?= $sc['color'] ?>"><?= $sc['label'] ?></span></td>
              <td><?= date('d/m/Y', strtotime($r['created_at'])) ?></td>
              <td>
                <div class="act-icons">
                  <a href="reservation_details.php?id=<?= $r['id'] ?>" title="Voir le détail"><i class="fas fa-eye"></i></a>
                  <form method="POST" style="display:contents">
                    <input type="hidden" name="action" value="update_statut">
                    <input type="hidden" name="id" value="<?= $r['id'] ?>">
                    <input type="hidden" name="statut" value="confirmee">
                    <button type="submit" class="ok" title="Confirmer"><i class="fas fa-check"></i></button>
                  </form>
                  <form method="POST" style="display:contents">
                    <input type="hidden" name="action" value="update_statut">
                    <input type="hidden" name="id" value="<?= $r['id'] ?>">
                    <input type="hidden" name="statut" value="annulee">
                    <button type="submit" class="no" title="Annuler"><i class="fas fa-times"></i></button>
                  </form>
                  <form method="POST" style="display:contents" onsubmit="return confirm('Supprimer cette réservation ? Cette action est irréversible.')">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?= $r['id'] ?>">
                    <button type="submit" class="no" title="Supprimer"><i class="fas fa-trash"></i></button>
                  </form>
                </div>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
        <?php endif; ?>
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