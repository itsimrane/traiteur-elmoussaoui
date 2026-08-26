<?php
require_once __DIR__ . '/../includes/config.php';
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success'=>false,'message'=>'Méthode non autorisée'],405);
}

$raw  = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!$data) jsonResponse(['success'=>false,'message'=>'Données invalides']);

// ── Numéro unique ──────────────────────────────────────────
$lastId = $pdo->query("SELECT COALESCE(MAX(id),0)+1 FROM devis_generes")->fetchColumn();
$numero = 'DEV-' . date('Y') . '-' . str_pad($lastId, 4, '0', STR_PAD_LEFT);

$prenomRaw = sanitize($data['prenom'] ?? '');
$nomRaw    = sanitize($data['nom']    ?? '');
$nomClient = trim($prenomRaw . ' ' . $nomRaw);
$telephone = sanitize($data['telephone'] ?? '');
$email     = sanitize($data['email']     ?? '');
$type      = sanitize($data['type']      ?? '');
$date      = !empty($data['date']) ? $data['date'] : null;
$ville     = sanitize($data['ville']     ?? '');
$nb        = (int)($data['nb'] ?? 0) ?: null;
$message   = sanitize($data['message']  ?? '');
$total     = (float)($data['total']     ?? 0);
$services  = json_encode($data['services'] ?? [], JSON_UNESCAPED_UNICODE);

$pdo->beginTransaction();
try {
    // ── 1. Insérer dans devis_generes (source principale) ─────
    $pdo->prepare("
        INSERT INTO devis_generes
            (numero, nom_client, telephone, email, type_evenement,
             date_evenement, ville, nb_personnes, services_json, montant_total, notes, statut, created_at)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,'nouveau',NOW())
    ")->execute([
        $numero, $nomClient, $telephone, $email, $type,
        $date, $ville, $nb, $services, $total, $message,
    ]);
    $devisId = $pdo->lastInsertId();

    // ── 2. Créer ou récupérer le client ───────────────────────
    $clientId = null;
    if ($telephone) {
        $existing = $pdo->prepare("SELECT id FROM clients WHERE telephone=? OR email=? LIMIT 1");
        $existing->execute([$telephone, $email ?: 'noemail@noemail.com']);
        $row = $existing->fetch();
        if ($row) {
            $clientId = $row['id'];
        } else {
            $pdo->prepare("
                INSERT INTO clients (nom, prenom, email, telephone, ville, source, created_at)
                VALUES (?,?,?,?,?,'site_web',NOW())
            ")->execute([$nomRaw, $prenomRaw, $email, $telephone, $ville]);
            $clientId = $pdo->lastInsertId();
        }
    }

    // ── 3. Insérer dans reservations (pour page Réservations) ─
    if ($clientId) {
        // Trouver type_evenement_id
        $tevt = $pdo->prepare("SELECT id FROM types_evenements WHERE slug=? OR nom LIKE ? LIMIT 1");
        $tevt->execute([$type, '%'.$type.'%']);
        $typeRow = $tevt->fetch();
        $typeId  = $typeRow ? $typeRow['id'] : 1;

        $refRes = 'RES-' . date('Y') . '-' . str_pad($devisId, 4, '0', STR_PAD_LEFT);
        $pdo->prepare("
            INSERT INTO reservations
                (reference, client_id, type_evenement_id, date_evenement,
                 nbr_invites, lieu, statut, notes_client, created_at)
            VALUES (?,?,?,?,?,?,'en_attente',?,NOW())
        ")->execute([
            $refRes, $clientId, $typeId,
            $date ?: date('Y-m-d', strtotime('+30 days')),
            $nb ?? 100, $ville, $message,
        ]);
    }

    // ── 4. Notification admin ──────────────────────────────────
    try {
        $pdo->prepare("
            INSERT INTO notifications (type, titre, message, lien, statut, created_at)
            VALUES ('devis','Nouveau devis reçu',?,'/admin/devis.php','non_lu',NOW())
        ")->execute(["Devis {$numero} — {$nomClient} — {$type} — " . number_format($total,0,',',' ') . " MAD"]);
    } catch(Exception $e) { /* table notifications optionnelle */ }

    $pdo->commit();
    jsonResponse(['success'=>true,'numero'=>$numero,'id'=>$devisId]);

} catch(Exception $e) {
    $pdo->rollBack();
    jsonResponse(['success'=>false,'message'=>$e->getMessage()]);
}