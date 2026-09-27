<?php
require_once __DIR__ . '/../includes/config.php';
header('Content-Type: application/json; charset=utf-8');

$mois   = (int)($_GET['mois'] ?? date('n'));
$annee  = (int)($_GET['annee'] ?? date('Y'));

if ($mois < 1 || $mois > 12) $mois = date('n');

$debut = sprintf('%04d-%02d-01', $annee, $mois);
$fin   = date('Y-m-t', strtotime($debut));

$dates = [];
try {
    // On passe par reservation_dates (une ligne par date choisie) plutôt
    // que reservations.date_evenement seule, pour qu'une réservation
    // multi-jours bloque bien CHACUNE de ses dates sur le calendrier.
    // Repli automatique sur reservations.date_evenement si la table
    // n'existe pas encore (migration pas lancée).
    try {
        $stmt = $pdo->prepare("
            SELECT rd.date_evenement, r.statut
            FROM reservation_dates rd
            JOIN reservations r ON r.id = rd.reservation_id
            WHERE rd.date_evenement BETWEEN ? AND ?
              AND r.deleted_at IS NULL
              AND r.statut IN ('en_attente','confirmee','en_cours')
        ");
        $stmt->execute([$debut, $fin]);
        $rows = $stmt->fetchAll();
    } catch (Exception $e) {
        $stmt = $pdo->prepare("
            SELECT date_evenement, statut
            FROM reservations
            WHERE date_evenement BETWEEN ? AND ?
              AND deleted_at IS NULL
              AND statut IN ('en_attente','confirmee','en_cours')
        ");
        $stmt->execute([$debut, $fin]);
        $rows = $stmt->fetchAll();
    }
    foreach ($rows as $r) {
        $d = $r['date_evenement'];
        // Une date "confirmée/en cours" prime toujours sur une simple "en attente"
        if (!isset($dates[$d]) || in_array($r['statut'], ['confirmee','en_cours'], true)) {
            $dates[$d] = in_array($r['statut'], ['confirmee','en_cours'], true) ? 'reserve' : 'attente';
        }
    }
} catch (Exception $e) {}

jsonResponse(['success' => true, 'dates' => $dates]);
