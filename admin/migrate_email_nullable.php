<?php
/**
 * MIGRATION UNIQUE — à supprimer après usage.
 * Rend clients.email réellement optionnelle (NULL autorisé),
 * sans toucher aux données existantes.
 */
require_once __DIR__ . '/../includes/config.php';
requireAdmin();

if (($_GET['confirm'] ?? '') !== 'EMAILNULL2026') {
    die('Ajoute ?confirm=EMAILNULL2026 à la fin de l\'URL pour lancer la migration.');
}

header('Content-Type: text/html; charset=utf-8');
echo "<!DOCTYPE html><html><head><meta charset='utf-8'><style>body{font-family:monospace;background:#111;color:#eee;padding:20px}.ok{color:#4ade80}.err{color:#f87171}</style></head><body>";
echo "<h1 style='color:#D4AF37'>Migration — email nullable</h1>";

try {
    // 1. On convertit d'abord les éventuels emails vides existants en NULL
    //    (sinon la contrainte NOT NULL empêcherait même de les modifier).
    $pdo->exec("UPDATE clients SET email = NULL WHERE email = ''");
    echo "<p class='ok'>✅ Emails vides existants convertis en NULL.</p>";

    // 2. On rend la colonne réellement nullable.
    $pdo->exec("ALTER TABLE clients MODIFY email VARCHAR(191) NULL");
    echo "<p class='ok'>✅ Colonne email rendue optionnelle (NULL autorisé).</p>";

} catch (Exception $e) {
    echo "<p class='err'>❌ " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "<hr><p class='err'><strong>⚠️ Supprime ce fichier (admin/migrate_email_nullable.php) maintenant.</strong></p></body></html>";
