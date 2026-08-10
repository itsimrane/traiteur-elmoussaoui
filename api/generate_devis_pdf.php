<?php
require_once __DIR__ . '/../includes/config.php';

$raw  = $_GET['data'] ?? '';
$data = json_decode($raw, true);
if (!$data) die('Données invalides');

$numero  = htmlspecialchars($data['numero'] ?? 'DEV-0000');
$prenom  = htmlspecialchars($data['prenom'] ?? '');
$nom     = htmlspecialchars($data['nom']    ?? '');
$tel     = htmlspecialchars($data['telephone'] ?? '');
$email   = htmlspecialchars($data['email']   ?? '');
$type    = htmlspecialchars($data['type']    ?? '');
$date    = !empty($data['date'])  ? date('d/m/Y', strtotime($data['date'])) : '—';
$ville   = htmlspecialchars($data['ville']   ?? '');
$nb      = (int)($data['nb'] ?? 0);
$total   = (float)($data['total'] ?? 0);
$message = htmlspecialchars($data['message'] ?? '');
$services= $data['services'] ?? [];
$dateEmission = date('d/m/Y');

$typesLabels = [
    'mariage'=>'Mariage','fiancailles'=>'Fiançailles','circoncision'=>'Circoncision',
    'anniversaire'=>'Anniversaire','reception_pro'=>'Réception Pro',
    'buffet'=>'Buffet','religieux'=>'Cérémonie religieuse',
];
$typeLabel = $typesLabels[$type] ?? $type;
$tierLabels = ['bronze'=>'🥉 Bronze','argent'=>'🥈 Argent','or'=>'🥇 Or'];
$fmt = fn($n) => number_format($n,0,',',' ') . ' MAD';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Devis <?= $numero ?> — Traiteur EL MOUSSAOUI</title>
  <style>
    *{box-sizing:border-box;margin:0;padding:0}
    body{font-family:'Segoe UI',Arial,sans-serif;color:#1A1A1A;background:#FFF;font-size:13.5px;line-height:1.5}
    .page{max-width:820px;margin:0 auto;padding:40px}
    .no-print{text-align:right;margin-bottom:20px}
    .btn-print{background:#D4AF37;color:#0D0D0D;padding:10px 24px;border:none;border-radius:8px;font-weight:700;cursor:pointer;font-size:.9rem;margin-right:8px}
    .btn-close{background:#EEE;color:#333;padding:10px 20px;border:none;border-radius:8px;cursor:pointer}

    .header{display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:36px;padding-bottom:24px;border-bottom:3px solid #D4AF37}
    .logo .company{font-size:1.7rem;font-weight:800;color:#111}
    .logo .company em{color:#D4AF37;font-style:normal}
    .logo .ar{font-size:1rem;color:#D4AF37;margin-top:3px}
    .logo .contact{font-size:.75rem;color:#888;margin-top:8px;line-height:1.9}
    .fac-meta{text-align:right}
    .fac-title{font-size:2rem;font-weight:800;color:#D4AF37;letter-spacing:2px}
    .fac-num{font-size:.88rem;color:#888;margin-top:4px}
    .fac-date{font-size:.78rem;color:#AAA;margin-top:2px}
    .validity{display:inline-block;margin-top:8px;padding:4px 14px;border-radius:4px;font-size:.72rem;font-weight:800;letter-spacing:.5px;border:1.5px solid #D4AF37;color:#D4AF37}

    .two-col{display:grid;grid-template-columns:1fr 1fr;gap:24px;margin-bottom:28px}
    .info-box{background:#FAFAFA;border:1px solid #EEE;border-radius:10px;padding:18px}
    .info-box h4{font-size:.65rem;text-transform:uppercase;letter-spacing:1px;color:#AAA;margin-bottom:12px;font-weight:700}
    .info-row{display:flex;gap:8px;margin-bottom:6px}
    .info-row label{font-size:.72rem;color:#AAA;width:110px;flex-shrink:0}
    .info-row span{font-size:.83rem;color:#1A1A1A;font-weight:500}

    /* Services table */
    .section-title{font-size:.65rem;text-transform:uppercase;letter-spacing:1px;color:#AAA;margin-bottom:10px;font-weight:700;padding-bottom:6px;border-bottom:1px solid #EEE}
    table{width:100%;border-collapse:collapse;margin-bottom:0}
    thead th{padding:10px 14px;font-size:.68rem;text-transform:uppercase;letter-spacing:.5px;color:#888;background:#F5F5F5;border-bottom:2px solid #EEE;text-align:left}
    thead th:last-child{text-align:right}
    tbody td{padding:12px 14px;font-size:.85rem;border-bottom:1px solid #EEE}
    tbody td:last-child{text-align:right;color:#D4AF37;font-weight:700}
    tbody tr:last-child td{border-bottom:none}
    .tier-badge{display:inline-block;padding:2px 8px;border-radius:6px;font-size:.62rem;font-weight:700}
    .tier-bronze{background:#FBF5EC;color:#A0522D}
    .tier-argent{background:#F5F5F5;color:#666}
    .tier-or{background:#FBF7E8;color:#B8860B}

    /* Totaux */
    .totals-wrap{display:flex;justify-content:flex-end;border-top:2px solid #EEE;margin-top:0}
    .totals-box{width:300px;padding:18px 0}
    .total-row{display:flex;justify-content:space-between;padding:5px 0;font-size:.85rem}
    .total-row.main{font-size:1.05rem;font-weight:800;border-top:1px solid #EEE;margin-top:6px;padding-top:10px}

    /* Conditions */
    .conditions{margin-top:20px;padding:14px 16px;background:#FAFAFA;border-radius:8px;font-size:.72rem;color:#888;line-height:1.8}
    .conditions strong{color:#555;display:block;margin-bottom:4px}

    /* Sigs */
    .signatures{display:grid;grid-template-columns:1fr 1fr;gap:40px;margin-top:28px;padding-top:18px;border-top:1px dashed #DDD}
    .sig-box label{font-size:.72rem;color:#AAA;display:block;margin-bottom:38px}
    .sig-line{border-top:1px solid #DDD;padding-top:6px;font-size:.7rem;color:#CCC}

    .fac-footer{margin-top:26px;padding-top:14px;border-top:1px solid #EEE;display:flex;justify-content:space-between;font-size:.7rem;color:#AAA}

    .message-box{background:#FAFAFA;border-left:3px solid #D4AF37;padding:12px 16px;border-radius:0 8px 8px 0;font-size:.82rem;color:#555;line-height:1.7;margin-top:20px}

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
    <button class="btn-print" onclick="window.print()">🖨️ Imprimer / Télécharger PDF</button>
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
      <div class="fac-title">DEVIS</div>
      <div class="fac-num"><?= $numero ?></div>
      <div class="fac-date">Émis le : <?= $dateEmission ?></div>
      <div class="fac-date">Valable 30 jours</div>
      <div class="validity">EN ATTENTE DE CONFIRMATION</div>
    </div>
  </div>

  <!-- Client + Événement -->
  <div class="two-col">
    <div class="info-box">
      <h4>Client</h4>
      <div class="info-row"><label>Nom</label><span><?= $prenom . ' ' . $nom ?></span></div>
      <div class="info-row"><label>Téléphone</label><span dir="ltr"><?= $tel ?></span></div>
      <?php if ($email): ?><div class="info-row"><label>Email</label><span><?= $email ?></span></div><?php endif; ?>
    </div>
    <div class="info-box">
      <h4>Événement</h4>
      <div class="info-row"><label>Type</label><span><?= $typeLabel ?></span></div>
      <div class="info-row"><label>Date</label><span dir="ltr"><?= $date ?></span></div>
      <div class="info-row"><label>Ville</label><span><?= $ville ?></span></div>
      <div class="info-row"><label>Invités</label><span dir="ltr"><?= $nb ?> personnes</span></div>
    </div>
  </div>

  <!-- Services -->
  <div class="section-title" style="margin-top:4px">Services sélectionnés</div>
  <table>
    <thead>
      <tr>
        <th style="width:50%">Prestation</th>
        <th>Catégorie</th>
        <th style="text-align:right">Prix unitaire</th>
        <th style="text-align:right">Montant</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($services as $s):
        $prix = (float)($s['prix'] ?? 0);
        $tier = $s['tier'] ?? 'bronze';
      ?>
      <tr>
        <td><?= htmlspecialchars($s['nom']) ?></td>
        <td><span class="tier-badge tier-<?= $tier ?>"><?= $tierLabels[$tier] ?? ucfirst($tier) ?></span></td>
        <td dir="ltr"><?= $prix > 0 ? $fmt($prix) : 'Sur devis' ?></td>
        <td dir="ltr"><?= $prix > 0 ? $fmt($prix) : '—' ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <!-- Totaux -->
  <div class="totals-wrap">
    <div class="totals-box">
      <div class="total-row main">
        <span style="font-weight:800">TOTAL ESTIMÉ</span>
        <span style="color:#D4AF37" dir="ltr"><?= $fmt($total) ?></span>
      </div>
      <div class="total-row" style="color:#888;font-size:.78rem">
        <span>Acompte requis (30%)</span>
        <span dir="ltr"><?= $fmt($total * 0.3) ?></span>
      </div>
      <div class="total-row" style="color:#888;font-size:.78rem">
        <span>Solde à régler avant l'événement</span>
        <span dir="ltr"><?= $fmt($total * 0.7) ?></span>
      </div>
    </div>
  </div>

  <?php if ($message): ?>
  <div class="message-box">
    <strong style="font-size:.68rem;color:#AAA;text-transform:uppercase;letter-spacing:.5px;display:block;margin-bottom:4px">Demandes spéciales</strong>
    <?= nl2br($message) ?>
  </div>
  <?php endif; ?>

  <!-- Conditions -->
  <div class="conditions">
    <strong>Conditions générales :</strong>
    Ce devis est établi sur la base des services sélectionnés et est valable 30 jours.
    Un acompte de 30% est requis pour confirmer la réservation.
    Le solde est dû 3 jours avant la date de l'événement.
    Les prix peuvent être ajustés selon les conditions définitives (lieu, traiteur exact, etc.).
    En cas d'annulation à moins de 30 jours, l'acompte reste acquis.
  </div>

  <!-- Signatures -->
  <div class="signatures">
    <div class="sig-box">
      <label>Signature et cachet — Traiteur EL MOUSSAOUI</label>
      <div class="sig-line">Bon pour accord</div>
    </div>
    <div class="sig-box">
      <label>Bon pour accord — Client (date + signature)</label>
      <div class="sig-line">Lu et approuvé</div>
    </div>
  </div>

  <div class="fac-footer">
    <div>Traiteur EL MOUSSAOUI · Errachidia, Maroc · 0626 986 533</div>
    <div><?= $numero ?> — Page 1/1</div>
  </div>
</div>
</body>
</html>
