<?php
/**
 * admin/client_details.php
 * Fiche client complète : infos personnelles + tout l'historique
 * (réservations, devis, factures, paiements, messages) dans une
 * seule page, comme demandé.
 */
require_once __DIR__ . '/../includes/config.php';
requireAdmin();

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) die('ID invalide');

$stmt = $pdo->prepare("SELECT * FROM clients WHERE id = ? AND deleted_at IS NULL");
$stmt->execute([$id]);
$c = $stmt->fetch();
if (!$c) die('Client introuvable');

// ── Réservations ───────────────────────────────────────────────
$reservations = [];
try {
    $r = $pdo->prepare("
        SELECT res.*, te.nom AS type_nom
        FROM reservations res
        LEFT JOIN types_evenements te ON te.id = res.type_evenement_id
        WHERE res.client_id = ? AND res.deleted_at IS NULL
        ORDER BY res.created_at DESC
    ");
    $r->execute([$id]);
    $reservations = $r->fetchAll();
} catch (Exception $e) {}

// ── Devis ──────────────────────────────────────────────────────
$devisListe = [];
try {
    $d = $pdo->prepare("SELECT * FROM devis WHERE client_id = ? ORDER BY created_at DESC");
    $d->execute([$id]);
    $devisListe = $d->fetchAll();
} catch (Exception $e) {}

// ── Factures ───────────────────────────────────────────────────
$factures = [];
try {
    $f = $pdo->prepare("SELECT * FROM factures WHERE client_id = ? ORDER BY created_at DESC");
    $f->execute([$id]);
    $factures = $f->fetchAll();
} catch (Exception $e) {}

// ── Paiements (via les factures de ce client) ─────────────────
$paiements = [];
try {
    $p = $pdo->prepare("
        SELECT p.*, f.numero AS facture_num
        FROM paiements p
        LEFT JOIN factures f ON f.id = p.facture_id
        WHERE f.client_id = ?
        ORDER BY p.date_paiement DESC
    ");
    $p->execute([$id]);
    $paiements = $p->fetchAll();
} catch (Exception $e) {}

// ── Messages (rapprochement par email ou téléphone) ────────────
$messages = [];
try {
    $m = $pdo->prepare("
        SELECT * FROM contacts
        WHERE (email = ? AND email <> '') OR (telephone = ? AND telephone <> '')
        ORDER BY created_at DESC
    ");
    $m->execute([$c['email'], $c['telephone']]);
    $messages = $m->fetchAll();
} catch (Exception $e) {}

$totalPaye = array_sum(array_column($paiements, 'montant'));
$totalFacture = array_sum(array_column($factures, 'montant_ttc'));

$nomComplet = trim(($c['civilite'] ?? '') . ' ' . ($c['prenom'] ?? '') . ' ' . ($c['nom'] ?? ''));
$initiales = strtoupper(mb_substr($c['prenom'] ?? '', 0, 1) . mb_substr($c['nom'] ?? '', 0, 1));

$statutResaConfig = [
    'en_attente' => ['label'=>'En attente','color'=>'#FBB724','bg'=>'rgba(251,183,36,.15)'],
    'confirmee'  => ['label'=>'Confirmée','color'=>'#25D366','bg'=>'rgba(37,211,102,.15)'],
    'en_cours'   => ['label'=>'En cours','color'=>'#60A5FA','bg'=>'rgba(59,130,246,.15)'],
    'terminee'   => ['label'=>'Terminée','color'=>'#888','bg'=>'rgba(136,136,136,.15)'],
    'annulee'    => ['label'=>'Annulée','color'=>'#EF5350','bg'=>'rgba(239,68,68,.15)'],
];
$statutDevisConfig = [
    'recu'=>['label'=>'Reçu','color'=>'#FBB724'], 'en_traitement'=>['label'=>'En traitement','color'=>'#60A5FA'],
    'envoye'=>['label'=>'Envoyé','color'=>'#A78BFA'], 'accepte'=>['label'=>'Accepté','color'=>'#25D366'],
    'refuse'=>['label'=>'Refusé','color'=>'#EF5350'], 'expire'=>['label'=>'Expiré','color'=>'#888'],
];
$statutFacConfig = [
    'brouillon'=>['label'=>'Brouillon','color'=>'#888'], 'envoyee'=>['label'=>'Envoyée','color'=>'#60A5FA'],
    'payee'=>['label'=>'Payée','color'=>'#25D366'], 'partiellement_payee'=>['label'=>'Partiel','color'=>'#FBB724'],
    'annulee'=>['label'=>'Annulée','color'=>'#EF5350'],
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <link rel="icon" type="image/png" href="../assets/img/favicon-32.png">
  <meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title><?= htmlspecialchars($nomComplet) ?> — Admin EL MOUSSAOUI</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600;700&family=Jost:wght@300;400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="../css/style.css">
  <style>
    body{overflow-x:hidden}
    .sidebar-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.6);z-index:999}
    .sidebar-overlay.show{display:block}
    @media(max-width:768px){.sidebar{position:fixed;left:0;top:0;bottom:0;z-index:1000;transform:translateX(-100%);transition:var(--transition)}.sidebar.open{transform:translateX(0)}}

    .client-hero{display:flex;align-items:center;gap:18px;background:var(--dark-card);border:1px solid var(--border);border-radius:var(--radius);padding:24px;margin-bottom:20px}
    .client-hero-avatar{width:64px;height:64px;border-radius:16px;background:linear-gradient(135deg,var(--gold-dark),var(--gold));display:flex;align-items:center;justify-content:center;font-size:1.6rem;font-weight:700;color:var(--dark);flex-shrink:0}
    .client-hero h2{font-size:1.2rem;color:var(--white);margin-bottom:4px}
    .client-hero-meta{display:flex;gap:16px;flex-wrap:wrap;font-size:.8rem;color:var(--text-muted)}
    .client-hero-meta a{color:var(--gold);text-decoration:none}

    .panel{background:var(--dark-card);border:1px solid var(--border);border-radius:var(--radius);padding:20px 22px;margin-bottom:20px}
    .panel h3{font-size:.9rem;color:var(--white);margin-bottom:14px;display:flex;align-items:center;gap:8px;justify-content:space-between}
    .panel h3 .count{font-size:.72rem;color:var(--text-muted);font-weight:400}
    .hist-row{display:flex;align-items:center;justify-content:space-between;padding:10px 0;border-bottom:1px solid rgba(255,255,255,.04);font-size:.82rem}
    .hist-row:last-child{border-bottom:none}
    .hist-left strong{color:var(--white);display:block;font-size:.84rem}
    .hist-left span{color:#666;font-size:.72rem}
    .hist-right{text-align:right}
    .hist-amount{color:var(--gold);font-weight:700}
    .badge-mini{padding:3px 10px;border-radius:12px;font-size:.68rem;font-weight:700}
    .empty-row{text-align:center;color:#555;padding:20px;font-size:.8rem}
    .info-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px}
    .info-field label{display:block;font-size:.68rem;color:var(--text-muted);text-transform:uppercase;margin-bottom:3px}
    .info-field span{color:var(--white);font-size:.85rem}
  </style>
</head>
<body>
<div class="sidebar-overlay" id="sidebarOverlay"></div>
<div class="admin-layout">
  <?php $activePage = 'clients'; include_once __DIR__ . '/../includes/admin-sidebar.php'; ?>
  <main class="admin-main">
    <div class="admin-topbar">
      <div style="display:flex;align-items:center;gap:12px">
        <button id="sidebarToggle" class="topbar-btn"><i class="fas fa-bars"></i></button>
        <div class="topbar-title"><h2>Fiche client</h2><p>Historique complet</p></div>
      </div>
      <a href="clients.php" class="topbar-btn" title="Retour à la liste"><i class="fas fa-arrow-left"></i></a>
    </div>

    <div class="admin-content">

      <div class="client-hero">
        <div class="client-hero-avatar"><?= htmlspecialchars($initiales) ?></div>
        <div>
          <h2><?= htmlspecialchars($nomComplet) ?></h2>
          <div class="client-hero-meta">
            <a href="tel:<?= htmlspecialchars($c['telephone']) ?>"><i class="fas fa-phone"></i> <?= htmlspecialchars($c['telephone']) ?></a>
            <?php if ($c['email']): ?><a href="mailto:<?= htmlspecialchars($c['email']) ?>"><i class="fas fa-envelope"></i> <?= htmlspecialchars($c['email']) ?></a><?php endif; ?>
            <span><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($c['ville'] ?: 'Errachidia') ?></span>
            <span><i class="fas fa-clock"></i> Client depuis <?= date('d/m/Y', strtotime($c['created_at'])) ?></span>
          </div>
        </div>
      </div>

      <!-- Résumé financier -->
      <div class="stats-grid" style="margin-bottom:20px">
        <div class="stat-card"><div class="stat-card-value"><?= count($reservations) ?></div><div class="stat-card-label">Réservations</div></div>
        <div class="stat-card"><div class="stat-card-value" style="color:var(--gold)"><?= number_format($totalFacture,0,',',' ') ?></div><div class="stat-card-label">Total facturé (MAD)</div></div>
        <div class="stat-card"><div class="stat-card-value" style="color:#25D366"><?= number_format($totalPaye,0,',',' ') ?></div><div class="stat-card-label">Total payé (MAD)</div></div>
        <div class="stat-card"><div class="stat-card-value" style="color:#FBB724"><?= number_format(max(0,$totalFacture-$totalPaye),0,',',' ') ?></div><div class="stat-card-label">Reste à payer (MAD)</div></div>
      </div>

      <!-- Informations personnelles -->
      <div class="panel">
        <h3><i class="fas fa-id-card" style="color:var(--gold)"></i> Informations personnelles</h3>
        <div class="info-grid">
          <div class="info-field"><label>Nom complet</label><span><?= htmlspecialchars($nomComplet) ?></span></div>
          <div class="info-field"><label>Téléphone</label><span dir="ltr"><?= htmlspecialchars($c['telephone']) ?></span></div>
          <div class="info-field"><label>Téléphone 2</label><span><?= htmlspecialchars($c['telephone2'] ?: '—') ?></span></div>
          <div class="info-field"><label>Email</label><span><?= htmlspecialchars($c['email'] ?: '—') ?></span></div>
          <div class="info-field"><label>Adresse</label><span><?= htmlspecialchars($c['adresse'] ?: '—') ?></span></div>
          <div class="info-field"><label>Ville</label><span><?= htmlspecialchars($c['ville'] ?: '—') ?></span></div>
          <div class="info-field"><label>CIN</label><span><?= htmlspecialchars($c['cin'] ?: '—') ?></span></div>
          <div class="info-field"><label>Source</label><span><?= htmlspecialchars($c['source'] ?: '—') ?></span></div>
        </div>
        <?php if (!empty($c['notes_internes'])): ?>
        <div style="margin-top:16px;background:var(--dark-3);border-left:3px solid var(--gold);padding:12px 16px;border-radius:0 8px 8px 0;font-size:.82rem;color:var(--text-muted)">
          <?= nl2br(htmlspecialchars($c['notes_internes'])) ?>
        </div>
        <?php endif; ?>
      </div>

      <!-- Réservations -->
      <div class="panel">
        <h3><i class="fas fa-calendar-check" style="color:var(--gold)"></i> Réservations <span class="count">(<?= count($reservations) ?>)</span></h3>
        <?php if (empty($reservations)): ?><div class="empty-row">Aucune réservation.</div><?php else: foreach ($reservations as $r):
          $sc = $statutResaConfig[$r['statut']] ?? $statutResaConfig['en_attente']; ?>
        <a href="reservation_details.php?id=<?= $r['id'] ?>" class="hist-row" style="text-decoration:none;cursor:pointer">
          <div class="hist-left"><strong><?= htmlspecialchars($r['type_nom'] ?: 'Événement') ?></strong><span><?= $r['date_evenement'] ? date('d/m/Y', strtotime($r['date_evenement'])) : '—' ?> · <?= (int)$r['nbr_invites'] ?> invités</span></div>
          <span class="badge-mini" style="background:<?= $sc['bg'] ?>;color:<?= $sc['color'] ?>"><?= $sc['label'] ?></span>
        </a>
        <?php endforeach; endif; ?>
      </div>

      <!-- Devis -->
      <div class="panel">
        <h3><i class="fas fa-file-invoice" style="color:var(--gold)"></i> Devis <span class="count">(<?= count($devisListe) ?>)</span></h3>
        <?php if (empty($devisListe)): ?><div class="empty-row">Aucun devis.</div><?php else: foreach ($devisListe as $d):
          $sc = $statutDevisConfig[$d['statut']] ?? $statutDevisConfig['recu']; ?>
        <div class="hist-row">
          <div class="hist-left"><strong><?= htmlspecialchars($d['reference']) ?></strong><span><?= date('d/m/Y', strtotime($d['created_at'])) ?></span></div>
          <div class="hist-right">
            <div class="hist-amount"><?= number_format($d['montant_ttc'],0,',',' ') ?> MAD</div>
            <span class="badge-mini" style="color:<?= $sc['color'] ?>"><?= $sc['label'] ?></span>
          </div>
        </div>
        <?php endforeach; endif; ?>
      </div>

      <!-- Factures -->
      <div class="panel">
        <h3><i class="fas fa-receipt" style="color:var(--gold)"></i> Factures <span class="count">(<?= count($factures) ?>)</span></h3>
        <?php if (empty($factures)): ?><div class="empty-row">Aucune facture.</div><?php else: foreach ($factures as $f):
          $sc = $statutFacConfig[$f['statut']] ?? $statutFacConfig['brouillon']; ?>
        <div class="hist-row">
          <div class="hist-left"><strong><?= htmlspecialchars($f['numero']) ?></strong><span><?= date('d/m/Y', strtotime($f['created_at'])) ?></span></div>
          <div class="hist-right">
            <div class="hist-amount"><?= number_format($f['montant_ttc'],0,',',' ') ?> MAD</div>
            <span class="badge-mini" style="color:<?= $sc['color'] ?>"><?= $sc['label'] ?></span>
          </div>
        </div>
        <?php endforeach; endif; ?>
      </div>

      <!-- Paiements -->
      <div class="panel">
        <h3><i class="fas fa-credit-card" style="color:var(--gold)"></i> Paiements <span class="count">(<?= count($paiements) ?>)</span></h3>
        <?php if (empty($paiements)): ?><div class="empty-row">Aucun paiement enregistré.</div><?php else: foreach ($paiements as $p): ?>
        <div class="hist-row">
          <div class="hist-left"><strong><?= htmlspecialchars($p['facture_num'] ?: 'Sans facture') ?></strong><span><?= date('d/m/Y', strtotime($p['date_paiement'])) ?> · <?= htmlspecialchars($p['mode'] ?? '') ?></span></div>
          <div class="hist-amount"><?= number_format($p['montant'],0,',',' ') ?> MAD</div>
        </div>
        <?php endforeach; endif; ?>
      </div>

      <!-- Messages -->
      <div class="panel">
        <h3><i class="fas fa-envelope" style="color:var(--gold)"></i> Messages <span class="count">(<?= count($messages) ?>)</span></h3>
        <?php if (empty($messages)): ?><div class="empty-row">Aucun message.</div><?php else: foreach ($messages as $m): ?>
        <a href="messages.php?id=<?= $m['id'] ?>" class="hist-row" style="text-decoration:none;cursor:pointer">
          <div class="hist-left"><strong><?= htmlspecialchars($m['sujet'] ?: 'Sans sujet') ?></strong><span><?= date('d/m/Y', strtotime($m['created_at'])) ?></span></div>
          <span class="badge-mini" style="background:rgba(136,136,136,.15);color:#888"><?= htmlspecialchars($m['statut']) ?></span>
        </a>
        <?php endforeach; endif; ?>
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
