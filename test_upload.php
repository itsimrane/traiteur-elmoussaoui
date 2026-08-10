<?php
/**
 * TEST UPLOAD — Traiteur EL MOUSSAOUI
 * Ouvrir : http://localhost/Traiteur_Elmoussaoui/test_upload.php
 * SUPPRIMER après utilisation !
 */
require_once __DIR__ . '/includes/config.php';

echo '<style>
body{font-family:monospace;background:#0D0D0D;color:#CCC;padding:30px}
h2{color:#D4AF37;margin:16px 0 8px}
.ok{color:#66BB6A}.err{color:#EF5350}.warn{color:#FFA726}
pre{background:#111;padding:14px;border-radius:8px;border:1px solid #333}
form{background:#1A1A2E;padding:20px;border-radius:10px;border:1px solid #333;margin-bottom:20px}
input[type=file]{color:#CCC;margin:10px 0;display:block}
button{background:#D4AF37;color:#0D0D0D;padding:10px 24px;border:none;border-radius:8px;cursor:pointer;font-weight:700;font-size:1rem}
</style>';

echo '<h1 style="color:#D4AF37">🔧 Test Upload — EL MOUSSAOUI</h1>';

// ── Infos config ───────────────────────────────────────────
echo '<h2>1. Configuration</h2><pre>';
echo 'UPLOAD_PATH : ' . UPLOAD_PATH . "\n";
echo 'UPLOAD_URL  : ' . UPLOAD_URL . "\n";
echo 'Dossier existe : ' . (is_dir(UPLOAD_PATH) ? '<span class="ok">OUI</span>' : '<span class="err">NON</span>') . "\n";
echo 'Dossier galerie : ' . (is_dir(UPLOAD_PATH . '/galerie') ? '<span class="ok">OUI</span>' : '<span class="warn">NON (sera créé)</span>') . "\n";
echo 'Accessible en écriture : ' . (is_writable(UPLOAD_PATH) ? '<span class="ok">OUI</span>' : '<span class="err">NON ← PROBLÈME ICI</span>') . "\n";
echo 'PHP upload_max_filesize : ' . ini_get('upload_max_filesize') . "\n";
echo 'PHP post_max_size       : ' . ini_get('post_max_size') . "\n";
echo '</pre>';

// ── Test BDD galerie ───────────────────────────────────────
echo '<h2>2. Test BDD</h2>';
try {
    $count = $pdo->query("SELECT COUNT(*) FROM galerie")->fetchColumn();
    echo '<p class="ok">✅ Table galerie accessible — ' . $count . ' entrée(s)</p>';
    $cat = $pdo->query("SELECT id, nom FROM categories_galerie LIMIT 1")->fetch();
    echo '<p class="ok">✅ Catégorie test : ID=' . $cat['id'] . ' nom=' . $cat['nom'] . '</p>';
    $catId = $cat['id'];
} catch(Exception $e) {
    echo '<p class="err">❌ Erreur BDD : ' . $e->getMessage() . '</p>';
    $catId = 1;
}

// ── Traitement de l'upload test ────────────────────────────
$result = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_FILES['photo'])) {
    echo '<h2>3. Résultat de l\'upload</h2><pre>';
    $file = $_FILES['photo'];
    echo 'Nom original : ' . $file['name'] . "\n";
    echo 'Taille       : ' . round($file['size']/1024, 1) . ' Ko' . "\n";
    echo 'Type MIME    : ' . $file['type'] . "\n";
    echo 'Erreur PHP   : ' . $file['error'] . ' (' . ($file['error'] === 0 ? '<span class="ok">OK</span>' : '<span class="err">ERREUR</span>') . ')' . "\n";
    echo 'Fichier tmp  : ' . $file['tmp_name'] . "\n";
    echo 'Tmp existe   : ' . (file_exists($file['tmp_name']) ? '<span class="ok">OUI</span>' : '<span class="err">NON</span>') . "\n\n";

    if ($file['error'] === 0) {
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime  = $finfo->file($file['tmp_name']);
        echo 'MIME réel    : ' . $mime . "\n";

        $uploadDir = UPLOAD_PATH . DIRECTORY_SEPARATOR . 'galerie' . DIRECTORY_SEPARATOR;
        echo 'Dossier dest : ' . $uploadDir . "\n";

        if (!is_dir($uploadDir)) {
            $created = mkdir($uploadDir, 0755, true);
            echo 'Création dir : ' . ($created ? '<span class="ok">OK</span>' : '<span class="err">ÉCHEC</span>') . "\n";
        }

        $filename = 'test_' . time() . '.jpg';
        $dest     = $uploadDir . $filename;
        echo 'Dest fichier : ' . $dest . "\n";

        $moved = move_uploaded_file($file['tmp_name'], $dest);
        echo 'move_file    : ' . ($moved ? '<span class="ok">✅ SUCCÈS</span>' : '<span class="err">❌ ÉCHEC</span>') . "\n";

        if ($moved && file_exists($dest)) {
            echo 'Fichier créé : <span class="ok">✅ OUI</span>' . "\n";
            echo 'URL publique : ' . UPLOAD_URL . '/galerie/' . $filename . "\n\n";

            // Test BDD
            try {
                $stmt = $pdo->prepare("INSERT INTO galerie (categorie_id, type, titre, fichier, alt_text, en_vedette, actif, created_at) VALUES (?, 'photo', 'Test Upload', ?, 'test', 0, 1, NOW())");
                $stmt->execute([$catId, 'galerie/' . $filename]);
                $newId = $pdo->lastInsertId();
                echo 'INSERT BDD   : <span class="ok">✅ ID=' . $newId . '</span>' . "\n";
                echo '</pre>';
                echo '<p class="ok" style="font-size:1.1rem;margin-top:16px">✅ <strong>TOUT FONCTIONNE !</strong> Le problème vient de galerie-admin.php, pas du serveur.</p>';
                echo '<p>Photo de test enregistrée → <a href="' . UPLOAD_URL . '/galerie/' . $filename . '" target="_blank" style="color:#D4AF37">Voir la photo</a></p>';

                // Nettoyer
                $pdo->prepare("DELETE FROM galerie WHERE id = ?")->execute([$newId]);
                unlink($dest);
                echo '<p style="color:#555;font-size:.8rem">(Photo et entrée BDD supprimées — c\'était juste un test)</p>';
            } catch(Exception $e) {
                echo 'INSERT BDD   : <span class="err">❌ ' . $e->getMessage() . '</span>' . "\n";
                echo '</pre>';
            }
        } else {
            echo '</pre>';
            echo '<p class="err">❌ <strong>ÉCHEC de move_uploaded_file</strong></p>';
            echo '<p class="warn">→ Vérifiez les permissions du dossier : ' . $uploadDir . '</p>';
        }
    }
}

// ── Formulaire de test ─────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
echo '<h2>3. Test d\'upload</h2>
<form method="POST" enctype="multipart/form-data">
  <label style="color:#888;font-size:.85rem">Sélectionne une photo quelconque (JPG, PNG) :</label>
  <input type="file" name="photo" accept="image/*" required>
  <button type="submit">🚀 Lancer le test d\'upload</button>
</form>';
}

echo '<hr style="border-color:#333;margin:30px 0">';
echo '<p class="warn">⚠️ Supprime ce fichier après utilisation : <strong>test_upload.php</strong></p>';
