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

    // ── Choix de la tente ────────────────────────────────────────
    if ($action === 'set_tente') {
        $tenteId = (int)($_POST['tente_id'] ?? 0);
        if ($tenteId > 0) {
            $chk = $pdo->prepare("SELECT id FROM tentes WHERE id=? AND actif=1");
            $chk->execute([$tenteId]);
            if ($chk->fetch()) {
                $pdo->prepare("UPDATE reservations SET tente_id=?, updated_at=NOW() WHERE id=?")->execute([$tenteId, $id]);
                $msg = 'Tente associée à la réservation.'; $msgType = 'success';
            }
        } else {
            $pdo->prepare("UPDATE reservations SET tente_id=NULL, updated_at=NOW() WHERE id=?")->execute([$id]);
            $msg = 'Tente retirée.'; $msgType = 'success';
        }
    }

    // ── Tarification manuelle (source de vérité serveur) ──────────
    // L'admin saisit librement désignation / quantité / prix unitaire
    // pour chaque ligne. On ne fait jamais confiance à un total envoyé
    // par le navigateur : il est toujours recalculé ici, côté serveur.
    if ($action === 'save_pricing') {
        $designations = $_POST['designation'] ?? [];
        $quantites    = $_POST['quantite']    ?? [];
        $prix         = $_POST['prix_unitaire'] ?? [];

        // Récupérer (ou créer) le devis lié à cette réservation
        $d = $pdo->prepare("SELECT id FROM devis WHERE reservation_id = ? ORDER BY id DESC LIMIT 1");
        $d->execute([$id]);
        $devisRow = $d->fetch();
        if ($devisRow) {
            $devisId = $devisRow['id'];
        } else {
            $tempNumero = 'TMP-' . uniqid();
            $pdo->prepare("INSERT INTO devis (reference, reservation_id, statut, montant_ht, tva_pct, created_at)
                            VALUES (?,?,'en_traitement',0,0,NOW())")->execute([$tempNumero, $id]);
            $devisId = $pdo->lastInsertId();
            $numero = 'DEV-' . date('Y') . '-' . str_pad($devisId, 4, '0', STR_PAD_LEFT);
            $pdo->prepare("UPDATE devis SET reference=? WHERE id=?")->execute([$numero, $devisId]);
        }

        $pdo->prepare("DELETE FROM devis_lignes WHERE devis_id=?")->execute([$devisId]);

        $total = 0; $ordre = 0;
        $ins = $pdo->prepare("INSERT INTO devis_lignes (devis_id, designation, quantite, prix_unitaire, ordre) VALUES (?,?,?,?,?)");
        foreach ($designations as $i => $nomLigne) {
            $nomLigne = sanitize($nomLigne);
            $q = max(0, (float)($quantites[$i] ?? 0));
            $pu = max(0, (float)($prix[$i] ?? 0));
            if ($nomLigne === '' || $q <= 0) continue; // ignore lignes vides
            $ins->execute([$devisId, $nomLigne, $q, $pu, $ordre++]);
            $total += $q * $pu;
        }

        $pdo->prepare("UPDATE devis SET montant_ht=?, tva_pct=0, statut=IF(statut='recu','en_traitement',statut), updated_at=NOW() WHERE id=?")
            ->execute([$total, $devisId]);
        $pdo->prepare("UPDATE reservations SET montant_total=?, updated_at=NOW() WHERE id=?")->execute([$total, $id]);

        $msg = 'Tarification enregistrée. Total : ' . number_format($total,0,',',' ') . ' MAD.'; $msgType = 'success';
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

// Tentes disponibles + tente actuellement choisie
$tentes = []; $tenteChoisie = null;
try {
    $tentes = $pdo->query("SELECT * FROM tentes WHERE actif=1 ORDER BY ordre ASC, id ASC")->fetchAll();
    if (!empty($r['tente_id'])) {
        foreach ($tentes as $t) { if ($t['id'] == $r['tente_id']) { $tenteChoisie = $t; break; } }
        if (!$tenteChoisie) {
            $tq = $pdo->prepare("SELECT * FROM tentes WHERE id=?");
            $tq->execute([$r['tente_id']]);
            $tenteChoisie = $tq->fetch() ?: null;
        }
    }
} catch (Exception $e) { /* table tentes pas encore migrée */ }

$statutConfig = [
    'en_attente' => ['label'=>tt('En attente','قيد الانتظار'), 'color'=>'#FBB724','bg'=>'rgba(251,183,36,.15)'],
    'confirmee'  => ['label'=>tt('Confirmée','مؤكدة'),  'color'=>'#25D366','bg'=>'rgba(37,211,102,.15)'],
    'en_cours'   => ['label'=>tt('En cours','قيد التنفيذ'),   'color'=>'#60A5FA','bg'=>'rgba(59,130,246,.15)'],
    'terminee'   => ['label'=>tt('Terminée','منتهية'),   'color'=>'#888',   'bg'=>'rgba(136,136,136,.15)'],
    'annulee'    => ['label'=>tt('Annulée','ملغاة'),    'color'=>'#EF5350','bg'=>'rgba(239,68,68,.15)'],
];
$sc = $statutConfig[$r['statut']] ?? $statutConfig['en_attente'];
$nomClient = trim(($r['c_prenom'] ?? '').' '.($r['c_nom'] ?? '')) ?: 'Client #'.$r['client_id'];
?>
<!DOCTYPE html>
<html lang="<?= adminLang() ?>" dir="<?= adminDir() ?>">
<head>
  <meta charset="UTF-8">
  <link rel="icon" type="image/png" href="../assets/img/favicon-32.png">
  <meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title><?= tt('Réservation','حجز') ?> #<?= $r['id'] ?> — Admin EL MOUSSAOUI</title>
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
    .tentes-choice-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:14px}
    .tente-choice-card{background:var(--dark-3);border:1px solid var(--border);border-radius:12px;overflow:hidden}
    .tente-choice-card.selected{border-color:var(--gold)}
    .tc-photo{width:100%;height:100px;background:var(--dark-card) center/cover no-repeat;display:flex;align-items:center;justify-content:center;color:#555;font-size:1.6rem}
    .tc-body{padding:12px}
    .tc-name{font-size:.85rem;font-weight:700;color:var(--white);margin-bottom:6px}
    .tc-meta{display:flex;justify-content:space-between;font-size:.7rem;color:var(--text-muted);margin-bottom:10px}
    .pricing-row input.form-control{padding:7px 9px;font-size:.8rem}
    #pricingTable td{vertical-align:middle}
    .pr-remove{background:transparent;border:none;color:#EF5350;cursor:pointer;font-size:.85rem}
  </style>
</head>
<body>
<div class="sidebar-overlay" id="sidebarOverlay"></div>
<div class="admin-layout <?= adminRtlClass() ?>">
  <?php $activePage = 'reservations'; include_once __DIR__ . '/../includes/admin-sidebar.php'; ?>
  <main class="admin-main">
    <div class="admin-topbar">
      <div style="display:flex;align-items:center;gap:12px">
        <button id="sidebarToggle" class="topbar-btn"><i class="fas fa-bars"></i></button>
        <div class="topbar-title"><h2><?= tt('Réservation','حجز') ?> <?= htmlspecialchars($r['reference']) ?></h2><p><?= tt('Reçue le','تم الاستلام في') ?> <span dir="ltr"><?= date('d/m/Y à H:i', strtotime($r['created_at'])) ?></span></p></div>
      </div>
      <a href="reservations.php" class="topbar-btn" title="<?= tt('Retour à la liste','العودة إلى القائمة') ?>"><i class="fas fa-arrow-left"></i></a>
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
            <h3><i class="fas fa-user"></i> <?= tt('Informations client','معلومات العميل') ?></h3>
            <div class="info-grid">
              <div class="info-field"><label><?= tt('Nom complet','الاسم الكامل') ?></label><span><?= htmlspecialchars($nomClient) ?></span></div>
              <div class="info-field"><label><?= tt('Téléphone','الهاتف') ?></label><span dir="ltr"><?= htmlspecialchars($r['c_tel'] ?: '—') ?></span></div>
              <div class="info-field"><label>Email</label><span><?= htmlspecialchars($r['c_email'] ?: '—') ?></span></div>
              <div class="info-field"><label><?= tt('Ville','المدينة') ?></label><span><?= htmlspecialchars($r['c_ville'] ?: '—') ?></span></div>
            </div>
          </div>

          <!-- Informations événement -->
          <div class="panel">
            <h3><i class="fas fa-calendar-star"></i> <?= tt('Informations événement','معلومات المناسبة') ?></h3>
            <div class="info-grid">
              <div class="info-field"><label><?= tt("Type d'événement",'نوع المناسبة') ?></label><span><?= htmlspecialchars($r['type_nom'] ?: '—') ?></span></div>
              <div class="info-field"><label><?= tt('Date','التاريخ') ?></label><span dir="ltr"><?= $r['date_evenement'] ? date('d/m/Y', strtotime($r['date_evenement'])) : '—' ?></span></div>
              <div class="info-field"><label><?= tt('Heure','الوقت') ?></label><span dir="ltr"><?= substr($r['heure_debut'],0,5) ?></span></div>
              <div class="info-field"><label><?= tt("Nombre d'invités",'عدد الضيوف') ?></label><span><?= (int)$r['nbr_invites'] ?> <?= tt('personnes','أشخاص') ?></span></div>
              <div class="info-field" style="grid-column:1/-1"><label><?= tt('Lieu','المكان') ?></label><span><?= htmlspecialchars($r['lieu'] ?: '—') ?></span></div>
            </div>
          </div>

          <!-- Choix de la tente -->
          <div class="panel">
            <h3><i class="fas fa-campground"></i> <?= tt('Choix de la tente','اختيار الخيمة') ?></h3>
            <?php if (empty($tentes)): ?>
              <div class="message-box" style="border-left-color:#EF5350">
                <?= tt("Aucune tente active n'est configurée. Rends-toi dans",'لا توجد خيمة نشطة مهيأة. توجه إلى') ?>
                <a href="tentes.php" style="color:var(--gold)"><?= tt('Admin → Tentes','الإدارة ← الخيام') ?></a> <?= tt('pour créer et activer les fiches des 4 tentes.','لإنشاء وتفعيل بطاقات الخيام الأربع.') ?>
              </div>
            <?php else: ?>
              <?php if ($tenteChoisie): ?>
                <div class="message-box" style="margin-bottom:16px">
                  <strong style="color:var(--gold)"><?= tt('Tente sélectionnée','الخيمة المختارة') ?> : <?= htmlspecialchars($tenteChoisie['nom']) ?></strong>
                  <?php if (!$tenteChoisie['actif']): ?><span style="color:#EF5350"> (<?= tt('désactivée depuis','معطّلة منذ') ?>)</span><?php endif; ?>
                </div>
              <?php endif; ?>
              <div class="tentes-choice-grid">
                <?php foreach ($tentes as $t): $isSel = $tenteChoisie && $tenteChoisie['id'] == $t['id']; ?>
                <div class="tente-choice-card <?= $isSel ? 'selected' : '' ?>">
                  <div class="tc-photo" style="<?= $t['photo'] ? "background-image:url('../assets/uploads/".htmlspecialchars($t['photo'])."')" : '' ?>">
                    <?php if (!$t['photo']): ?><i class="fas fa-campground"></i><?php endif; ?>
                  </div>
                  <div class="tc-body">
                    <div class="tc-name"><?= htmlspecialchars($t['nom']) ?></div>
                    <div class="tc-meta">
                      <span dir="ltr"><?= $t['longueur'] !== null ? $t['longueur'].'m' : '—' ?> × <?= $t['largeur'] !== null ? $t['largeur'].'m' : '—' ?></span>
                      <span><?= $t['capacite_max'] !== null ? $t['capacite_max'].' '.tt('pers.','شخص') : '—' ?></span>
                    </div>
                    <form method="POST">
                      <input type="hidden" name="action" value="set_tente">
                      <input type="hidden" name="tente_id" value="<?= $t['id'] ?>">
                      <button type="submit" class="action-btn <?= $isSel ? 'confirm' : 'edit' ?>" style="margin-bottom:0">
                        <?= $isSel ? '<i class="fas fa-check"></i> ' . tt('Choisie','مختارة') : tt('Choisir','اختيار') ?>
                      </button>
                    </form>
                  </div>
                </div>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </div>

          <!-- Tarification manuelle -->
          <div class="panel">
            <h3><i class="fas fa-tags"></i> <?= tt('Tarification','التسعير') ?></h3>
            <p style="font-size:.78rem;color:var(--text-muted);margin-bottom:14px">
              <?= tt('Saisis librement la désignation, la quantité et le prix unitaire de chaque ligne. Le total est calculé automatiquement.','أدخل بحرية اسم البند والكمية والسعر لكل سطر. يُحسب المجموع تلقائياً.') ?>
            </p>
            <form method="POST" id="pricingForm">
              <input type="hidden" name="action" value="save_pricing">
              <table class="lignes-table" id="pricingTable" style="width:100%">
                <thead>
                  <tr style="font-size:.72rem;color:var(--text-muted);text-transform:uppercase">
                    <th style="text-align:left"><?= tt('Désignation','البند') ?></th><th style="width:70px"><?= tt('Qté','الكمية') ?></th><th style="width:110px"><?= tt('Prix unit. (MAD)','السعر (MAD)') ?></th><th style="width:110px;text-align:right"><?= tt('Total','الإجمالي') ?></th><th style="width:30px"></th>
                  </tr>
                </thead>
                <tbody id="pricingBody">
                  <?php if (empty($lignes)): ?>
                    <!-- ligne vide de départ -->
                  <?php else: foreach ($lignes as $l): ?>
                  <tr class="pricing-row">
                    <td><input type="text" name="designation[]" class="form-control pr-designation" value="<?= htmlspecialchars($l['designation']) ?>" required></td>
                    <td><input type="number" step="1" min="0" name="quantite[]" class="form-control pr-qte" value="<?= (float)$l['quantite'] ?>" oninput="recalcPricing()"></td>
                    <td><input type="number" step="0.01" min="0" name="prix_unitaire[]" class="form-control pr-prix" value="<?= (float)$l['prix_unitaire'] ?>" oninput="recalcPricing()"></td>
                    <td class="pr-total" style="text-align:right;color:var(--gold)">0 MAD</td>
                    <td><button type="button" class="pr-remove" onclick="this.closest('tr').remove();recalcPricing()"><i class="fas fa-times"></i></button></td>
                  </tr>
                  <?php endforeach; endif; ?>
                </tbody>
              </table>
              <button type="button" class="action-btn edit" style="margin-top:10px" onclick="addPricingRow()"><i class="fas fa-plus"></i> <?= tt('Ajouter une ligne','إضافة سطر') ?></button>
              <div style="display:flex;justify-content:flex-end;margin-top:14px;padding-top:14px;border-top:1px dashed var(--border)">
                <div style="text-align:right">
                  <div style="font-size:.75rem;color:var(--text-muted);text-transform:uppercase"><?= tt('Total','الإجمالي') ?></div>
                  <div style="font-size:1.3rem;font-weight:700;color:var(--gold)" id="pricingGrandTotal">0 MAD</div>
                </div>
              </div>
              <button type="submit" class="action-btn confirm" style="margin-top:14px"><i class="fas fa-save"></i> <?= tt('Enregistrer la tarification','حفظ التسعير') ?></button>
            </form>
          </div>

          <!-- Message du client -->
          <?php if (!empty($r['notes_client'])): ?>
          <div class="panel">
            <h3><i class="fas fa-comment-dots"></i> <?= tt('Message du client','رسالة العميل') ?></h3>
            <div class="message-box"><?= nl2br(htmlspecialchars($r['notes_client'])) ?></div>
          </div>
          <?php endif; ?>

          <!-- Notes internes -->
          <div class="panel">
            <h3><i class="fas fa-sticky-note"></i> <?= tt('Notes internes (privées)','ملاحظات داخلية (خاصة)') ?></h3>
            <div class="message-box"><?= $r['notes_internes'] ? nl2br(htmlspecialchars($r['notes_internes'])) : '<em style="color:#555">' . tt("Aucune note pour l'instant.",'لا توجد ملاحظة حالياً.') . '</em>' ?></div>
          </div>
        </div>

        <div>
          <!-- Actions -->
          <div class="panel">
            <h3><i class="fas fa-bolt"></i> <?= tt('Actions','إجراءات') ?></h3>
            <form method="POST">
              <input type="hidden" name="action" value="update_statut">
              <input type="hidden" name="statut" value="confirmee">
              <button type="submit" class="action-btn confirm"><i class="fas fa-check"></i> <?= t('confirmer') ?></button>
            </form>
            <form method="POST">
              <input type="hidden" name="action" value="update_statut">
              <input type="hidden" name="statut" value="annulee">
              <button type="submit" class="action-btn refuse"><i class="fas fa-times"></i> <?= tt('Refuser / Annuler','رفض / إلغاء') ?></button>
            </form>
            <button type="button" class="action-btn edit" onclick="document.getElementById('editForm').classList.toggle('show')">
              <i class="fas fa-edit"></i> <?= t('modifier') ?>
            </button>
            <?php if ($r['c_tel']): ?>
            <a href="https://wa.me/212<?= ltrim(preg_replace('/[^0-9]/','',$r['c_tel']), '0') ?>" target="_blank" class="action-btn contact">
              <i class="fab fa-whatsapp"></i> <?= tt('Contacter sur WhatsApp','التواصل عبر واتساب') ?>
            </a>
            <?php endif; ?>
            <?php if ($r['c_email']): ?>
            <a href="mailto:<?= htmlspecialchars($r['c_email']) ?>" class="action-btn contact">
              <i class="fas fa-envelope"></i> <?= tt('Contacter par email','التواصل بالبريد الإلكتروني') ?>
            </a>
            <?php endif; ?>
            <form method="POST" onsubmit="return confirm('<?= tt('Supprimer cette réservation ? Cette action est irréversible.','هل تريد حذف هذا الحجز؟ هذا الإجراء نهائي.') ?>')">
              <input type="hidden" name="action" value="delete">
              <button type="submit" class="action-btn delete"><i class="fas fa-trash"></i> <?= t('supprimer') ?></button>
            </form>

            <div id="editForm" class="edit-form">
              <form method="POST">
                <input type="hidden" name="action" value="update_infos">
                <label style="font-size:.72rem;color:var(--text-muted)"><?= tt("Date de l'événement",'تاريخ المناسبة') ?></label>
                <input type="date" name="date_evenement" value="<?= htmlspecialchars($r['date_evenement']) ?>">
                <label style="font-size:.72rem;color:var(--text-muted)"><?= tt('Heure','الوقت') ?></label>
                <input type="time" name="heure_debut" value="<?= substr($r['heure_debut'],0,5) ?>">
                <label style="font-size:.72rem;color:var(--text-muted)"><?= tt("Nombre d'invités",'عدد الضيوف') ?></label>
                <input type="number" name="nbr_invites" value="<?= (int)$r['nbr_invites'] ?>">
                <label style="font-size:.72rem;color:var(--text-muted)"><?= tt('Lieu','المكان') ?></label>
                <input type="text" name="lieu" value="<?= htmlspecialchars($r['lieu']) ?>">
                <label style="font-size:.72rem;color:var(--text-muted)"><?= tt('Notes internes','ملاحظات داخلية') ?></label>
                <textarea name="notes_internes" rows="3"><?= htmlspecialchars($r['notes_internes'] ?? '') ?></textarea>
                <button type="submit" class="action-btn edit" style="margin-bottom:0"><i class="fas fa-save"></i> <?= tt('Enregistrer les modifications','حفظ التعديلات') ?></button>
              </form>
            </div>
          </div>

          <!-- Infos système -->
          <div class="panel">
            <h3><i class="fas fa-info-circle"></i> <?= tt('Informations système','معلومات النظام') ?></h3>
            <div class="info-field" style="margin-bottom:12px"><label><?= tt('Référence','المرجع') ?></label><span><?= htmlspecialchars($r['reference']) ?></span></div>
            <div class="info-field" style="margin-bottom:12px"><label><?= tt('Créée le','أُنشئ في') ?></label><span dir="ltr"><?= date('d/m/Y à H:i', strtotime($r['created_at'])) ?></span></div>
            <div class="info-field"><label><?= tt('Dernière modification','آخر تعديل') ?></label><span dir="ltr"><?= $r['updated_at'] ? date('d/m/Y à H:i', strtotime($r['updated_at'])) : '—' ?></span></div>
          </div>

          <?php if ($devis): ?>
          <div class="panel">
            <h3><i class="fas fa-file-invoice"></i> <?= tt('Devis associé','عرض الأسعار المرتبط') ?></h3>
            <div class="info-field" style="margin-bottom:12px"><label><?= tt('Référence','المرجع') ?></label><span><?= htmlspecialchars($devis['reference']) ?></span></div>
            <div class="info-field"><label><?= tt('Total','الإجمالي') ?></label><span style="color:var(--gold);font-weight:700"><?= number_format($devis['montant_ht'],0,',',' ') ?> MAD</span></div>
            <?php if ($r['c_tel'] && !empty($lignes)):
                $texte = "Bonjour {$nomClient},\n\nVoici votre devis {$devis['reference']} — Traiteur EL MOUSSAOUI :\n";
                $texte .= "Événement : " . ($r['type_nom'] ?: '—') . " le " . ($r['date_evenement'] ? date('d/m/Y', strtotime($r['date_evenement'])) : '—') . "\n";
                if ($tenteChoisie) $texte .= "Tente : " . $tenteChoisie['nom'] . "\n";
                $texte .= "\nServices :\n";
                foreach ($lignes as $l) {
                    $texte .= "- {$l['designation']} (x{$l['quantite']}) : " . number_format($l['prix_unitaire']*$l['quantite'],0,',',' ') . " MAD\n";
                }
                $texte .= "\nTOTAL : " . number_format($devis['montant_ht'],0,',',' ') . " MAD\n\nMerci de nous confirmer votre accord.";
                $numTel = ltrim(preg_replace('/[^0-9]/','',$r['c_tel']), '0');
            ?>
            <a href="https://wa.me/212<?= $numTel ?>?text=<?= urlencode($texte) ?>" target="_blank" class="action-btn contact" style="margin-top:8px">
              <i class="fab fa-whatsapp"></i> <?= tt('Envoyer le devis par WhatsApp','إرسال عرض الأسعار عبر واتساب') ?>
            </a>
            <?php endif; ?>
            <a href="print_devis.php?id=<?= $devis['id'] ?>" target="_blank" style="display:block;text-align:center;margin-top:14px;font-size:.78rem;color:var(--gold)"><?= tt('Voir / imprimer le devis','عرض / طباعة عرض الأسعار') ?> →</a>
            <a href="devis.php" style="display:block;text-align:center;margin-top:8px;font-size:.78rem;color:var(--text-muted)"><?= tt('Voir dans Devis','عرضه في عروض الأسعار') ?> →</a>
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
function addPricingRow(designation, quantite, prix) {
  const tbody = document.getElementById('pricingBody');
  const tr = document.createElement('tr');
  tr.className = 'pricing-row';
  tr.innerHTML = `
    <td><input type="text" name="designation[]" class="form-control pr-designation" value="${designation||''}"></td>
    <td><input type="number" step="1" min="0" name="quantite[]" class="form-control pr-qte" value="${quantite||1}" oninput="recalcPricing()"></td>
    <td><input type="number" step="0.01" min="0" name="prix_unitaire[]" class="form-control pr-prix" value="${prix||0}" oninput="recalcPricing()"></td>
    <td class="pr-total" style="text-align:right;color:var(--gold)">0 MAD</td>
    <td><button type="button" class="pr-remove" onclick="this.closest('tr').remove();recalcPricing()"><i class="fas fa-times"></i></button></td>`;
  tbody.appendChild(tr);
  recalcPricing();
}
function recalcPricing() {
  let grandTotal = 0;
  document.querySelectorAll('#pricingBody .pricing-row').forEach(row => {
    const q = parseFloat(row.querySelector('.pr-qte').value) || 0;
    const p = parseFloat(row.querySelector('.pr-prix').value) || 0;
    const t = q * p;
    row.querySelector('.pr-total').textContent = t.toLocaleString('fr-FR') + ' MAD';
    grandTotal += t;
  });
  document.getElementById('pricingGrandTotal').textContent = grandTotal.toLocaleString('fr-FR') + ' MAD';
}
document.addEventListener('DOMContentLoaded', function () {
  if (document.querySelectorAll('#pricingBody .pricing-row').length === 0) addPricingRow();
  else recalcPricing();
});
document.getElementById('sidebarOverlay').addEventListener('click',()=>{
  document.getElementById('sidebar').classList.remove('open');
  document.getElementById('sidebarOverlay').classList.remove('show');
});
</script>
</body>
</html>
