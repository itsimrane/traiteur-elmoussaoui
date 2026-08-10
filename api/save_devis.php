<?php
require_once __DIR__ . '/../includes/config.php';
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success'=>false,'message'=>'Méthode non autorisée'],405);
}

$raw  = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!$data) jsonResponse(['success'=>false,'message'=>'Données invalides']);

// Générer numéro unique
$lastId = $pdo->query("SELECT COALESCE(MAX(id),0)+1 FROM devis_generes")->fetchColumn();
$numero = 'DEV-' . date('Y') . '-' . str_pad($lastId, 4, '0', STR_PAD_LEFT);

$servicesJson = json_encode($data['services'] ?? [], JSON_UNESCAPED_UNICODE);
$total        = (float)($data['total'] ?? 0);

try {
    $pdo->prepare("
        INSERT INTO devis_generes
            (numero,nom_client,telephone,email,type_evenement,date_evenement,ville,nb_personnes,services_json,montant_total,notes)
        VALUES (?,?,?,?,?,?,?,?,?,?,?)
    ")->execute([
        $numero,
        sanitize(($data['prenom']??'') . ' ' . ($data['nom']??'')),
        sanitize($data['telephone'] ?? ''),
        sanitize($data['email'] ?? ''),
        sanitize($data['type'] ?? ''),
        $data['date'] ?: null,
        sanitize($data['ville'] ?? ''),
        (int)($data['nb'] ?? 0) ?: null,
        $servicesJson,
        $total,
        sanitize($data['message'] ?? ''),
    ]);

    jsonResponse(['success'=>true,'numero'=>$numero,'id'=>$pdo->lastInsertId()]);
} catch(Exception $e) {
    jsonResponse(['success'=>false,'message'=>$e->getMessage()]);
}
