<?php
require_once __DIR__ . '/../includes/config.php';
requireAdmin();

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) die('ID invalide');

try {
    $f = $pdo->prepare("SELECT * FROM factures WHERE id = ?");
    $f->execute([$id]);
    $f = $f->fetch();
} catch(Exception $e) { die('Erreur : ' . $e->getMessage()); }

if (!$f) die('Facture introuvable');

$dateEv  = !empty($f['date_evenement'])  ? date('d/m/Y', strtotime($f['date_evenement']))  : '—';
$dateEch = !empty($f['date_echeance'])   ? date('d/m/Y', strtotime($f['date_echeance']))   : '—';
$datePai = !empty($f['date_paiement'])   ? date('d/m/Y', strtotime($f['date_paiement']))   : null;
$dateEmi = date('d/m/Y', strtotime($f['created_at']));

$statutLabels = [
    'brouillon'           => ['label' => 'BROUILLON',       'color' => '#888'],
    'envoyee'             => ['label' => 'ENVOYÉE',          'color' => '#3B82F6'],
    'payee'               => ['label' => 'PAYÉE ✓',          'color' => '#16A34A'],
    'partiellement_payee' => ['label' => 'PARTIELLEMENT PAYÉE', 'color' => '#D97706'],
    'annulee'             => ['label' => 'ANNULÉE',          'color' => '#DC2626'],
];
$sc = $statutLabels[$f['statut']] ?? $statutLabels['brouillon'];

$fmt = fn($n) => number_format((float)$n, 2, ',', ' ') . ' MAD';
$fmtInt = fn($n) => number_format((float)$n, 0, ',', ' ') . ' MAD';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Facture <?= htmlspecialchars($f['numero']) ?> — Traiteur EL MOUSSAOUI</title>
  <style>
    *{box-sizing:border-box;margin:0;padding:0}
    body{font-family:'Segoe UI',Arial,sans-serif;color:#1A1A1A;background:#FFF;font-size:13px;line-height:1.5}
    .page{max-width:800px;margin:0 auto;padding:40px}
    .no-print{text-align:right;margin-bottom:20px}
    .btn-print{background:#D4AF37;color:#0D0D0D;padding:10px 24px;border:none;border-radius:8px;font-weight:700;cursor:pointer;font-size:.9rem;margin-right:8px}
    .btn-close{background:#EEE;color:#333;padding:10px 20px;border:none;border-radius:8px;cursor:pointer;font-size:.9rem}

    /* Header */
    .header{display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:36px;padding-bottom:24px;border-bottom:3px solid #D4AF37}
    .logo .company{font-size:1.8rem;font-weight:800;color:#111;letter-spacing:-1px}
    .logo .company em{color:#D4AF37;font-style:normal}
    .logo .ar{font-size:1rem;color:#D4AF37;margin-top:2px}
    .logo .contact{font-size:.75rem;color:#888;margin-top:6px;line-height:1.8}
    .fac-meta{text-align:right}
    .fac-title{font-size:2rem;font-weight:800;color:#D4AF37;letter-spacing:2px}
    .fac-num{font-size:.85rem;color:#888;margin-top:4px}
    .fac-date{font-size:.8rem;color:#AAA;margin-top:2px}
    .statut-stamp{display:inline-block;margin-top:10px;padding:5px 16px;border-radius:4px;font-size:.75rem;font-weight:800;letter-spacing:1px;border:2px solid;text-transform:uppercase}

    /* Client & event */
    .two-col{display:grid;grid-template-columns:1fr 1fr;gap:24px;margin-bottom:28px}
    .info-box{background:#FAFAFA;border:1px solid #EEE;border-radius:8px;padding:16px}
    .info-box h4{font-size:.65rem;text-transform:uppercase;letter-spacing:1px;color:#AAA;margin-bottom:10px;font-weight:700}
    .info-row{display:flex;gap:8px;margin-bottom:5px}
    .info-row label{font-size:.72rem;color:#AAA;width:100px;flex-shrink:0}
    .info-row span{font-size:.82rem;color:#1A1A1A;font-weight:500}

    /* Lignes */
    .lines-table{width:100%;border-collapse:collapse;margin-bottom:0}
    .lines-table thead th{padding:10px 14px;font-size:.68rem;text-transform:uppercase;letter-spacing:.5px;color:#888;background:#F5F5F5;border-bottom:2px solid #EEE;text-align:left}
    .lines-table thead th:last-child,.lines-table td:last-child{text-align:right}
    .lines-table tbody td{padding:12px 14px;font-size:.83rem;border-bottom:1px solid #EEE}
    .lines-table tbody tr:last-child td{border-bottom:none}
    .line-name{font-weight:600;color:#1A1A1A}
    .line-desc{font-size:.73rem;color:#888;margin-top:2px}

    /* Totaux */
    .totals-wrap{display:flex;justify-content:flex-end;margin-top:0;border-top:2px solid #EEE}
    .totals-box{width:320px;padding:20px 0}
    .total-row{display:flex;justify-content:space-between;padding:5px 0;font-size:.83rem}
    .total-row.sep{border-top:1px solid #EEE;margin-top:6px;padding-top:10px}
    .total-row.main{font-size:1.05rem;font-weight:800;color:#1A1A1A}
    .total-row.acompte{color:#D97706}
    .total-row.reste{color:#16A34A;font-weight:700;font-size:.9rem}
    .total-row.reste.due{color:#DC2626}

    /* Paiement */
    .payment-box{background:#FFFBF0;border:1px solid #F59E0B33;border-radius:8px;padding:16px;margin-top:20px}
    .payment-box h4{font-size:.68rem;text-transform:uppercase;letter-spacing:1px;color:#D97706;margin-bottom:8px;font-weight:700}
    .payment-methods{display:flex;gap:12px;flex-wrap:wrap;margin-top:8px}
    .payment-method{background:#FFF;border:1px solid #EEE;border-radius:6px;padding:6px 14px;font-size:.75rem;color:#555}

    /* Conditions */
    .conditions{margin-top:24px;padding:16px;background:#F9F9F9;border-radius:8px;font-size:.72rem;color:#888;line-height:1.8}
    .conditions strong{color:#555;display:block;margin-bottom:4px}

    /* Signatures */
    .signatures{display:grid;grid-template-columns:1fr 1fr;gap:40px;margin-top:32px;padding-top:20px;border-top:1px dashed #DDD}
    .sig-box label{font-size:.72rem;color:#AAA;display:block;margin-bottom:40px}
    .sig-line{border-top:1px solid #DDD;padding-top:6px;font-size:.7rem;color:#CCC}

    /* Footer */
    .fac-footer{margin-top:30px;padding-top:16px;border-top:1px solid #EEE;display:flex;justify-content:space-between;font-size:.72rem;color:#AAA}

    @media print{
      .no-print{display:none!important}
      body{print-color-adjust:exact;-webkit-print-color-adjust:exact}
      .page{padding:20px}
    }
  </style>
</head>
<body>
<div class="page">

  <div class="no-print">
    <button class="btn-print" onclick="window.print()">🖨️ Imprimer / PDF</button>
    <button class="btn-close" onclick="window.close()">Fermer</button>
  </div>

  <!-- Header -->
  <div class="header">
    <div class="logo">
      <div class="company">TRAITEUR <em>EL MOUSSAOUI</em></div>
      <div class="ar">أفراح المساوي</div>
      <div class="contact">
        📍 Errachidia, Maroc<br>
        📞 0626 986 533<br>
        ✉️ contact@traiteur-elmoussaoui.ma
      </div>
    </div>
    <div class="fac-meta">
      <div class="fac-title">FACTURE</div>
      <div class="fac-num"><?= htmlspecialchars($f['numero']) ?></div>
      <div class="fac-date">Émise le : <?= $dateEmi ?></div>
      <div class="fac-date">Échéance : <?= $dateEch ?></div>
      <div class="statut-stamp" style="color:<?= $sc['color'] ?>;border-color:<?= $sc['color'] ?>">
        <?= $sc['label'] ?>
      </div>
    </div>
  </div>

  <!-- Client + Événement -->
  <div class="two-col">
    <div class="info-box">
      <h4>Facturé à</h4>
      <div class="info-row"><label>Nom</label><span><?= htmlspecialchars($f['nom_client']) ?></span></div>
      <?php if ($f['telephone_client']): ?>
      <div class="info-row"><label>Téléphone</label><span><?= htmlspecialchars($f['telephone_client']) ?></span></div>
      <?php endif; ?>
      <?php if ($f['email_client']): ?>
      <div class="info-row"><label>Email</label><span><?= htmlspecialchars($f['email_client']) ?></span></div>
      <?php endif; ?>
    </div>
    <div class="info-box">
      <h4>Détails de l'événement</h4>
      <div class="info-row"><label>Type</label><span><?= ucfirst(str_replace('_',' ',$f['type_evenement']??'—')) ?></span></div>
      <div class="info-row"><label>Date</label><span><?= $dateEv ?></span></div>
      <div class="info-row"><label>Invités</label><span><?= $f['nb_personnes'] ? $f['nb_personnes'] . ' personnes' : '—' ?></span></div>
      <?php if ($f['package_nom']): ?>
      <div class="info-row"><label>Package</label><span style="color:#D4AF37;font-weight:600"><?= htmlspecialchars($f['package_nom']) ?></span></div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Lignes de facturation -->
  <table class="lines-table">
    <thead>
      <tr>
        <th style="width:50%">Description</th>
        <th>Qté</th>
        <th>Prix unitaire</th>
        <th>Total HT</th>
      </tr>
    </thead>
    <tbody>
      <tr>
        <td>
          <div class="line-name">
            <?= $f['package_nom'] ? 'Package ' . htmlspecialchars($f['package_nom']) : 'Prestation événementielle' ?>
          </div>
          <div class="line-desc">
            <?= ucfirst(str_replace('_',' ',$f['type_evenement']??'')) ?>
            <?= $f['date_evenement'] ? ' — ' . $dateEv : '' ?>
            <?= $f['nb_personnes'] ? ' — ' . $f['nb_personnes'] . ' invités' : '' ?>
          </div>
        </td>
        <td>1</td>
        <td><?= $fmt($f['montant_ht']) ?></td>
        <td><?= $fmt($f['montant_ht']) ?></td>
      </tr>
    </tbody>
  </table>

  <!-- Totaux -->
  <div class="totals-wrap">
    <div class="totals-box">
      <div class="total-row"><span>Sous-total HT</span><span><?= $fmt($f['montant_ht']) ?></span></div>
      <div class="total-row"><span>TVA (<?= (float)$f['tva'] ?>%)</span><span><?= $fmt($f['montant_tva']) ?></span></div>
      <div class="total-row sep main"><span>TOTAL TTC</span><span style="color:#D4AF37"><?= $fmt($f['montant_ttc']) ?></span></div>
      <?php if ($f['acompte'] > 0): ?>
      <div class="total-row acompte"><span>Acompte versé (30%)</span><span>- <?= $fmt($f['acompte']) ?></span></div>
      <div class="total-row reste <?= $f['reste_a_payer'] <= 0 ? '' : 'due' ?>">
        <span><?= $f['reste_a_payer'] <= 0 ? '✓ Soldée' : 'RESTE À PAYER' ?></span>
        <span><?= $f['reste_a_payer'] <= 0 ? 'Facture soldée' : $fmt($f['reste_a_payer']) ?></span>
      </div>
      <?php endif; ?>
      <?php if ($datePai): ?>
      <div class="total-row" style="color:#16A34A;margin-top:4px"><span>Payée le</span><span><?= $datePai ?></span></div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Modes de paiement -->
  <div class="payment-box">
    <h4>Modes de paiement acceptés</h4>
    <div class="payment-methods">
      <span class="payment-method">💵 Espèces</span>
      <span class="payment-method">🏦 Virement bancaire</span>
      <span class="payment-method">📝 Chèque</span>
    </div>
  </div>

  <?php if ($f['notes']): ?>
  <div style="margin-top:16px;padding:14px 16px;background:#FAFAFA;border-left:3px solid #D4AF37;border-radius:0 8px 8px 0">
    <div style="font-size:.68rem;text-transform:uppercase;letter-spacing:1px;color:#AAA;margin-bottom:4px">Notes</div>
    <div style="font-size:.82rem;color:#555"><?= nl2br(htmlspecialchars($f['notes'])) ?></div>
  </div>
  <?php endif; ?>

  <!-- Conditions -->
  <div class="conditions">
    <strong>Conditions générales :</strong>
    Un acompte de 30% est exigé pour confirmer la réservation.
    Le solde est dû 3 jours avant la date de l'événement.
    En cas d'annulation à moins de 30 jours, l'acompte reste acquis.
    Cette facture est valable 30 jours à compter de sa date d'émission.
  </div>

  <!-- Signatures -->
  <div class="signatures">
    <div class="sig-box">
      <label>Signature et cachet — Traiteur EL MOUSSAOUI</label>
      <div class="sig-line">Nom & Signature</div>
    </div>
    <div class="sig-box">
      <label>Bon pour accord — Client (date + signature)</label>
      <div class="sig-line">Nom & Signature</div>
    </div>
  </div>

  <!-- Footer -->
  <div class="fac-footer">
    <div>Traiteur EL MOUSSAOUI — Errachidia, Maroc — 0626 986 533</div>
    <div><?= htmlspecialchars($f['numero']) ?> — Page 1/1</div>
  </div>

</div>
<script src="../js/admin-lang.js"></script>
</body>
</html>
