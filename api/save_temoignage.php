<?php
require_once __DIR__ . '/../includes/config.php';
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success'=>false,'message'=>'Méthode non autorisée'], 405);
}

$raw  = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!$data) jsonResponse(['success'=>false,'message'=>'Données invalides']);

$nomClient = sanitize($data['nom_client'] ?? '');
$ville     = sanitize($data['ville'] ?? '');
$type      = sanitize($data['type_evenement'] ?? '');
$note      = (int)($data['note'] ?? 0);
$contenu   = sanitize($data['contenu'] ?? '');

if ($nomClient === '') jsonResponse(['success'=>false,'message'=>'Le nom est requis']);
if ($contenu === '' || mb_strlen($contenu) < 10) jsonResponse(['success'=>false,'message'=>'Merci de détailler un peu votre avis (10 caractères minimum)']);
if ($note < 1 || $note > 5) jsonResponse(['success'=>false,'message'=>'Merci de choisir une note entre 1 et 5 étoiles']);

// Rapprochement avec un client existant (best-effort, non bloquant)
$clientId = null;
try {
    // On n'a pas de téléphone/email ici, donc on tente un match par nom exact seulement
    $c = $pdo->prepare("SELECT id FROM clients WHERE CONCAT(prenom,' ',nom) = ? LIMIT 1");
    $c->execute([$nomClient]);
    $row = $c->fetch();
    if ($row) $clientId = $row['id'];
} catch (Exception $e) {}

try {
    $pdo->prepare("
        INSERT INTO temoignages (client_id, nom_client, ville, contenu, note, type_evenement, statut, created_at)
        VALUES (?,?,?,?,?,?,'en_attente',NOW())
    ")->execute([$clientId, $nomClient, $ville ?: null, $contenu, $note, $type ?: null]);

    jsonResponse(['success' => true]);
} catch (Exception $e) {
    jsonResponse(['success' => false, 'message' => 'Erreur serveur, merci de réessayer.']);
}
