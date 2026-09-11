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
if (strtotime($date) < strtotime('today')) jsonResponse(['success'=>false,'message'=>'La date de l\'événement ne peut pas être dans le passé']);

// ── Recalcul serveur du prix — SOURCE DE VÉRITÉ ─────────────────
// On ignore complètement les prix envoyés par le navigateur : on ne
// garde que les IDs des services choisis, et on recalcule tout
// depuis la base de données (protection contre la manipulation
// des prix côté client).
$serviceIds = array_map(fn($s) => (int)($s['id'] ?? 0), $services);
$serviceIds = array_filter($serviceIds);
$calcul = recalculerDevis($serviceIds, $nb, $pdo);
$lignesCalculees = $calcul['lignes'];
$total = $calcul['total'];

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
        ")->execute([$nomRaw ?: '—', $prenomRaw ?: 'Client', $email, $telephone, $ville ?: 'Errachidia']);
        $clientId = $pdo->lastInsertId();
    }

    // ── 2. Type d'événement ──────────────────────────────────────
    $typeId = 1;
    if ($typeRaw) {
        $tevt = $pdo->prepare("SELECT id FROM types_evenements WHERE slug=? OR nom LIKE ? LIMIT 1");
        $tevt->execute([$typeRaw, '%'.$typeRaw.'%']);
        $typeRow = $tevt->fetch();
        if ($typeRow) $typeId = $typeRow['id'];
    }

    // ── 3. Réservation ────────────────────────────────────────────
    $lastResId = (int)$pdo->query("SELECT COALESCE(MAX(id),0)+1 FROM reservations")->fetchColumn();
    $refRes = 'RES-' . date('Y') . '-' . str_pad($lastResId, 4, '0', STR_PAD_LEFT);

    $pdo->prepare("
        INSERT INTO reservations
            (reference, client_id, type_evenement_id, date_evenement, nbr_invites,
             lieu, statut, notes_client, montant_total, created_at)
        VALUES (?,?,?,?,?,?,'en_attente',?,?,NOW())
    ")->execute([$refRes, $clientId, $typeId, $date, $nb, $ville, $message, $total]);
    $reservationId = $pdo->lastInsertId();

    // ── 4. Devis lié à la réservation ────────────────────────────
    $lastDevisId = (int)$pdo->query("SELECT COALESCE(MAX(id),0)+1 FROM devis")->fetchColumn();
    $numero = 'DEV-' . date('Y') . '-' . str_pad($lastDevisId, 4, '0', STR_PAD_LEFT);

    $pdo->prepare("
        INSERT INTO devis
            (reference, client_id, type_evenement_id, date_evenement, nbr_invites, lieu,
             message, statut, montant_ht, tva_pct, reservation_id, date_expiration, created_at)
        VALUES (?,?,?,?,?,?,?,'recu',?,20,?, DATE_ADD(NOW(), INTERVAL 30 DAY), NOW())
    ")->execute([$numero, $clientId, $typeId, $date, $nb, $ville, $message, $total, $reservationId]);
    $devisId = $pdo->lastInsertId();

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