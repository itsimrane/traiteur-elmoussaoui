<?php
/**
 * API : toggle_vedette.php
 * Bascule le statut "en vedette" d'un média
 */
require_once __DIR__ . '/../includes/config.php';
requireAdmin();

header('Content-Type: application/json; charset=utf-8');

$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) jsonResponse(['success' => false, 'message' => 'ID invalide'], 400);

$pdo->prepare("UPDATE galerie SET en_vedette = NOT en_vedette WHERE id = ?")->execute([$id]);
$val = $pdo->prepare("SELECT en_vedette FROM galerie WHERE id = ?");
$val->execute([$id]);

jsonResponse(['success' => true, 'en_vedette' => (bool)$val->fetchColumn()]);
