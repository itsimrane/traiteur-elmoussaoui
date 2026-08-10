<?php
require_once __DIR__ . '/../includes/config.php';
requireAdmin();

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Méthode non autorisée'], 405);
}

$type       = sanitize($_POST['type'] ?? 'photo');
$titre      = sanitize($_POST['titre'] ?? '');
$categorie  = (int)($_POST['categorie_id'] ?? 1);
$en_vedette = isset($_POST['en_vedette']) ? 1 : 0;
$alt_text   = sanitize($_POST['alt_text'] ?? $titre);

// Vérifier que le dossier uploads existe et est accessible
$baseUpload = UPLOAD_PATH;
if (!is_dir($baseUpload)) {
    if (!mkdir($baseUpload, 0755, true)) {
        jsonResponse(['success' => false, 'message' => 'Impossible de créer le dossier uploads : ' . $baseUpload]);
    }
}

if ($type === 'photo') {

    // Vérifier qu'un fichier a été envoyé
    if (empty($_FILES['fichier']) || $_FILES['fichier']['error'] !== UPLOAD_ERR_OK) {
        $errors = [
            1 => 'Fichier trop grand (php.ini)',
            2 => 'Fichier trop grand (formulaire)',
            3 => 'Fichier partiellement uploadé',
            4 => 'Aucun fichier envoyé',
            6 => 'Dossier temporaire manquant',
            7 => 'Écriture impossible',
        ];
        $code = $_FILES['fichier']['error'] ?? 4;
        jsonResponse(['success' => false, 'message' => 'Erreur upload : ' . ($errors[$code] ?? "Code $code")]);
    }

    $file  = $_FILES['fichier'];
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime  = $finfo->file($file['tmp_name']);

    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];
    if (!array_key_exists($mime, $allowed)) {
        jsonResponse(['success' => false, 'message' => "Format non autorisé ($mime). Utilisez JPG, PNG ou WEBP."]);
    }
    if ($file['size'] > MAX_FILE_SIZE) {
        jsonResponse(['success' => false, 'message' => 'Fichier trop lourd (max 5 Mo). Taille reçue : ' . round($file['size']/1024/1024, 2) . ' Mo']);
    }

    // Créer le dossier galerie
    $uploadDir = $baseUpload . DIRECTORY_SEPARATOR . 'galerie' . DIRECTORY_SEPARATOR;
    if (!is_dir($uploadDir)) {
        if (!mkdir($uploadDir, 0755, true)) {
            jsonResponse(['success' => false, 'message' => 'Impossible de créer le dossier galerie : ' . $uploadDir]);
        }
    }
    if (!is_writable($uploadDir)) {
        jsonResponse(['success' => false, 'message' => 'Dossier non accessible en écriture : ' . $uploadDir]);
    }

    $ext      = $allowed[$mime];
    $filename = 'img_' . uniqid() . '_' . time() . '.' . $ext;
    $dest     = $uploadDir . $filename;

    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        jsonResponse(['success' => false, 'message' => 'Échec de la sauvegarde. Dest : ' . $dest]);
    }

    // Vérifier que le fichier existe bien
    if (!file_exists($dest)) {
        jsonResponse(['success' => false, 'message' => 'Fichier sauvegardé mais introuvable : ' . $dest]);
    }

    $fichierPath = 'galerie/' . $filename;

    try {
        $stmt = $pdo->prepare("
            INSERT INTO galerie (categorie_id, type, titre, fichier, alt_text, en_vedette, actif, created_at)
            VALUES (?, 'photo', ?, ?, ?, ?, 1, NOW())
        ");
        $stmt->execute([$categorie, $titre, $fichierPath, $alt_text, $en_vedette]);
    } catch (Exception $e) {
        // Si BDD échoue, supprimer le fichier uploadé
        unlink($dest);
        jsonResponse(['success' => false, 'message' => 'Erreur BDD : ' . $e->getMessage()]);
    }

    jsonResponse([
        'success' => true,
        'message' => 'Photo "' . $titre . '" ajoutée avec succès !',
        'id'      => $pdo->lastInsertId(),
        'fichier' => UPLOAD_URL . '/' . $fichierPath,
        'path'    => $dest,
    ]);

} elseif ($type === 'video') {

    if (empty($_FILES['fichier']) || $_FILES['fichier']['error'] !== UPLOAD_ERR_OK) {
        $code = $_FILES['fichier']['error'] ?? 4;
        jsonResponse(['success' => false, 'message' => "Erreur upload vidéo (code $code). Vérifiez que la vidéo fait moins de 100 Mo."]);
    }

    $file  = $_FILES['fichier'];
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime  = $finfo->file($file['tmp_name']);

    $allowedVid = ['video/mp4' => 'mp4', 'video/webm' => 'webm', 'video/ogg' => 'ogv', 'video/quicktime' => 'mov'];
    if (!array_key_exists($mime, $allowedVid)) {
        jsonResponse(['success' => false, 'message' => "Format vidéo non autorisé ($mime). Utilisez MP4 ou WEBM."]);
    }
    if ($file['size'] > 100 * 1024 * 1024) {
        jsonResponse(['success' => false, 'message' => 'Vidéo trop lourde (max 100 Mo)']);
    }

    $uploadDir = $baseUpload . DIRECTORY_SEPARATOR . 'galerie' . DIRECTORY_SEPARATOR . 'videos' . DIRECTORY_SEPARATOR;
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

    $ext      = $allowedVid[$mime];
    $filename = 'vid_' . uniqid() . '_' . time() . '.' . $ext;
    $dest     = $uploadDir . $filename;

    // Miniature optionnelle
    $miniaturePath = null;
    if (!empty($_FILES['miniature']) && $_FILES['miniature']['error'] === UPLOAD_ERR_OK) {
        $mfinfo = new finfo(FILEINFO_MIME_TYPE);
        $mmime  = $mfinfo->file($_FILES['miniature']['tmp_name']);
        $mExts  = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        if (isset($mExts[$mmime])) {
            $mDir  = $baseUpload . DIRECTORY_SEPARATOR . 'galerie' . DIRECTORY_SEPARATOR . 'thumbnails' . DIRECTORY_SEPARATOR;
            if (!is_dir($mDir)) mkdir($mDir, 0755, true);
            $mName = 'thumb_' . uniqid() . '.' . $mExts[$mmime];
            if (move_uploaded_file($_FILES['miniature']['tmp_name'], $mDir . $mName)) {
                $miniaturePath = 'galerie/thumbnails/' . $mName;
            }
        }
    }

    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        jsonResponse(['success' => false, 'message' => 'Échec sauvegarde vidéo : ' . $dest]);
    }

    $fichierPath = 'galerie/videos/' . $filename;

    try {
        $stmt = $pdo->prepare("
            INSERT INTO galerie (categorie_id, type, titre, fichier, miniature, alt_text, en_vedette, actif, created_at)
            VALUES (?, 'video', ?, ?, ?, ?, ?, 1, NOW())
        ");
        $stmt->execute([$categorie, $titre, $fichierPath, $miniaturePath, $alt_text, $en_vedette]);
    } catch (Exception $e) {
        unlink($dest);
        jsonResponse(['success' => false, 'message' => 'Erreur BDD : ' . $e->getMessage()]);
    }

    jsonResponse([
        'success' => true,
        'message' => 'Vidéo "' . $titre . '" ajoutée avec succès !',
        'id'      => $pdo->lastInsertId(),
        'fichier' => UPLOAD_URL . '/' . $fichierPath,
    ]);
}

jsonResponse(['success' => false, 'message' => 'Type invalide'], 400);
