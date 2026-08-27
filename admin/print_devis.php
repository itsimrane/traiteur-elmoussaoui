<?php
/**
 * Admin : print_devis.php
 * Page d'impression d'un devis
 */
require_once __DIR__ . '/../includes/config.php';
requireAdmin();

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0)
  die('ID invalide');

try {
  $stmt = $pdo->prepare("SELECT * FROM devis_generes WHERE id = ?");
  $stmt->execute([$id]);
  $d = $stmt->fetch();
} catch (Exception $e) {
  die('Erreur : ' . $e->getMessage());
}

if (!$d)
  die('Devis introuvable');

$nom = $d['nom_client'] ?: 'Client';
$date = !empty($d['date_evenement']) ? date('d/m/Y', strtotime($d['date_evenement'])) : '—';
$recu = date('d/m/Y', strtotime($d['created_at']));
$refNum = $d['numero'] ?: ('DEV-' . date('Y') . '-' . str_pad($id, 4, '0', STR_PAD_LEFT));
$services = json_decode($d['services_json'] ?? '[]', true) ?: [];
?>
<!DOCTYPE html>
<html lang="fr">

<head>
  <meta charset="UTF-8">
  <title>Devis <?= $refNum ?> — Traiteur EL MOUSSAOUI</title>
  <style>
    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }

    body {
      font-family: 'Segoe UI', Arial, sans-serif;
      color: #1A1A1A;
      background: #FFF;
      font-size: 14px;
    }

    .page {
      max-width: 800px;
      margin: 0 auto;
      padding: 40px;
    }

    /* Header */
    .header {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      margin-bottom: 40px;
      padding-bottom: 24px;
      border-bottom: 3px solid #D4AF37;
    }

    .logo-block .company {
      font-size: 1.6rem;
      font-weight: 800;
      color: #1A1A1A;
      letter-spacing: -0.5px;
    }

    .logo-block .company span {
      color: #D4AF37;
    }

    .logo-block .subtitle {
      font-size: .8rem;
      color: #888;
      margin-top: 2px;
    }

    .logo-block .ar {
      font-size: 1rem;
      color: #D4AF37;
      margin-top: 4px;
    }

    .devis-info {
      text-align: right;
    }

    .devis-info .ref {
      font-size: 1.1rem;
      font-weight: 700;
      color: #D4AF37;
    }

    .devis-info .date {
      font-size: .8rem;
      color: #888;
      margin-top: 4px;
    }

    .devis-info .statut {
      display: inline-block;
      margin-top: 8px;
      padding: 4px 14px;
      border-radius: 20px;
      font-size: .75rem;
      font-weight: 700;
      background: #FFF8E7;
      color: #D4AF37;
      border: 1px solid #D4AF37;
    }

    /* Sections */
    .section {
      margin-bottom: 28px;
    }

    .section-title {
      font-size: .7rem;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 1px;
      color: #888;
      margin-bottom: 12px;
      padding-bottom: 6px;
      border-bottom: 1px solid #EEE;
    }

    .grid-2 {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 20px;
    }

    .field label {
      display: block;
      font-size: .72rem;
      color: #888;
      margin-bottom: 3px;
    }

    .field span {
      font-size: .88rem;
      color: #1A1A1A;
      font-weight: 500;
    }

    /* Package card */
    .package-card {
      background: #FAFAFA;
      border: 1px solid #EEE;
      border-radius: 10px;
      padding: 20px;
    }

    .package-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 12px;
    }

    .package-name {
      font-size: 1.1rem;
      font-weight: 700;
      color: #1A1A1A;
    }

    .package-price {
      font-size: 1.4rem;
      font-weight: 800;
      color: #D4AF37;
    }

    .package-desc {
      font-size: .82rem;
      color: #666;
      margin-bottom: 14px;
    }

    .package-items {
      list-style: none;
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 6px;
    }

    .package-items li {
      display: flex;
      align-items: center;
      gap: 6px;
      font-size: .8rem;
      color: #555;
    }

    .package-items li::before {
      content: '✓';
      color: #D4AF37;
      font-weight: 700;
      flex-shrink: 0;
    }

    /* Message */
    .message-box {
      background: #F9F9F9;
      border-left: 3px solid #D4AF37;
      padding: 14px 18px;
      border-radius: 0 8px 8px 0;
      font-size: .85rem;
      color: #555;
      line-height: 1.6;
    }

    /* Total */
    .total-box {
      background: #1A1A1A;
      color: #FFF;
      border-radius: 10px;
      padding: 20px 24px;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .total-label {
      font-size: .85rem;
      color: #AAA;
    }

    .total-amount {
      font-size: 1.6rem;
      font-weight: 800;
      color: #D4AF37;
    }

    .total-note {
      font-size: .72rem;
      color: #666;
      margin-top: 4px;
    }

    /* Footer */
    .footer {
      margin-top: 40px;
      padding-top: 20px;
      border-top: 1px solid #EEE;
      display: flex;
      justify-content: space-between;
      font-size: .75rem;
      color: #AAA;
    }

    .footer strong {
      display: block;
      color: #888;
    }

    /* Conditions */
    .conditions {
      margin-top: 24px;
      font-size: .72rem;
      color: #AAA;
      line-height: 1.7;
    }

    .conditions strong {
      color: #888;
    }

    /* Signature */
    .signatures {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 40px;
      margin-top: 32px;
    }

    .sig-box {
      border-top: 1px solid #DDD;
      padding-top: 10px;
    }

    .sig-box label {
      font-size: .72rem;
      color: #888;
    }

    @media print {
      body {
        print-color-adjust: exact;
        -webkit-print-color-adjust: exact;
      }

      .no-print {
        display: none !important;
      }

      .page {
        padding: 20px;
      }
    }
  </style>
</head>

<body>
  <div class="page">

    <!-- Bouton imprimer (masqué à l'impression) -->
    <div class="no-print" style="text-align:right;margin-bottom:20px">
      <button onclick="window.print()"
        style="background:#D4AF37;color:#0D0D0D;padding:10px 24px;border:none;border-radius:8px;font-weight:700;cursor:pointer;font-size:.9rem;margin-right:8px">
        🖨️ Imprimer / PDF
      </button>
      <button onclick="window.close()"
        style="background:#EEE;color:#333;padding:10px 20px;border:none;border-radius:8px;cursor:pointer;font-size:.9rem">
        Fermer
      </button>
    </div>

    <!-- En-tête -->
    <div class="header">
      <div class="logo-block">
        <div class="company">TRAITEUR <span>EL MOUSSAOUI</span></div>
        <div class="ar">أفراح المساوي</div>
        <div class="subtitle">Organisation d'événements — Errachidia, Maroc</div>
        <div class="subtitle" style="margin-top:4px">📞 0626 986 533 | contact@traiteur-elmoussaoui.ma</div>
      </div>
      <div class="devis-info">
        <div class="ref">DEVIS <?= $refNum ?></div>
        <div class="date">Établi le : <?= $recu ?></div>
        <div class="date">Valable 30 jours</div>
        <div class="statut">
          <?= match ($d['statut'] ?? '') {
            'accepte' => '✅ CONFIRMÉ',
            'refuse' => '❌ REFUSÉ',
            'en_cours' => '🔄 EN COURS',
            default => '⏳ EN ATTENTE'
          } ?>
        </div>
      </div>
    </div>

    <!-- Informations client -->
    <div class="section">
      <div class="section-title">Informations client</div>
      <div class="grid-2">
        <div class="field"><label>Nom complet</label><span><?= htmlspecialchars($nom) ?></span></div>
        <div class="field"><label>Téléphone</label><span><?= htmlspecialchars($d['telephone'] ?? '—') ?></span></div>
        <div class="field"><label>Email</label><span><?= htmlspecialchars($d['email'] ?? '—') ?></span></div>
        <div class="field"><label>Ville</label><span><?= htmlspecialchars($d['ville'] ?? '—') ?></span></div>
        <div class="field"><label>Nombre
            d'invités</label><span><?= $d['nb_personnes'] ? $d['nb_personnes'] . ' personnes' : '—' ?></span></div>
      </div>
    </div>

    <!-- Détails événement -->
    <div class="section">
      <div class="section-title">Détails de l'événement</div>
      <div class="grid-2">
        <div class="field"><label>Type
            d'événement</label><span><?= ucfirst(str_replace('_', ' ', $d['type_evenement'] ?? '—')) ?></span></div>
        <div class="field"><label>Date souhaitée</label><span><?= $date ?></span></div>
      </div>
    </div>

    <!-- Services sélectionnés -->
    <?php if (!empty($services)): ?>
      <div class="section">
        <div class="section-title">Services sélectionnés</div>
        <div class="package-card">
          <ul class="package-items" style="grid-template-columns:1fr">
            <?php foreach ($services as $s): ?>
              <li style="justify-content:space-between">
                <span><?= htmlspecialchars($s['nom'] ?? '') ?></span>
                <span style="margin-left:auto;color:#D4AF37;font-weight:700">
                  <?= isset($s['prix']) && $s['prix'] > 0 ? number_format((float) $s['prix'], 0, ',', ' ') . ' MAD' : 'Sur devis' ?>
                </span>
              </li>
            <?php endforeach; ?>
          </ul>
        </div>
      </div>
    <?php endif; ?>

    <!-- Total -->
    <div class="section">
      <div class="total-box">
        <div>
          <div class="total-label">Montant total estimé</div>
          <div class="total-note">* Hors options supplémentaires</div>
        </div>
        <div style="text-align:right">
          <div class="total-amount"><?= number_format((float) $d['montant_total'], 0, ',', ' ') ?> MAD</div>
          <div style="font-size:.75rem;color:#AAA;margin-top:2px">Acompte 30% :
            <?= number_format((float) $d['montant_total'] * 0.3, 0, ',', ' ') ?> MAD</div>
        </div>
      </div>
    </div>

    <!-- Message client -->
    <?php if (!empty($d['notes'])): ?>
      <div class="section">
        <div class="section-title">Demandes spéciales</div>
        <div class="message-box"><?= nl2br(htmlspecialchars($d['notes'])) ?></div>
      </div>
    <?php endif; ?>

    <!-- Conditions -->
    <div class="conditions">
      <strong>Conditions générales :</strong>
      Un acompte de 30% est requis pour confirmer la réservation. Le solde est réglé 3 jours avant l'événement.
      En cas d'annulation plus de 30 jours avant l'événement, l'acompte est remboursé à 50%.
      Ce devis est valable 30 jours à compter de sa date d'émission.
    </div>

    <!-- Signatures -->
    <div class="signatures">
      <div class="sig-box">
        <label>Signature et cachet — Traiteur EL MOUSSAOUI</label>
        <div style="height:60px"></div>
      </div>
      <div class="sig-box">
        <label>Bon pour accord — Client (date et signature)</label>
        <div style="height:60px"></div>
      </div>
    </div>

    <!-- Footer -->
    <div class="footer">
      <div>
        <strong>Traiteur EL MOUSSAOUI</strong>
        Errachidia, Maroc — 0626 986 533
      </div>
      <div style="text-align:right">
        <strong>Référence</strong>
        <?= $refNum ?> — Page 1/1
      </div>
    </div>

  </div>
  <script src="../js/admin-lang.js"></script>
</body>

</html>