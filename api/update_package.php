<?php
/**
 * API : update_package.php
 * Modifie un package (prix, description, contenu, personnes, durée)
 */
require_once __DIR__ . '/../includes/config.php';
requireAdmin();

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Méthode non autorisée'], 405);
}

$id          = (int)($_POST['id'] ?? 0);
$prix        = (float)($_POST['prix'] ?? 0);
$description = sanitize($_POST['description'] ?? '');
$min_pers    = (int)($_POST['min_personnes'] ?? 0);
$max_pers    = (int)($_POST['max_personnes'] ?? 0);
$duree       = (float)($_POST['duree_heures'] ?? 0);
$mis_avant   = isset($_POST['mis_en_avant']) ? 1 : 0;
$contenu_raw = $_POST['contenu'] ?? '[]';

if ($id <= 0) jsonResponse(['success' => false, 'message' => 'ID invalide'], 400);
if ($prix <= 0) jsonResponse(['success' => false, 'message' => 'Prix invalide']);

$contenu_arr = json_decode($contenu_raw, true);
if (!is_array($contenu_arr)) jsonResponse(['success' => false, 'message' => 'Format contenu invalide']);

$contenu_arr  = array_values(array_filter(array_map('trim', $contenu_arr)));
$contenu_json = json_encode($contenu_arr, JSON_UNESCAPED_UNICODE);

$stmt = $pdo->prepare("
    UPDATE packages SET
        prix          = ?,
        description   = ?,
        min_personnes = ?,
        max_personnes = ?,
        duree_heures  = ?,
        mis_en_avant  = ?,
        contenu       = ?,
        updated_at    = NOW()
    WHERE id = ?
");
$stmt->execute([$prix, $description, $min_pers, $max_pers, $duree, $mis_avant, $contenu_json, $id]);

jsonResponse([
    'success' => true,
    'message' => 'Package mis à jour avec succès',
    'data'    => [
        'prix'    => number_format($prix, 0, ',', ' ') . ' MAD',
        'contenu' => $contenu_arr,
    ]
]);
