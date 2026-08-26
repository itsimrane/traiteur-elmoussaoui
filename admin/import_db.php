<?php
/**
 * ══════════════════════════════════════════════════════════════
 * SCRIPT D'IMPORT — Traiteur EL MOUSSAOUI
 * À utiliser UNE SEULE FOIS pour importer la base sur Railway.
 * ⚠️ À SUPPRIMER du projet juste après usage (sécurité).
 * ══════════════════════════════════════════════════════════════
 *
 * INSTALLATION :
 * 1. Place ce fichier dans le dossier  admin/import_db.php
 * 2. Place ton fichier .sql dans       sql/db_traiteur_elmoussaoui.sql
 *    (crée le dossier "sql" à la racine du projet s'il n'existe pas)
 * 3. git add / commit / push vers Railway
 * 4. Ouvre https://TON-SITE.up.railway.app/admin/import_db.php?confirm=IMPORT2026
 * 5. Une fois "TERMINÉ" affiché → supprime ce fichier et repush.
 */

// ── Sécurité simple : évite qu'un import se lance par accident ──
if (($_GET['confirm'] ?? '') !== 'IMPORT2026') {
    die('Pour lancer l\'import, ajoute ?confirm=IMPORT2026 à la fin de l\'URL.');
}

// ── Chemin du fichier SQL à importer ──
$sqlFile = __DIR__ . '/../sql/fix_factures_paiements.sql';

header('Content-Type: text/html; charset=utf-8');
echo "<!DOCTYPE html><html><head><meta charset='utf-8'>
<style>
body{font-family:monospace;background:#111;color:#eee;padding:20px;line-height:1.6}
.ok{color:#4ade80} .err{color:#f87171} .info{color:#fbbf24}
h1{color:#D4AF37}
</style></head><body>";
echo "<h1>📦 Import base de données — Traiteur EL MOUSSAOUI</h1>";

if (!file_exists($sqlFile)) {
    echo "<p class='err'>❌ Fichier introuvable : $sqlFile</p>";
    echo "<p class='info'>Vérifie que sql/db_traiteur_elmoussaoui.sql existe bien à la racine du projet.</p>";
    exit;
}

// ── Connexion à MySQL Railway via les variables d'environnement ──
$host = getenv('MYSQLHOST') ?: '';
$db   = getenv('MYSQLDATABASE') ?: '';
$user = getenv('MYSQLUSER') ?: '';
$pass = getenv('MYSQLPASSWORD') ?: '';
$port = getenv('MYSQLPORT') ?: '3306';

if (!$host || !$db || !$user) {
    echo "<p class='err'>❌ Variables d'environnement MySQL introuvables (MYSQLHOST/MYSQLDATABASE/MYSQLUSER).</p>";
    echo "<p class='info'>Ce script doit être exécuté SUR Railway (pas en local), pour avoir accès à ces variables.</p>";
    exit;
}

echo "<p class='info'>🔌 Connexion à $host / $db ...</p>";

try {
    $pdo = new PDO(
        "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4",
        $user,
        $pass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    echo "<p class='ok'>✅ Connexion réussie.</p>";
} catch (PDOException $e) {
    echo "<p class='err'>❌ Connexion impossible : " . htmlspecialchars($e->getMessage()) . "</p>";
    exit;
}

// ── Lecture et découpage du fichier SQL en requêtes individuelles ──
// (découpage qui respecte les guillemets, pour ne pas couper au milieu
//  d'un texte contenant un point-virgule)
function splitSqlStatements(string $sql): array
{
    $statements = [];
    $current = '';
    $len = strlen($sql);
    $inString = false;
    $stringChar = '';

    for ($i = 0; $i < $len; $i++) {
        $char = $sql[$i];
        $current .= $char;

        if ($inString) {
            if ($char === '\\') {
                // caractère suivant échappé, on l'ajoute tel quel
                if ($i + 1 < $len) {
                    $current .= $sql[++$i];
                }
                continue;
            }
            if ($char === $stringChar) {
                $inString = false;
            }
        } else {
            if ($char === "'" || $char === '"' || $char === '`') {
                $inString = true;
                $stringChar = $char;
            } elseif ($char === ';') {
                $statements[] = trim($current);
                $current = '';
            }
        }
    }
    if (trim($current) !== '') {
        $statements[] = trim($current);
    }
    return $statements;
}

$sqlContent = file_get_contents($sqlFile);

// Supprime les commentaires de ligne complète (-- ...) pour ne pas les
// exécuter comme requêtes vides / perturber le découpage
$sqlContent = preg_replace('/^--.*$/m', '', $sqlContent);
$sqlContent = preg_replace('/^\/\*.*?\*\/;?$/ms', '', $sqlContent);

$statements = splitSqlStatements($sqlContent);

echo "<p class='info'>📄 " . count($statements) . " requêtes détectées. Import en cours...</p><hr>";

$pdo->exec('SET FOREIGN_KEY_CHECKS=0');
$pdo->exec('SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO"');

$ok = 0;
$errors = [];

foreach ($statements as $stmt) {
    $trimmed = trim($stmt);
    if ($trimmed === '' || stripos($trimmed, 'START TRANSACTION') === 0 || stripos($trimmed, 'COMMIT') === 0) {
        continue;
    }
    try {
        $pdo->exec($trimmed);
        $ok++;
    } catch (PDOException $e) {
        $errors[] = [
            'message' => $e->getMessage(),
            'query'   => substr($trimmed, 0, 120),
        ];
    }
}

$pdo->exec('SET FOREIGN_KEY_CHECKS=1');

echo "<p class='ok'>✅ $ok requêtes exécutées avec succès.</p>";

if (count($errors) > 0) {
    echo "<p class='err'>⚠️ " . count($errors) . " erreur(s) rencontrée(s) :</p><ul>";
    foreach ($errors as $err) {
        echo "<li class='err'>" . htmlspecialchars($err['message']) . "<br>";
        echo "<small>→ " . htmlspecialchars($err['query']) . "...</small></li><br>";
    }
    echo "</ul>";
} else {
    echo "<p class='ok'>🎉 Aucune erreur !</p>";
}

// ── Vérification finale : liste des tables créées ──
echo "<hr><h3>📋 Tables présentes dans la base :</h3><ul>";
$tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
foreach ($tables as $t) {
    $count = $pdo->query("SELECT COUNT(*) FROM `$t`")->fetchColumn();
    echo "<li>$t <span class='info'>($count lignes)</span></li>";
}
echo "</ul>";

echo "<hr><p class='ok'><strong>✅ TERMINÉ.</strong></p>";
echo "<p class='err'>⚠️ N'oublie pas de SUPPRIMER ce fichier (admin/import_db.php) et de repush sur Railway maintenant que l'import est fait.</p>";
echo "</body></html>";
