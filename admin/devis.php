<?php
require_once __DIR__ . '/../includes/config.php';
requireAdmin();

// ── Actions ────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id     = (int)($_POST['id'] ?? 0);

    if ($action === 'update_statut' && $id) {
        $statut = sanitize($_POST['statut'] ?? '');
        $allowed = ['recu','en_traitement','envoye','accepte','refuse','expire'];
        if (in_array($statut, $allowed, true)) {
            $pdo->prepare("UPDATE devis SET statut=?, updated_at=NOW() WHERE id=?")->execute([$statut, $id]);

            // Si le devis est accepté, on confirme automatiquement la réservation liée
            if ($statut === 'accepte') {
                $d = $pdo->prepare("SELECT reservation_id FROM devis WHERE id=?");
                $d->execute([$id]);
                $resId = $d->fetchColumn();
                if ($resId) {
                    $pdo->prepare("UPDATE reservations SET statut='confirmee', updated_at=NOW() WHERE id=? AND statut='en_attente'")->execute([$resId]);
                }
            }
        }
        header('Location: devis.php?msg=Statut+mis+à+jour&type=success'); exit;
    }

    if ($action === 'delete' && $id) {
        $pdo->prepare("DELETE FROM devis_lignes WHERE devis_id=?")->execute([$id]);
        $pdo->prepare("DELETE FROM devis WHERE id=?")->execute([$id]);
        header('Location: devis.php?msg=Devis+supprimé&type=success'); exit;
    }
}

// ── Filtres ────────────────────────────────────────────────────
$filtreStatut = $_GET['statut'] ?? '';
$recherche    = trim($_GET['q'] ?? '');

$where  = ['1=1'];
$params = [];
if ($filtreStatut && in_array($filtreStatut, ['recu','en_traitement','envoye','accepte','refuse','expire'], true)) {
    $where[] = 'd.statut = ?';
    $params[] = $filtreStatut;
}
if ($recherche !== '') {
    $where[] = "(c.nom LIKE ? OR c.prenom LIKE ? OR c.telephone LIKE ? OR d.reference LIKE ?)";
    $like = '%' . $recherche . '%';
    array_push($params, $like, $like, $like, $like);
}
$whereSql = implode(' AND ', $where);

// ── Liste ──────────────────────────────────────────────────────
$devisListe = [];
try {
    $stmt = $pdo->prepare("
        SELECT d.*, c.nom AS c_nom, c.prenom AS c_prenom, c.telephone AS c_tel,
               te.nom AS type_nom, r.reference AS resa_ref,
               f.acompte AS facture_paye, f.montant_ttc AS facture_ttc, f.id AS facture_id
        FROM devis d
        LEFT JOIN clients c ON c.id = d.client_id
        LEFT JOIN types_evenements te ON te.id = d.type_evenement_id
        LEFT JOIN reservations r ON r.id = d.reservation_id
        LEFT JOIN factures f ON f.reservation_id = d.reservation_id
        WHERE $whereSql
        ORDER BY d.created_at DESC
    ");
    $stmt->execute($params);
    $devisListe = $stmt->fetchAll();
} catch (Exception $e) { $erreurBdd = $e->getMessage(); }

// ── Compteurs ────────────────────────────────────────────────────
$compteurs = ['total'=>0,'recu'=>0,'accepte'=>0,'refuse'=>0];
try {
    $c = $pdo->query("SELECT statut, COUNT(*) n FROM devis GROUP BY statut")->fetchAll();
    foreach ($c as $row) {
        $compteurs['total'] += $row['n'];
        if (isset($compteurs[$row['statut']])) $compteurs[$row['statut']] = $row['n'];
    }
} catch (Exception $e) {}

$statutConfig = [
    'recu'          => ['label'=>'Reçu',         'color'=>'#FBB724','bg'=>'rgba(251,183,36,.15)'],
    'en_traitement' => ['label'=>'En traitement', 'color'=>'#60A5FA','bg'=>'rgba(59,130,246,.15)'],
    'envoye'        => ['label'=>'Envoyé',        'color'=>'#A78BFA','bg'=>'rgba(167,139,250,.15)'],
    'accepte'       => ['label'=>'Accepté',       'color'=>'#25D366','bg'=>'rgba(37,211,102,.15)'],
    'refuse'        => ['label'=>'Refusé',        'color'=>'#EF5350','bg'=>'rgba(239,68,68,.15)'],
    'expire'        => ['label'=>'Expiré',        'color'=>'#888',   'bg'=>'rgba(136,136,136,.15)'],
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <link rel="icon" type="image/png" href="../assets/img/favicon-32.png">
  <meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title>Devis — Admin EL MOUSSAOUI</title>
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
    .filters-bar input{background:var(--dark-3);border:1px solid var(--border);border-radius:8px;padding:8px 12px;color:var(--white);font-size:.8rem;min-width:200px}
    .filters-bar button{background:var(--gold);color:var(--dark);border:none;border-radius:8px;padding:8px 16px;font-weight:700;font-size:.78rem;cursor:pointer}
    .tfilter{padding:7px 14px;border-radius:20px;border:1px solid var(--border);background:transparent;color:var(--text-muted);font-size:.75rem;cursor:pointer;text-decoration:none;display:inline-block}
    .tfilter.active{background:rgba(212,175,55,.12);border-color:var(--gold);color:var(--gold)}
    .devis-table{width:100%;border-collapse:collapse}
    .devis-table th{text-align:left;padding:10px 14px;font-size:.66rem;text-transform:uppercase;letter-spacing:.5px;color:var(--text-muted);border-bottom:1px solid var(--border)}
    .devis-table td{padding:12px 14px;font-size:.8rem;border-bottom:1px solid rgba(255,255,255,.04);vertical-align:top}
    .devis-table tr:hover{background:rgba(212,175,55,.03)}
    .badge-statut{padding:4px 12px;border-radius:20px;font-size:.68rem;font-weight:700;display:inline-block;white-space:nowrap}
    .act-icons{display:flex;gap:6px;flex-wrap:wrap}
    .act-icons a,.act-icons button{width:29px;height:29px;border-radius:8px;border:1px solid var(--border);background:transparent;color:var(--text-muted);display:flex;align-items:center;justify-content:center;cursor:pointer;text-decoration:none;font-size:.75rem}
    .act-icons a:hover,.act-icons button:hover{border-color:var(--gold);color:var(--gold)}
    .act-icons .ok:hover{border-color:#25D366;color:#25D366}
    .act-icons .no:hover{border-color:#EF5350;color:#EF5350}
    @media(max-width:1000px){.devis-table{display:block;overflow-x:auto;white-space:nowrap}}
  </style>
</head>
<body>
<div class="sidebar-overlay" id="sidebarOverlay"></div>
<div class="admin-layout">
  <?php $activePage = 'devis'; include_once __DIR__ . '/../includes/admin-sidebar.php'; ?>
  <main class="admin-main">
    <div class="admin-topbar">
      <div style="display:flex;align-items:center;gap:12px">
        <button id="sidebarToggle" class="topbar-btn"><i class="fas fa-bars"></i></button>
        <div class="topbar-title"><h2>Devis</h2><p>Propositions financières liées aux réservations</p></div>
      </div>
    </div>

    <div class="admin-content">
      <?php if (!empty($_GET['msg'])): ?>
      <div class="alert alert-<?= ($_GET['type'] ?? '') === 'success' ? 'success' : 'error' ?>" style="margin-bottom:20px">
        <i class="fas fa-check-circle"></i> <?= htmlspecialchars($_GET['msg']) ?>
      </div>
      <?php endif; ?>

      <div class="stats-grid" style="margin-bottom:20px">
        <div class="stat-card"><div class="stat-card-value"><?= $compteurs['total'] ?></div><div class="stat-card-label">Total devis</div></div>
        <div class="stat-card"><div class="stat-card-value" style="color:#FBB724"><?= $compteurs['recu'] ?></div><div class="stat-card-label">Reçus</div></div>
        <div class="stat-card"><div class="stat-card-value" style="color:#25D366"><?= $compteurs['accepte'] ?></div><div class="stat-card-label">Acceptés</div></div>
        <div class="stat-card"><div class="stat-card-value" style="color:#EF5350"><?= $compteurs['refuse'] ?></div><div class="stat-card-label">Refusés</div></div>
      </div>

      <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:16px">
        <a href="?statut=" class="tfilter <?= $filtreStatut==='' ? 'active':'' ?>">Tous</a>
        <a href="?statut=recu" class="tfilter <?= $filtreStatut==='recu' ? 'active':'' ?>">Reçus</a>
        <a href="?statut=en_traitement" class="tfilter <?= $filtreStatut==='en_traitement' ? 'active':'' ?>">En traitement</a>
        <a href="?statut=envoye" class="tfilter <?= $filtreStatut==='envoye' ? 'active':'' ?>">Envoyés</a>
        <a href="?statut=accepte" class="tfilter <?= $filtreStatut==='accepte' ? 'active':'' ?>">Acceptés</a>
        <a href="?statut=refuse" class="tfilter <?= $filtreStatut==='refuse' ? 'active':'' ?>">Refusés</a>
        <a href="?statut=expire" class="tfilter <?= $filtreStatut==='expire' ? 'active':'' ?>">Expirés</a>
      </div>

      <div class="filters-bar">
        <form method="GET" style="display:contents">
          <input type="hidden" name="statut" value="<?= htmlspecialchars($filtreStatut) ?>">
          <input type="search" name="q" placeholder="🔍 Client, téléphone, référence..." value="<?= htmlspecialchars($recherche) ?>">
          <button type="submit"><i class="fas fa-filter"></i> Filtrer</button>
        </form>
      </div>

      <div class="dash-card">
        <?php if (!empty($erreurBdd)): ?>
        <div class="alert alert-error" style="margin-bottom:20px">
          <i class="fas fa-exclamation-circle"></i> Erreur base de données : <?= htmlspecialchars($erreurBdd) ?>
        </div>
        <?php endif; ?>
        <?php if (empty($devisListe)): ?>
        <div style="padding:60px 20px;text-align:center;color:#555">
          <i class="fas fa-file-invoice" style="font-size:2.5rem;opacity:.2;display:block;margin-bottom:14px"></i>
          Aucun devis ne correspond à ces critères.
        </div>
        <?php else: ?>
        <table class="devis-table">
          <thead>
            <tr>
              <th>N° Devis</th><th>Client</th><th>Réservation</th><th>Événement</th><th>Date</th>
              <th>HT</th><th>TVA</th><th>TTC</th><th>Acompte / Reste</th><th>Expire le</th><th>Statut</th><th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($devisListe as $d):
              $sc = $statutConfig[$d['statut']] ?? $statutConfig['recu'];
              $nomClient = trim(($d['c_prenom'] ?? '').' '.($d['c_nom'] ?? '')) ?: ($d['nom_prospect'] ?: '—');
            ?>
            <tr>
              <td><strong style="color:var(--gold)"><?= htmlspecialchars($d['reference']) ?></strong></td>
              <td>
                <strong style="color:var(--white);display:block"><?= htmlspecialchars($nomClient) ?></strong>
                <span style="font-size:.72rem;color:#666"><?= htmlspecialchars($d['c_tel'] ?? $d['telephone_prospect'] ?? '—') ?></span>
              </td>
              <td><?= $d['resa_ref'] ? htmlspecialchars($d['resa_ref']) : '<span style="color:#555">—</span>' ?></td>
              <td><?= htmlspecialchars($d['type_nom'] ?: '—') ?></td>
              <td><?= $d['date_evenement'] ? date('d/m/Y', strtotime($d['date_evenement'])) : '—' ?></td>
              <td><?= number_format($d['montant_ht'],0,',',' ') ?> MAD</td>
              <td><?= number_format($d['montant_tva'],0,',',' ') ?> MAD</td>
              <td><strong style="color:var(--gold)"><?= number_format($d['montant_ttc'],0,',',' ') ?> MAD</strong></td>
              <td>
                <?php if ($d['facture_id']): ?>
                  <span style="color:#25D366"><?= number_format($d['facture_paye'],0,',',' ') ?></span> /
                  <span style="color:#EF5350"><?= number_format($d['facture_ttc'] - $d['facture_paye'],0,',',' ') ?></span>
                <?php else: ?>
                  <span style="color:#555">Pas encore facturé</span>
                <?php endif; ?>
              </td>
              <td><?= $d['date_expiration'] ? date('d/m/Y', strtotime($d['date_expiration'])) : '—' ?></td>
              <td><span class="badge-statut" style="background:<?= $sc['bg'] ?>;color:<?= $sc['color'] ?>"><?= $sc['label'] ?></span></td>
              <td>
                <div class="act-icons">
                  <a href="print_devis.php?id=<?= $d['id'] ?>" target="_blank" title="Voir / Imprimer PDF"><i class="fas fa-eye"></i></a>
                  <?php if ($d['c_tel']): ?>
                  <a href="https://wa.me/212<?= ltrim(preg_replace('/[^0-9]/','',$d['c_tel']), '0') ?>?text=<?= urlencode("Bonjour, voici votre devis {$d['reference']} de Traiteur EL MOUSSAOUI : ") ?>"
                     target="_blank" class="ok" title="Envoyer au client (WhatsApp)"><i class="fab fa-whatsapp"></i></a>
                  <?php endif; ?>
                  <?php if ($d['statut'] === 'accepte' && !$d['facture_id']): ?>
                  <a href="factures.php?from_devis=<?= $d['id'] ?>" class="ok" title="Générer la facture"><i class="fas fa-file-invoice-dollar"></i></a>
                  <?php endif; ?>
                  <form method="POST" style="display:contents">
                    <input type="hidden" name="action" value="update_statut">
                    <input type="hidden" name="id" value="<?= $d['id'] ?>">
                    <input type="hidden" name="statut" value="accepte">
                    <button type="submit" class="ok" title="Accepter"><i class="fas fa-check"></i></button>
                  </form>
                  <form method="POST" style="display:contents">
                    <input type="hidden" name="action" value="update_statut">
                    <input type="hidden" name="id" value="<?= $d['id'] ?>">
                    <input type="hidden" name="statut" value="refuse">
                    <button type="submit" class="no" title="Refuser"><i class="fas fa-times"></i></button>
                  </form>
                  <form method="POST" style="display:contents" onsubmit="return confirm('Supprimer ce devis ? Cette action est irréversible.')">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?= $d['id'] ?>">
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