<?php
/**
 * API : update_statut.php
 * Change le statut d'un devis ou réservation
 */
require_once __DIR__ . '/../includes/config.php';
requireAdmin();

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Méthode non autorisée'], 405);
}

$id     = (int)($_POST['id'] ?? 0);
$statut = sanitize($_POST['statut'] ?? '');
$table  = sanitize($_POST['table'] ?? 'reservations');

if ($id <= 0) jsonResponse(['success' => false, 'message' => 'ID invalide']);

$allowed_statuts = ['en_attente', 'confirme', 'refuse', 'en_cours', 'annule', 'termine'];
$allowed_tables  = ['reservations', 'devis'];

if (!in_array($statut, $allowed_statuts)) {
    jsonResponse(['success' => false, 'message' => 'Statut invalide']);
}
if (!in_array($table, $allowed_tables)) {
    jsonResponse(['success' => false, 'message' => 'Table invalide']);
}

try {
    $stmt = $pdo->prepare("UPDATE `$table` SET statut = ?, updated_at = NOW() WHERE id = ?");
    $stmt->execute([$statut, $id]);

    if ($stmt->rowCount() === 0) {
        jsonResponse(['success' => false, 'message' => 'Enregistrement introuvable']);
    }

    jsonResponse([
        'success' => true,
        'message' => 'Statut mis à jour : ' . $statut,
        'id'      => $id,
        'statut'  => $statut,
    ]);
} catch (Exception $e) {
    jsonResponse(['success' => false, 'message' => 'Erreur BDD : ' . $e->getMessage()]);
}
