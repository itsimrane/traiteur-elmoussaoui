<?php
/**
 * API : delete_media.php
 * Supprime un média (fichier physique + entrée BDD)
 */
require_once __DIR__ . '/../includes/config.php';
requireAdmin();

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Méthode non autorisée'], 405);
}

$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) jsonResponse(['success' => false, 'message' => 'ID invalide'], 400);

$stmt = $pdo->prepare("SELECT fichier, miniature FROM galerie WHERE id = ?");
$stmt->execute([$id]);
$media = $stmt->fetch();

if (!$media) jsonResponse(['success' => false, 'message' => 'Média introuvable'], 404);

// Supprimer les fichiers physiques
foreach (['fichier', 'miniature'] as $field) {
    if (!empty($media[$field])) {
        $path = UPLOAD_PATH . '/' . $media[$field];
        if (file_exists($path)) unlink($path);
    }
}

$pdo->prepare("DELETE FROM galerie WHERE id = ?")->execute([$id]);

jsonResponse(['success' => true, 'message' => 'Média supprimé avec succès']);
