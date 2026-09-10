<?php
require_once __DIR__ . '/../includes/config.php';
requireAdmin();

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) die('ID invalide');

// ── Actions ────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_statut') {
        $statut = sanitize($_POST['statut'] ?? '');
        $allowed = ['en_attente','confirmee','en_cours','terminee','annulee'];
        if (in_array($statut, $allowed, true)) {
            $pdo->prepare("UPDATE reservations SET statut=?, updated_at=NOW() WHERE id=?")->execute([$statut, $id]);
            $msg = 'Statut mis à jour.'; $msgType = 'success';
        }
    }

    if ($action === 'update_infos') {
        $pdo->prepare("
            UPDATE reservations SET
                date_evenement=?, heure_debut=?, nbr_invites=?, lieu=?, notes_internes=?, updated_at=NOW()
            WHERE id=?
        ")->execute([
            $_POST['date_evenement'], $_POST['heure_debut'] ?: '18:00:00',
            (int)$_POST['nbr_invites'], sanitize($_POST['lieu'] ?? ''),
            sanitize($_POST['notes_internes'] ?? ''), $id
        ]);
        $msg = 'Réservation modifiée avec succès.'; $msgType = 'success';
    }

    if ($action === 'delete') {
        $pdo->prepare("UPDATE reservations SET deleted_at=NOW() WHERE id=?")->execute([$id]);
        header('Location: reservations.php?msg=Réservation+supprimée&type=success'); exit;
    }
}

// ── Chargement ─────────────────────────────────────────────────
$stmt = $pdo->prepare("
    SELECT r.*, c.nom AS c_nom, c.prenom AS c_prenom, c.telephone AS c_tel,
           c.email AS c_email, c.adresse AS c_adresse, c.ville AS c_ville,
           te.nom AS type_nom
    FROM reservations r
    LEFT JOIN clients c ON c.id = r.client_id
    LEFT JOIN types_evenements te ON te.id = r.type_evenement_id
    WHERE r.id = ?
");
$stmt->execute([$id]);
$r = $stmt->fetch();
if (!$r) die('Réservation introuvable');

// Devis + lignes liés
$devis = null; $lignes = [];
try {
    $d = $pdo->prepare("SELECT * FROM devis WHERE reservation_id = ? ORDER BY id DESC LIMIT 1");
    $d->execute([$id]);
    $devis = $d->fetch();
    if ($devis) {
        $l = $pdo->prepare("SELECT * FROM devis_lignes WHERE devis_id = ? ORDER BY ordre ASC");
        $l->execute([$devis['id']]);
        $lignes = $l->fetchAll();
    }
} catch (Exception $e) {}

$statutConfig = [
    'en_attente' => ['label'=>'En attente', 'color'=>'#FBB724','bg'=>'rgba(251,183,36,.15)'],
    'confirmee'  => ['label'=>'Confirmée',  'color'=>'#25D366','bg'=>'rgba(37,211,102,.15)'],
    'en_cours'   => ['label'=>'En cours',   'color'=>'#60A5FA','bg'=>'rgba(59,130,246,.15)'],
    'terminee'   => ['label'=>'Terminée',   'color'=>'#888',   'bg'=>'rgba(136,136,136,.15)'],
    'annulee'    => ['label'=>'Annulée',    'color'=>'#EF5350','bg'=>'rgba(239,68,68,.15)'],
];
$sc = $statutConfig[$r['statut']] ?? $statutConfig['en_attente'];
$nomClient = trim(($r['c_prenom'] ?? '').' '.($r['c_nom'] ?? '')) ?: 'Client #'.$r['client_id'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <link rel="icon" type="image/png" href="../assets/img/favicon-32.png">
  <meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title>Réservation #<?= $r['id'] ?> — Admin EL MOUSSAOUI</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600;700&family=Jost:wght@300;400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="../css/style.css">
  <style>
    body{overflow-x:hidden}
    .sidebar-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.6);z-index:999}
    .sidebar-overlay.show{display:block}
    @media(max-width:768px){.sidebar{position:fixed;left:0;top:0;bottom:0;z-index:1000;transform:translateX(-100%);transition:var(--transition)}.sidebar.open{transform:translateX(0)}}
    .detail-grid{display:grid;grid-template-columns:2fr 1fr;gap:20px}
    @media(max-width:900px){.detail-grid{grid-template-columns:1fr}}
    .panel{background:var(--dark-card);border:1px solid var(--border);border-radius:var(--radius);padding:22px;margin-bottom:20px}
    .panel h3{font-size:.9rem;color:var(--white);margin-bottom:16px;display:flex;align-items:center;gap:8px}
    .panel h3 i{color:var(--gold)}
    .info-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px}
    .info-field label{display:block;font-size:.68rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:.5px;margin-bottom:4px}
    .info-field span{font-size:.9rem;color:var(--white)}
    .message-box{background:var(--dark-3);border-left:3px solid var(--gold);padding:14px 18px;border-radius:0 8px 8px 0;font-size:.85rem;color:#ccc;line-height:1.6}
    .lignes-table{width:100%;font-size:.82rem}
    .lignes-table td{padding:8px 0;border-bottom:1px solid rgba(255,255,255,.05)}
    .action-btn{display:flex;align-items:center;justify-content:center;gap:8px;width:100%;padding:11px;border-radius:10px;border:none;font-weight:700;font-size:.82rem;cursor:pointer;margin-bottom:10px;text-decoration:none}
    .action-btn.confirm{background:rgba(37,211,102,.15);color:#25D366;border:1px solid rgba(37,211,102,.3)}
    .action-btn.refuse{background:rgba(239,68,68,.1);color:#EF5350;border:1px solid rgba(239,68,68,.25)}
    .action-btn.edit{background:rgba(212,175,55,.1);color:var(--gold);border:1px solid rgba(212,175,55,.25)}
    .action-btn.contact{background:rgba(37,211,102,.12);color:#25D366;border:1px solid rgba(37,211,102,.25)}
    .action-btn.delete{background:rgba(239,68,68,.05);color:#EF5350;border:1px solid rgba(239,68,68,.15)}
    .edit-form{display:none;margin-top:16px;padding-top:16px;border-top:1px dashed var(--border)}
    .edit-form.show{display:block}
    .edit-form input,.edit-form textarea{width:100%;background:var(--dark-3);border:1px solid var(--border);border-radius:8px;padding:9px 12px;color:var(--white);font-size:.82rem;font-family:inherit;margin-bottom:10px}
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
        <div class="topbar-title"><h2>Réservation <?= htmlspecialchars($r['reference']) ?></h2><p>Reçue le <?= date('d/m/Y à H:i', strtotime($r['created_at'])) ?></p></div>
      </div>
      <a href="reservations.php" class="topbar-btn" title="Retour à la liste"><i class="fas fa-arrow-left"></i></a>
    </div>

    <div class="admin-content">
      <?php if (!empty($msg)): ?>
      <div class="alert alert-<?= $msgType ?>" style="margin-bottom:20px"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($msg) ?></div>
      <?php endif; ?>

      <div style="margin-bottom:20px">
        <span class="badge-statut" style="background:<?= $sc['bg'] ?>;color:<?= $sc['color'] ?>;padding:6px 16px;border-radius:20px;font-weight:700;font-size:.8rem"><?= $sc['label'] ?></span>
      </div>

      <div class="detail-grid">
        <div>
          <!-- Informations client -->
          <div class="panel">
            <h3><i class="fas fa-user"></i> Informations client</h3>
            <div class="info-grid">
              <div class="info-field"><label>Nom complet</label><span><?= htmlspecialchars($nomClient) ?></span></div>
              <div class="info-field"><label>Téléphone</label><span dir="ltr"><?= htmlspecialchars($r['c_tel'] ?: '—') ?></span></div>
              <div class="info-field"><label>Email</label><span><?= htmlspecialchars($r['c_email'] ?: '—') ?></span></div>
              <div class="info-field"><label>Ville</label><span><?= htmlspecialchars($r['c_ville'] ?: '—') ?></span></div>
            </div>
          </div>

          <!-- Informations événement -->
          <div class="panel">
            <h3><i class="fas fa-calendar-star"></i> Informations événement</h3>
            <div class="info-grid">
              <div class="info-field"><label>Type d'événement</label><span><?= htmlspecialchars($r['type_nom'] ?: '—') ?></span></div>
              <div class="info-field"><label>Date</label><span><?= $r['date_evenement'] ? date('d/m/Y', strtotime($r['date_evenement'])) : '—' ?></span></div>
              <div class="info-field"><label>Heure</label><span><?= substr($r['heure_debut'],0,5) ?></span></div>
              <div class="info-field"><label>Nombre d'invités</label><span><?= (int)$r['nbr_invites'] ?> personnes</span></div>
              <div class="info-field" style="grid-column:1/-1"><label>Lieu</label><span><?= htmlspecialchars($r['lieu'] ?: '—') ?></span></div>
            </div>
          </div>

          <!-- Service / menu -->
          <?php if (!empty($lignes)): ?>
          <div class="panel">
            <h3><i class="fas fa-utensils"></i> Services / Menu demandés</h3>
            <table class="lignes-table">
              <?php foreach ($lignes as $l): ?>
              <tr>
                <td><?= htmlspecialchars($l['designation']) ?></td>
                <td style="text-align:right;color:var(--gold)"><?= $l['prix_unitaire'] > 0 ? number_format($l['prix_unitaire'],0,',',' ').' MAD' : 'Sur devis' ?></td>
              </tr>
              <?php endforeach; ?>
            </table>
          </div>
          <?php endif; ?>

          <!-- Message du client -->
          <?php if (!empty($r['notes_client'])): ?>
          <div class="panel">
            <h3><i class="fas fa-comment-dots"></i> Message du client</h3>
            <div class="message-box"><?= nl2br(htmlspecialchars($r['notes_client'])) ?></div>
          </div>
          <?php endif; ?>

          <!-- Notes internes -->
          <div class="panel">
            <h3><i class="fas fa-sticky-note"></i> Notes internes (privées)</h3>
            <div class="message-box"><?= $r['notes_internes'] ? nl2br(htmlspecialchars($r['notes_internes'])) : '<em style="color:#555">Aucune note pour l\'instant.</em>' ?></div>
          </div>
        </div>

        <div>
          <!-- Actions -->
          <div class="panel">
            <h3><i class="fas fa-bolt"></i> Actions</h3>
            <form method="POST">
              <input type="hidden" name="action" value="update_statut">
              <input type="hidden" name="statut" value="confirmee">
              <button type="submit" class="action-btn confirm"><i class="fas fa-check"></i> Confirmer</button>
            </form>
            <form method="POST">
              <input type="hidden" name="action" value="update_statut">
              <input type="hidden" name="statut" value="annulee">
              <button type="submit" class="action-btn refuse"><i class="fas fa-times"></i> Refuser / Annuler</button>
            </form>
            <button type="button" class="action-btn edit" onclick="document.getElementById('editForm').classList.toggle('show')">
              <i class="fas fa-edit"></i> Modifier
            </button>
            <?php if ($r['c_tel']): ?>
            <a href="https://wa.me/212<?= ltrim(preg_replace('/[^0-9]/','',$r['c_tel']), '0') ?>" target="_blank" class="action-btn contact">
              <i class="fab fa-whatsapp"></i> Contacter sur WhatsApp
            </a>
            <?php endif; ?>
            <?php if ($r['c_email']): ?>
            <a href="mailto:<?= htmlspecialchars($r['c_email']) ?>" class="action-btn contact">
              <i class="fas fa-envelope"></i> Contacter par email
            </a>
            <?php endif; ?>
            <form method="POST" onsubmit="return confirm('Supprimer cette réservation ? Cette action est irréversible.')">
              <input type="hidden" name="action" value="delete">
              <button type="submit" class="action-btn delete"><i class="fas fa-trash"></i> Supprimer</button>
            </form>

            <div id="editForm" class="edit-form">
              <form method="POST">
                <input type="hidden" name="action" value="update_infos">
                <label style="font-size:.72rem;color:var(--text-muted)">Date de l'événement</label>
                <input type="date" name="date_evenement" value="<?= htmlspecialchars($r['date_evenement']) ?>">
                <label style="font-size:.72rem;color:var(--text-muted)">Heure</label>
                <input type="time" name="heure_debut" value="<?= substr($r['heure_debut'],0,5) ?>">
                <label style="font-size:.72rem;color:var(--text-muted)">Nombre d'invités</label>
                <input type="number" name="nbr_invites" value="<?= (int)$r['nbr_invites'] ?>">
                <label style="font-size:.72rem;color:var(--text-muted)">Lieu</label>
                <input type="text" name="lieu" value="<?= htmlspecialchars($r['lieu']) ?>">
                <label style="font-size:.72rem;color:var(--text-muted)">Notes internes</label>
                <textarea name="notes_internes" rows="3"><?= htmlspecialchars($r['notes_internes'] ?? '') ?></textarea>
                <button type="submit" class="action-btn edit" style="margin-bottom:0"><i class="fas fa-save"></i> Enregistrer les modifications</button>
              </form>
            </div>
          </div>

          <!-- Infos système -->
          <div class="panel">
            <h3><i class="fas fa-info-circle"></i> Informations système</h3>
            <div class="info-field" style="margin-bottom:12px"><label>Référence</label><span><?= htmlspecialchars($r['reference']) ?></span></div>
            <div class="info-field" style="margin-bottom:12px"><label>Créée le</label><span><?= date('d/m/Y à H:i', strtotime($r['created_at'])) ?></span></div>
            <div class="info-field"><label>Dernière modification</label><span><?= $r['updated_at'] ? date('d/m/Y à H:i', strtotime($r['updated_at'])) : '—' ?></span></div>
          </div>

          <?php if ($devis): ?>
          <div class="panel">
            <h3><i class="fas fa-file-invoice"></i> Devis associé</h3>
            <div class="info-field" style="margin-bottom:12px"><label>Référence</label><span><?= htmlspecialchars($devis['reference']) ?></span></div>
            <div class="info-field"><label>Montant TTC</label><span style="color:var(--gold);font-weight:700"><?= number_format($devis['montant_ttc'],0,',',' ') ?> MAD</span></div>
            <a href="devis.php" style="display:block;text-align:center;margin-top:14px;font-size:.78rem;color:var(--gold)">Voir dans Devis →</a>
          </div>
          <?php endif; ?>
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
