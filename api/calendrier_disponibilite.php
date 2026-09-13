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
    $stmt = $pdo->prepare("
        SELECT date_evenement, statut
        FROM reservations
        WHERE date_evenement BETWEEN ? AND ?
          AND deleted_at IS NULL
          AND statut IN ('en_attente','confirmee','en_cours')
    ");
    $stmt->execute([$debut, $fin]);
    foreach ($stmt->fetchAll() as $r) {
        $d = $r['date_evenement'];
        // Une date "confirmée/en cours" prime toujours sur une simple "en attente"
        if (!isset($dates[$d]) || in_array($r['statut'], ['confirmee','en_cours'], true)) {
            $dates[$d] = in_array($r['statut'], ['confirmee','en_cours'], true) ? 'reserve' : 'attente';
        }
    }
} catch (Exception $e) {}

jsonResponse(['success' => true, 'dates' => $dates]);
