<?php
/**
 * MIGRATION UNIQUE — à supprimer après usage.
 * Ajoute max_invites à types_evenements, sans rien supprimer.
 */
require_once __DIR__ . '/../includes/config.php';
requireAdmin();

if (($_GET['confirm'] ?? '') !== 'MAXINVITES2026') {
    die('Ajoute ?confirm=MAXINVITES2026 à la fin de l\'URL pour lancer la migration.');
}

header('Content-Type: text/html; charset=utf-8');
echo "<!DOCTYPE html><html><head><meta charset='utf-8'><style>body{font-family:monospace;background:#111;color:#eee;padding:20px}.ok{color:#4ade80}.err{color:#f87171}</style></head><body>";
echo "<h1 style='color:#D4AF37'>Migration — max_invites</h1>";

try {
    $colonnes = $pdo->query("SHOW COLUMNS FROM types_evenements LIKE 'max_invites'")->fetchAll();
    if (empty($colonnes)) {
        $pdo->exec("ALTER TABLE types_evenements ADD COLUMN max_invites INT UNSIGNED NULL COMMENT 'Nombre maximal d invites autorise, NULL = pas de limite'");
        echo "<p class='ok'>✅ Colonne max_invites ajoutée à types_evenements.</p>";
    } else {
        echo "<p class='ok'>✅ La colonne max_invites existe déjà, rien à faire.</p>";
    }
} catch (Exception $e) {
    echo "<p class='err'>❌ " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "<hr><p class='err'><strong>⚠️ Supprime ce fichier (admin/migrate_max_invites.php) maintenant.</strong></p></body></html>";
