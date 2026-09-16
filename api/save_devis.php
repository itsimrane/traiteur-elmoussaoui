<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/pricing.php';
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success'=>false,'message'=>'Méthode non autorisée'],405);
}

$raw  = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!$data) jsonResponse(['success'=>false,'message'=>'Données invalides']);

$prenomRaw = sanitize($data['prenom'] ?? '');
$nomRaw    = sanitize($data['nom']    ?? '');
$telephone = sanitize($data['telephone'] ?? '');
$email     = sanitize($data['email']     ?? '');
$typeRaw   = sanitize($data['type']      ?? '');
$date      = !empty($data['date']) ? $data['date'] : date('Y-m-d', strtotime('+30 days'));
$ville     = sanitize($data['ville']     ?? '');
$nb        = (int)($data['nb'] ?? 0) ?: 100;
$message   = sanitize($data['message']  ?? '');
$services  = $data['services'] ?? [];

if (!$telephone) jsonResponse(['success'=>false,'message'=>'Téléphone requis']);
if ($nb <= 0) jsonResponse(['success'=>false,'message'=>'Le nombre d\'invités doit être supérieur à 0']);
$dateMin = strtotime('+7 days', strtotime('today'));
$dateMax = strtotime('+2 months', strtotime('today'));
$dateChoisie = strtotime($date);
if ($dateChoisie === false) jsonResponse(['success'=>false,'message'=>'Date invalide']);
if ($dateChoisie < $dateMin) jsonResponse(['success'=>false,'message'=>"La date de l'événement doit être au moins 7 jours à l'avance"]);
if ($dateChoisie > $dateMax) jsonResponse(['success'=>false,'message'=>"La date de l'événement ne peut pas dépasser 2 mois à l'avance"]);

// ── Services demandés — SANS PRIX ───────────────────────────────
// Le client ne voit et n'envoie plus aucun prix : on ne garde que les
// noms des services choisis. Le prix de chaque ligne sera fixé
// librement par l'administrateur au moment du traitement de la
// demande (voir admin/reservation_details.php). Le total démarre à 0
// et sera calculé automatiquement une fois les prix saisis par l'admin.
$serviceIds = array_map(fn($s) => (int)($s['id'] ?? 0), $services);
$serviceIds = array_filter($serviceIds);
$lignesCalculees = [];
if (!empty($serviceIds)) {
    $placeholders = implode(',', array_fill(0, count($serviceIds), '?'));
    $stmt = $pdo->prepare("SELECT id, nom FROM services WHERE id IN ($placeholders) AND actif=1");
    $stmt->execute($serviceIds);
    foreach ($stmt->fetchAll() as $s) {
        $lignesCalculees[] = ['service_id' => $s['id'], 'nom' => $s['nom'], 'quantite' => 1, 'prix_unitaire' => 0];
    }
}
$total = 0;

$pdo->beginTransaction();
try {
    // ── 1. Client : retrouver ou créer ──────────────────────────
    $existing = $pdo->prepare("SELECT id FROM clients WHERE telephone=? OR (email=? AND email<>'') LIMIT 1");
    $existing->execute([$telephone, $email ?: '__none__']);
    $row = $existing->fetch();
    if ($row) {
        $clientId = $row['id'];
    } else {
        $pdo->prepare("
            INSERT INTO clients (nom, prenom, email, telephone, ville, source, created_at)
            VALUES (?,?,?,?,?,'site_web',NOW())
        ")->execute([$nomRaw ?: '—', $prenomRaw ?: 'Client', $email ?: null, $telephone, $ville ?: 'Errachidia']);
        $clientId = $pdo->lastInsertId();
    }

    // ── 2. Type d'événement ──────────────────────────────────────
    $typeId = 1;
    $maxInvites = null;
    if ($typeRaw) {
        $tevt = $pdo->prepare("SELECT id, max_invites FROM types_evenements WHERE slug=? OR nom LIKE ? LIMIT 1");
        $tevt->execute([$typeRaw, '%'.$typeRaw.'%']);
        $typeRow = $tevt->fetch();
        if ($typeRow) { $typeId = $typeRow['id']; $maxInvites = $typeRow['max_invites']; }
    }

    // ── 2b. Vérification de la capacité maximale (source de vérité serveur) ──
    if ($maxInvites !== null && $nb > (int)$maxInvites) {
        $pdo->rollBack();
        jsonResponse(['success'=>false,'message'=>"Le nombre maximal d'invités pour ce type d'événement est de {$maxInvites} personnes."]);
    }

    // ── 2c. Vérification que la date n'est pas déjà confirmée ────────
    $dateOccupee = $pdo->prepare("
        SELECT COUNT(*) FROM reservations
        WHERE date_evenement = ? AND statut IN ('confirmee','en_cours') AND deleted_at IS NULL
    ");
    $dateOccupee->execute([$date]);
    if ((int)$dateOccupee->fetchColumn() > 0) {
        $pdo->rollBack();
        jsonResponse(['success'=>false,'message'=>"Cette date est déjà réservée. Merci de choisir une autre date."]);
    }

    // ── 3. Réservation ────────────────────────────────────────────
    // On insère d'abord avec une référence temporaire unique (basée sur
    // le temps), puis on la remplace par la référence finale calculée à
    // partir du VRAI id attribué — jamais une estimation, pour éviter
    // toute collision si d'anciens enregistrements ont été supprimés.
    $tempRefRes = 'TMP-' . uniqid();

    $pdo->prepare("
        INSERT INTO reservations
            (reference, client_id, type_evenement_id, date_evenement, nbr_invites,
             lieu, statut, notes_client, montant_total, created_at)
        VALUES (?,?,?,?,?,?,'en_attente',?,?,NOW())
    ")->execute([$tempRefRes, $clientId, $typeId, $date, $nb, $ville, $message, $total]);
    $reservationId = $pdo->lastInsertId();

    $refRes = 'RES-' . date('Y') . '-' . str_pad($reservationId, 4, '0', STR_PAD_LEFT);
    $pdo->prepare("UPDATE reservations SET reference=? WHERE id=?")->execute([$refRes, $reservationId]);

    // ── 4. Devis lié à la réservation ────────────────────────────
    $tempNumero = 'TMP-' . uniqid();

    $pdo->prepare("
        INSERT INTO devis
            (reference, client_id, type_evenement_id, date_evenement, nbr_invites, lieu,
             message, statut, montant_ht, tva_pct, reservation_id, date_expiration, created_at)
        VALUES (?,?,?,?,?,?,?,'recu',?,0,?, DATE_ADD(NOW(), INTERVAL 30 DAY), NOW())
    ")->execute([$tempNumero, $clientId, $typeId, $date, $nb, $ville, $message, $total, $reservationId]);
    $devisId = $pdo->lastInsertId();

    $numero = 'DEV-' . date('Y') . '-' . str_pad($devisId, 4, '0', STR_PAD_LEFT);
    $pdo->prepare("UPDATE devis SET reference=? WHERE id=?")->execute([$numero, $devisId]);

    // ── 5. Lignes de devis (quantité × prix unitaire réellement calculés) ─
    $ordre = 0;
    foreach ($lignesCalculees as $l) {
        $pdo->prepare("
            INSERT INTO devis_lignes (devis_id, designation, quantite, prix_unitaire, ordre)
            VALUES (?,?,?,?,?)
        ")->execute([$devisId, $l['nom'], $l['quantite'] ?: 1, $l['prix_unitaire'], $ordre++]);
    }

    // ── 6. Notification admin (best-effort, ne bloque pas l'envoi) ─
    try {
        $cols = $pdo->query("SHOW COLUMNS FROM notifications")->fetchAll(PDO::FETCH_COLUMN);
        if (in_array('type', $cols) && in_array('data', $cols)) {
            $payload = json_encode([
                'titre'   => 'Nouvelle réservation reçue',
                'message' => "Réservation {$refRes} — {$nomRaw} {$prenomRaw} — " . number_format($total,0,',',' ') . " MAD",
                'lien'    => '/admin/reservation_details.php?id=' . $reservationId,
            ], JSON_UNESCAPED_UNICODE);
            $pdo->prepare("INSERT INTO notifications (id, type, notifiable_type, notifiable_id, data, created_at, updated_at)
                            VALUES (UUID(), 'reservation', 'App\\\\Models\\\\Reservation', ?, ?, NOW(), NOW())")
                ->execute([$reservationId, $payload]);
        }
    } catch (Exception $e) { /* notifications optionnelles */ }

    $pdo->commit();

    jsonResponse([
        'success'        => true,
        'numero'         => $numero,
        'reservation_id' => $reservationId,
        'devis_id'       => $devisId,
        'total'          => $total,
        'lignes'         => $lignesCalculees,
    ]);

} catch (Exception $e) {
    $pdo->rollBack();
    jsonResponse(['success'=>false,'message'=>$e->getMessage()]);
}
