<?php
/**
 * DIAGNOSTIC — Traiteur EL MOUSSAOUI
 * Placer dans C:\xampp\htdocs\Traiteur_Elmoussaoui\diagnostic.php
 * Ouvrir dans le navigateur : http://localhost/Traiteur_Elmoussaoui/diagnostic.php
 * SUPPRIMER après utilisation
 */

// Activer les erreurs
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo '<style>body{font-family:monospace;background:#0D0D0D;color:#CCC;padding:30px}
h2{color:#D4AF37;margin:20px 0 10px}
.ok{color:#66BB6A} .err{color:#EF5350} .warn{color:#FFA726}
pre{background:#111;padding:14px;border-radius:8px;border:1px solid #333;overflow:auto}
table{border-collapse:collapse;width:100%}
td,th{padding:8px 12px;border:1px solid #333;text-align:left}
th{background:#1A1A2E;color:#D4AF37}
</style>';

echo '<h1 style="color:#D4AF37">🔍 Diagnostic — Traiteur EL MOUSSAOUI</h1>';

// ── 1. Config.php ──────────────────────────────────────────
echo '<h2>1. Fichier config.php</h2>';
$configPath = __DIR__ . '/includes/config.php';
if (file_exists($configPath)) {
    echo '<p class="ok">✅ includes/config.php trouvé</p>';
    require_once $configPath;
    echo '<p class="ok">✅ config.php chargé sans erreur</p>';
    echo '<pre>';
    echo 'SITE_URL    : ' . (defined('SITE_URL')    ? SITE_URL    : '❌ NON DÉFINI') . "\n";
    echo 'UPLOAD_PATH : ' . (defined('UPLOAD_PATH') ? UPLOAD_PATH : '❌ NON DÉFINI') . "\n";
    echo 'UPLOAD_URL  : ' . (defined('UPLOAD_URL')  ? UPLOAD_URL  : '❌ NON DÉFINI') . "\n";
    echo 'MAX_FILE_SIZE: ' . (defined('MAX_FILE_SIZE') ? MAX_FILE_SIZE . ' octets (' . round(MAX_FILE_SIZE/1024/1024, 1) . ' Mo)' : '❌ NON DÉFINI') . "\n";
    echo '</pre>';
} else {
    echo '<p class="err">❌ includes/config.php INTROUVABLE</p>';
    echo '<p class="warn">Chemin cherché : ' . $configPath . '</p>';
    die();
}

// ── 2. Connexion BDD ───────────────────────────────────────
echo '<h2>2. Connexion Base de Données</h2>';
if (isset($pdo)) {
    echo '<p class="ok">✅ Connexion PDO établie</p>';
    try {
        $version = $pdo->query('SELECT VERSION()')->fetchColumn();
        echo '<p class="ok">✅ MySQL version : ' . $version . '</p>';
    } catch (Exception $e) {
        echo '<p class="err">❌ Erreur requête : ' . $e->getMessage() . '</p>';
    }
} else {
    echo '<p class="err">❌ Variable $pdo non définie — connexion échouée</p>';
}

// ── 3. Tables BDD ──────────────────────────────────────────
echo '<h2>3. Tables dans la base de données</h2>';
try {
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    $required = ['galerie', 'categories_galerie', 'packages', 'reservations', 'clients'];
    echo '<table><tr><th>Table</th><th>Statut</th><th>Nb lignes</th></tr>';
    foreach ($required as $t) {
        $exists = in_array($t, $tables);
        $count  = $exists ? $pdo->query("SELECT COUNT(*) FROM `$t`")->fetchColumn() : '—';
        $status = $exists ? '<span class="ok">✅ Existe</span>' : '<span class="err">❌ MANQUANTE</span>';
        echo "<tr><td>$t</td><td>$status</td><td>$count</td></tr>";
    }
    echo '</table>';

    // Colonnes de la table galerie
    echo '<h2>4. Colonnes table `galerie`</h2>';
    if (in_array('galerie', $tables)) {
        $cols = $pdo->query("DESCRIBE galerie")->fetchAll();
        echo '<table><tr><th>Colonne</th><th>Type</th><th>Null</th><th>Défaut</th></tr>';
        foreach ($cols as $col) {
            echo "<tr><td>{$col['Field']}</td><td>{$col['Type']}</td><td>{$col['Null']}</td><td>{$col['Default']}</td></tr>";
        }
        echo '</table>';
    }

    // Colonnes de la table packages
    echo '<h2>5. Colonnes table `packages`</h2>';
    if (in_array('packages', $tables)) {
        $cols = $pdo->query("DESCRIBE packages")->fetchAll();
        $hasCols = array_column($cols, 'Field');
        $needed  = ['contenu', 'duree_heures', 'min_personnes', 'max_personnes', 'mis_en_avant'];
        echo '<table><tr><th>Colonne</th><th>Type</th><th>Statut</th></tr>';
        foreach ($cols as $col) {
            $mark = in_array($col['Field'], $needed) ? ' <span class="ok">← requise</span>' : '';
            echo "<tr><td>{$col['Field']}{$mark}</td><td>{$col['Type']}</td><td><span class='ok'>✅</span></td></tr>";
        }
        // Colonnes manquantes
        foreach ($needed as $n) {
            if (!in_array($n, $hasCols)) {
                echo "<tr><td>$n <span class='warn'>← MANQUANTE</span></td><td>—</td><td><span class='err'>❌ À créer</span></td></tr>";
            }
        }
        echo '</table>';
    }

} catch (Exception $e) {
    echo '<p class="err">❌ Erreur : ' . $e->getMessage() . '</p>';
}

// ── 5. Fichiers admin ──────────────────────────────────────
echo '<h2>6. Fichiers admin & API</h2>';
$files = [
    'admin/galerie-admin.php',
    'admin/packages-admin.php',
    'api/upload_media.php',
    'api/delete_media.php',
    'api/toggle_vedette.php',
    'api/update_package.php',
    'pages/galerie.php',
    'js/lang.js',
];
echo '<table><tr><th>Fichier</th><th>Statut</th><th>Taille</th><th>Dernière modif</th></tr>';
foreach ($files as $f) {
    $path   = __DIR__ . '/' . $f;
    $exists = file_exists($path);
    $size   = $exists ? round(filesize($path)/1024, 1) . ' Ko' : '—';
    $mtime  = $exists ? date('d/m/Y H:i', filemtime($path)) : '—';
    $status = $exists ? '<span class="ok">✅ Présent</span>' : '<span class="err">❌ MANQUANT</span>';
    echo "<tr><td>$f</td><td>$status</td><td>$size</td><td>$mtime</td></tr>";
}
echo '</table>';

// ── 6. Dossier uploads ─────────────────────────────────────
echo '<h2>7. Dossiers uploads</h2>';
$uploadDirs = [
    'assets/uploads',
    'assets/uploads/galerie',
    'assets/uploads/galerie/videos',
    'assets/uploads/galerie/thumbnails',
];
foreach ($uploadDirs as $d) {
    $path     = __DIR__ . '/' . $d;
    $exists   = is_dir($path);
    $writable = $exists && is_writable($path);
    if (!$exists) {
        mkdir($path, 0755, true);
        $exists   = is_dir($path);
        $writable = is_writable($path);
    }
    $status = $exists ? ($writable ? '<span class="ok">✅ Accessible en écriture</span>' : '<span class="warn">⚠️ Lecture seule</span>') : '<span class="err">❌ MANQUANT</span>';
    echo "<p>$d : $status</p>";
}

// ── 7. PHP config ──────────────────────────────────────────
echo '<h2>8. Configuration PHP</h2>';
echo '<pre>';
echo 'PHP version          : ' . PHP_VERSION . "\n";
echo 'upload_max_filesize  : ' . ini_get('upload_max_filesize') . "\n";
echo 'post_max_size        : ' . ini_get('post_max_size') . "\n";
echo 'max_execution_time   : ' . ini_get('max_execution_time') . "s\n";
echo 'memory_limit         : ' . ini_get('memory_limit') . "\n";
echo 'Extensions PDO       : ' . (extension_loaded('pdo') ? '✅ OK' : '❌ MANQUANTE') . "\n";
echo 'Extension PDO_MySQL  : ' . (extension_loaded('pdo_mysql') ? '✅ OK' : '❌ MANQUANTE') . "\n";
echo 'Extension fileinfo   : ' . (extension_loaded('fileinfo') ? '✅ OK' : '❌ MANQUANTE') . "\n";
echo '</pre>';

echo '<hr style="border-color:#333;margin:30px 0">';
echo '<p class="warn">⚠️ Supprime ce fichier après utilisation : <strong>diagnostic.php</strong></p>';
echo '<p style="color:#555;font-size:.85rem">Traiteur EL MOUSSAOUI — Diagnostic généré le ' . date('d/m/Y H:i:s') . '</p>';
