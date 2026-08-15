<?php
/**
 * api/update_statut.php — Version finale
 * Gère toutes les tables avec vérification dynamique des colonnes
 */
require_once __DIR__ . '/../includes/config.php';
requireAdmin();
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success'=>false,'message'=>'Méthode non autorisée'],405);
}

$id     = (int)($_POST['id']     ?? 0);
$statut = sanitize($_POST['statut'] ?? '');
$table  = sanitize($_POST['table']  ?? 'devis_generes');

if ($id <= 0) jsonResponse(['success'=>false,'message'=>'ID invalide']);

$allowed_statuts = [
    'en_attente','confirme','confirme','refuse','en_cours',
    'annule','termine','confirmee','annulee','terminee',
    'nouveau','lu','traite','archive',
    'brouillon','publie','refuse',
    'partiellement_payee','payee','envoyee','recu'
];
$allowed_tables = [
    'devis_generes','reservations','devis',
    'contacts','temoignages','blog_articles',
    'galerie','factures','services','packages'
];

if (!in_array($statut, $allowed_statuts))
    jsonResponse(['success'=>false,'message'=>'Statut invalide: '.$statut]);
if (!in_array($table, $allowed_tables))
    jsonResponse(['success'=>false,'message'=>'Table invalide: '.$table]);

try {
    // Vérifier colonnes disponibles
    $cols = $pdo->query("SHOW COLUMNS FROM `$table`")->fetchAll(PDO::FETCH_COLUMN);

    $sets  = ['statut = ?'];
    $binds = [$statut];

    if (in_array('updated_at', $cols)) {
        $sets[] = 'updated_at = NOW()';
    }

    // Cas spécial contacts: lu_le
    if ($table === 'contacts' && $statut === 'lu' && in_array('lu_le', $cols)) {
        $sets[] = 'lu_le = NOW()';
    }

    $sql  = "UPDATE `$table` SET " . implode(', ', $sets) . " WHERE id = ?";
    $binds[] = $id;

    $stmt = $pdo->prepare($sql);
    $stmt->execute($binds);

    if ($stmt->rowCount() === 0)
        jsonResponse(['success'=>false,'message'=>'Enregistrement introuvable (id='.$id.')']);

    jsonResponse(['success'=>true,'statut'=>$statut,'id'=>$id,'message'=>'Statut mis à jour']);

} catch(Exception $e) {
    jsonResponse(['success'=>false,'message'=>'Erreur BDD: '.$e->getMessage()]);
}
