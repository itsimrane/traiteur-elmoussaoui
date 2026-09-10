<?php
/**
 * MIGRATION UNIQUE — à supprimer après usage.
 * Copie chaque ligne de devis_generes vers le vrai système
 * relationnel (clients → reservations → devis → devis_lignes),
 * sans rien supprimer ni modifier dans devis_generes.
 */
require_once __DIR__ . '/../includes/config.php';
requireAdmin();

if (($_GET['confirm'] ?? '') !== 'MIGRATE2026') {
    die('Ajoute ?confirm=MIGRATE2026 à la fin de l\'URL pour lancer la migration.');
}

header('Content-Type: text/html; charset=utf-8');
echo "<!DOCTYPE html><html><head><meta charset='utf-8'><style>
body{font-family:monospace;background:#111;color:#eee;padding:20px;line-height:1.7}
.ok{color:#4ade80} .err{color:#f87171} .info{color:#fbbf24} .skip{color:#888}
h1{color:#D4AF37}
</style></head><body>";
echo "<h1>🔄 Migration devis_generes → reservations / devis</h1>";

try {
    $rows = $pdo->query("SELECT * FROM devis_generes ORDER BY id ASC")->fetchAll();
} catch (Exception $e) {
    die("<p class='err'>❌ Impossible de lire devis_generes : " . htmlspecialchars($e->getMessage()) . "</p>");
}

echo "<p class='info'>📄 " . count($rows) . " devis à migrer.</p><hr>";

// Mappage des statuts devis_generes → reservations / devis
$mapStatutReservation = [
    'nouveau'  => 'en_attente',
    'en_cours' => 'en_cours',
    'accepte'  => 'confirmee',
    'refuse'   => 'annulee',
    ''         => 'en_attente',
];
$mapStatutDevis = [
    'nouveau'  => 'recu',
    'en_cours' => 'en_traitement',
    'accepte'  => 'accepte',
    'refuse'   => 'refuse',
    ''         => 'recu',
];

$migres = 0; $ignores = 0; $erreurs = [];

foreach ($rows as $dg) {

    // Déjà migré ? (on retrouve via le numéro stocké comme référence devis)
    $exists = $pdo->prepare("SELECT id FROM devis WHERE reference = ?");
    $exists->execute([$dg['numero']]);
    if ($exists->fetch()) {
        echo "<p class='skip'>⏭ #{$dg['id']} ({$dg['numero']}) déjà migré, ignoré.</p>";
        $ignores++;
        continue;
    }

    try {
        $pdo->beginTransaction();

        // ── 1. Client : retrouver ou créer ──────────────────────
        $clientId = null;
        if (!empty($dg['telephone'])) {
            $c = $pdo->prepare("SELECT id FROM clients WHERE telephone = ? OR (email = ? AND email <> '') LIMIT 1");
            $c->execute([$dg['telephone'], $dg['email'] ?: '__none__']);
            $row = $c->fetch();
            if ($row) {
                $clientId = $row['id'];
            } else {
                $nomComplet = trim($dg['nom_client'] ?? '');
                $parts = explode(' ', $nomComplet, 2);
                $prenom = $parts[0] ?? 'Client';
                $nom    = $parts[1] ?? '—';
                $pdo->prepare("
                    INSERT INTO clients (nom, prenom, email, telephone, ville, source, created_at)
                    VALUES (?,?,?,?,?, 'site_web', ?)
                ")->execute([
                    $nom, $prenom, $dg['email'] ?: '', $dg['telephone'],
                    $dg['ville'] ?: 'Errachidia', $dg['created_at']
                ]);
                $clientId = $pdo->lastInsertId();
            }
        }

        if (!$clientId) {
            throw new Exception("Pas de téléphone, impossible de créer/lier un client.");
        }

        // ── 2. Type d'événement : retrouver par nom/slug ────────
        $typeId = 1;
        if (!empty($dg['type_evenement'])) {
            $t = $pdo->prepare("SELECT id FROM types_evenements WHERE slug = ? OR nom LIKE ? LIMIT 1");
            $t->execute([$dg['type_evenement'], '%' . $dg['type_evenement'] . '%']);
            $row = $t->fetch();
            if ($row) $typeId = $row['id'];
        }

        $dateEvenement = $dg['date_evenement'] ?: date('Y-m-d', strtotime('+30 days'));

        // ── 3. Réservation : réutiliser si l'ancien save_devis.php ──
        //     en avait déjà créé une (même schéma de référence), sinon créer.
        $refRes = 'RES-' . date('Y', strtotime($dg['created_at'])) . '-' . str_pad($dg['id'], 4, '0', STR_PAD_LEFT);
        $statutRes = $mapStatutReservation[$dg['statut'] ?? ''] ?? 'en_attente';

        $existingRes = $pdo->prepare("SELECT id FROM reservations WHERE reference = ?");
        $existingRes->execute([$refRes]);
        $foundRes = $existingRes->fetch();

        if ($foundRes) {
            $reservationId = $foundRes['id'];
            // On met à jour le statut/montant au cas où ils étaient restés par défaut
            $pdo->prepare("UPDATE reservations SET statut=?, montant_total=? WHERE id=? AND montant_total=0")
                ->execute([$statutRes, $dg['montant_total'] ?: 0, $reservationId]);
        } else {
            $pdo->prepare("
                INSERT INTO reservations
                    (reference, client_id, type_evenement_id, date_evenement, nbr_invites,
                     lieu, statut, notes_client, montant_total, created_at, updated_at)
                VALUES (?,?,?,?,?,?,?,?,?,?,?)
            ")->execute([
                $refRes, $clientId, $typeId, $dateEvenement, $dg['nb_personnes'] ?: 100,
                $dg['ville'] ?: null, $statutRes, $dg['notes'] ?: null, $dg['montant_total'] ?: 0,
                $dg['created_at'], $dg['updated_at'] ?: $dg['created_at']
            ]);
            $reservationId = $pdo->lastInsertId();
        }

        // ── 4. Devis (lié à la réservation) ──────────────────────
        // Pas de TVA inventée sur les anciens devis : on garde le montant
        // exact déjà communiqué au client (tva_pct = 0 pour ne rien changer).
        $statutDevis = $mapStatutDevis[$dg['statut'] ?? ''] ?? 'recu';

        $pdo->prepare("
            INSERT INTO devis
                (reference, client_id, type_evenement_id, date_evenement, nbr_invites, lieu,
                 message, statut, montant_ht, tva_pct, reservation_id, created_at, updated_at)
            VALUES (?,?,?,?,?,?,?,?,?,0,?,?,?)
        ")->execute([
            $dg['numero'], $clientId, $typeId, $dateEvenement, $dg['nb_personnes'] ?: 100,
            $dg['ville'] ?: null, $dg['notes'] ?: null, $statutDevis, $dg['montant_total'] ?: 0,
            $reservationId, $dg['created_at'], $dg['updated_at'] ?: $dg['created_at']
        ]);
        $devisId = $pdo->lastInsertId();

        // ── 5. Lignes de devis (depuis services_json) ────────────
        $services = json_decode($dg['services_json'] ?? '[]', true) ?: [];
        $ordre = 0;
        foreach ($services as $s) {
            $nom  = $s['nom'] ?? 'Service';
            $prix = isset($s['prix']) ? (float)$s['prix'] : 0;
            $pdo->prepare("
                INSERT INTO devis_lignes (devis_id, designation, quantite, prix_unitaire, ordre)
                VALUES (?,?,1,?,?)
            ")->execute([$devisId, $nom, $prix, $ordre++]);
        }

        $pdo->commit();
        echo "<p class='ok'>✅ #{$dg['id']} ({$dg['numero']}) → réservation #$reservationId + devis #$devisId (" . count($services) . " ligne(s))</p>";
        $migres++;

    } catch (Exception $e) {
        $pdo->rollBack();
        $erreurs[] = "#{$dg['id']} : " . $e->getMessage();
        echo "<p class='err'>❌ #{$dg['id']} : " . htmlspecialchars($e->getMessage()) . "</p>";
    }
}

echo "<hr><h3>Résumé</h3>";
echo "<p class='ok'>✅ $migres migré(s)</p>";
echo "<p class='skip'>⏭ $ignores déjà présent(s)</p>";
if ($erreurs) echo "<p class='err'>❌ " . count($erreurs) . " erreur(s)</p>";
echo "<p class='info'>ℹ️ devis_generes n'a pas été modifié — tes anciennes données restent intactes en double, en sécurité.</p>";
echo "<hr><p class='err'><strong>⚠️ Supprime ce fichier (admin/migrate_devis_generes.php) maintenant que la migration est faite.</strong></p>";
echo "</body></html>";