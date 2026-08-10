<?php
/**
 * API : inline_upload.php
 * Upload inline d'une image directement depuis la page publique
 */
require_once __DIR__ . '/../includes/config.php';
requireAdmin();

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Méthode non autorisée'], 405);
}

$zone      = sanitize($_POST['zone'] ?? '');        // ex: galerie, accueil, services
$item_id   = (int)($_POST['item_id'] ?? 0);         // ID BDD si remplacement
$titre     = sanitize($_POST['titre'] ?? 'Photo');
$categorie = (int)($_POST['categorie_id'] ?? 1);

if (empty($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    $codes = [1=>'Trop grand (php.ini)',2=>'Trop grand (form)',3=>'Partiel',4=>'Aucun fichier',6=>'Pas de tmp',7=>'Écriture impossible'];
    $code  = $_FILES['image']['error'] ?? 4;
    jsonResponse(['success' => false, 'message' => 'Erreur upload : ' . ($codes[$code] ?? "Code $code")]);
}

$file  = $_FILES['image'];
$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime  = $finfo->file($file['tmp_name']);

$allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];
if (!array_key_exists($mime, $allowed)) {
    jsonResponse(['success' => false, 'message' => "Format non autorisé ($mime)"]);
}
if ($file['size'] > MAX_FILE_SIZE) {
    jsonResponse(['success' => false, 'message' => 'Fichier trop lourd (max 5 Mo)']);
}

// Dossier selon la zone
$subDir = match($zone) {
    'accueil'   => 'accueil',
    'services'  => 'services',
    'packages'  => 'packages',
    'apropos'   => 'apropos',
    default     => 'galerie',
};

$uploadDir = UPLOAD_PATH . DIRECTORY_SEPARATOR . $subDir . DIRECTORY_SEPARATOR;
if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

if (!is_writable($uploadDir)) {
    jsonResponse(['success' => false, 'message' => 'Dossier non accessible en écriture : ' . $uploadDir]);
}

$ext      = $allowed[$mime];
$filename = $subDir . '_' . uniqid() . '_' . time() . '.' . $ext;
$dest     = $uploadDir . $filename;

if (!move_uploaded_file($file['tmp_name'], $dest)) {
    jsonResponse(['success' => false, 'message' => 'Échec de la sauvegarde du fichier']);
}

$fichierPath = $subDir . '/' . $filename;
$publicUrl   = UPLOAD_URL . '/' . $fichierPath;

// Catégorie selon zone
$catMap = [
    'galerie'  => $categorie,
    'accueil'  => $categorie,
    'services' => $categorie,
    'packages' => $categorie,
    'apropos'  => $categorie,
];
$catId     = $catMap[$zone] ?? 1;
$enVedette = ($zone === 'accueil') ? 1 : 0;

try {
    if ($item_id > 0) {
        // Remplacement — récupérer l'ancien fichier pour le supprimer
        $old = $pdo->prepare("SELECT fichier FROM galerie WHERE id = ?");
        $old->execute([$item_id]);
        $oldRow = $old->fetch();
        if ($oldRow && $oldRow['fichier']) {
            $oldPath = UPLOAD_PATH . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $oldRow['fichier']);
            if (file_exists($oldPath)) unlink($oldPath);
        }
        $pdo->prepare("UPDATE galerie SET fichier = ?, titre = ?, updated_at = NOW() WHERE id = ?")
            ->execute([$fichierPath, $titre, $item_id]);
        $newId = $item_id;
    } else {
        // Nouvel insert
        $stmt = $pdo->prepare("
            INSERT INTO galerie (categorie_id, type, titre, fichier, alt_text, en_vedette, actif, created_at)
            VALUES (?, 'photo', ?, ?, ?, ?, 1, NOW())
        ");
        $stmt->execute([$catId, $titre, $fichierPath, $titre, $enVedette]);
        $newId = $pdo->lastInsertId();
    }
} catch (Exception $e) {
    unlink($dest);
    jsonResponse(['success' => false, 'message' => 'Erreur BDD : ' . $e->getMessage()]);
}

jsonResponse([
    'success'   => true,
    'message'   => 'Image enregistrée avec succès',
    'id'        => $newId,
    'url'       => $publicUrl,
    'fichier'   => $fichierPath,
]);
