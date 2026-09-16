<?php
/**
 * API : update_media.php
 * Met à jour un média existant de la galerie (titre, catégorie, alt, vedette)
 * et remplace la photo physique seulement si un nouveau fichier est envoyé.
 */
require_once __DIR__ . '/../includes/config.php';
requireAdmin();

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Méthode non autorisée'], 405);
}

$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) jsonResponse(['success' => false, 'message' => 'ID invalide']);

$titre      = sanitize($_POST['titre'] ?? '');
$categorie  = (int)($_POST['categorie_id'] ?? 1);
$alt_text   = sanitize($_POST['alt_text'] ?? $titre);
$en_vedette = isset($_POST['en_vedette']) && $_POST['en_vedette'] === '1' ? 1 : 0;

if (!$titre) jsonResponse(['success' => false, 'message' => 'Le titre est obligatoire']);

$existing = $pdo->prepare("SELECT * FROM galerie WHERE id = ?");
$existing->execute([$id]);
$row = $existing->fetch();
if (!$row) jsonResponse(['success' => false, 'message' => 'Média introuvable']);

$nouveauFichier = null;

// Remplacement de la photo — uniquement si un nouveau fichier est fourni
if (!empty($_FILES['fichier']) && $_FILES['fichier']['error'] === UPLOAD_ERR_OK) {
    $file  = $_FILES['fichier'];
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime  = $finfo->file($file['tmp_name']);

    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];
    if (!array_key_exists($mime, $allowed)) {
        jsonResponse(['success' => false, 'message' => "Format non autorisé ($mime). Utilisez JPG, PNG ou WEBP."]);
    }
    if ($file['size'] > MAX_FILE_SIZE) {
        jsonResponse(['success' => false, 'message' => 'Fichier trop lourd (max 5 Mo)']);
    }

    $subDir = $row['type'] === 'video' ? 'galerie/thumbnails' : 'galerie';
    $uploadDir = UPLOAD_PATH . DIRECTORY_SEPARATOR . $subDir . DIRECTORY_SEPARATOR;
    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true)) {
        jsonResponse(['success' => false, 'message' => 'Impossible de créer le dossier : ' . $uploadDir]);
    }
    if (!is_writable($uploadDir)) {
        jsonResponse(['success' => false, 'message' => 'Dossier non accessible en écriture : ' . $uploadDir]);
    }

    $ext      = $allowed[$mime];
    $filename = 'img_' . uniqid() . '_' . time() . '.' . $ext;
    $dest     = $uploadDir . $filename;

    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        jsonResponse(['success' => false, 'message' => 'Échec de la sauvegarde du nouveau fichier']);
    }

    $nouveauFichier = $subDir . '/' . $filename;
}

try {
    if ($nouveauFichier) {
        $champFichier = $row['type'] === 'video' ? 'miniature' : 'fichier';
        $ancien = $row[$champFichier] ?? null;

        $pdo->prepare("UPDATE galerie SET titre=?, categorie_id=?, alt_text=?, en_vedette=?, `$champFichier`=?, updated_at=NOW() WHERE id=?")
            ->execute([$titre, $categorie, $alt_text, $en_vedette, $nouveauFichier, $id]);

        // Supprimer l'ancien fichier seulement après succès de la mise à jour BDD
        if ($ancien) {
            $ancienPath = UPLOAD_PATH . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $ancien);
            if (file_exists($ancienPath)) @unlink($ancienPath);
        }
    } else {
        $pdo->prepare("UPDATE galerie SET titre=?, categorie_id=?, alt_text=?, en_vedette=?, updated_at=NOW() WHERE id=?")
            ->execute([$titre, $categorie, $alt_text, $en_vedette, $id]);
    }
} catch (Exception $e) {
    if ($nouveauFichier) {
        $newPath = UPLOAD_PATH . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $nouveauFichier);
        if (file_exists($newPath)) @unlink($newPath);
    }
    jsonResponse(['success' => false, 'message' => 'Erreur BDD : ' . $e->getMessage()]);
}

jsonResponse([
    'success' => true,
    'message' => 'Média "' . $titre . '" mis à jour avec succès !',
]);
