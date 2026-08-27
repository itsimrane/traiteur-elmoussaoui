<?php
/**
 * RÉPARATION UNIQUE — à supprimer juste après utilisation.
 * Supprime les tables factures/paiements mal structurées pour que
 * factures.php puisse les recréer automatiquement avec la bonne structure.
 */
require_once __DIR__ . '/../includes/config.php';
requireAdmin();

if (($_GET['confirm'] ?? '') !== 'FIX2026') {
    die('Ajoute ?confirm=FIX2026 à la fin de l\'URL pour lancer la réparation.');
}

header('Content-Type: text/html; charset=utf-8');
echo "<pre style='background:#111;color:#0f0;padding:20px;font-family:monospace'>";

try {
    $pdo->exec('SET FOREIGN_KEY_CHECKS=0');
    $pdo->exec('DROP TABLE IF EXISTS `paiements`');
    echo "✅ Table paiements supprimée.\n";
    $pdo->exec('DROP TABLE IF EXISTS `factures`');
    echo "✅ Table factures supprimée.\n";
    $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
    echo "\n🎉 TERMINÉ. Va maintenant sur admin/factures.php : la table sera recréée automatiquement avec la bonne structure.\n";
    echo "\n⚠️ N'oublie pas de SUPPRIMER ce fichier (admin/fix_factures_table.php) juste après.\n";
} catch (Exception $e) {
    echo "❌ Erreur : " . htmlspecialchars($e->getMessage()) . "\n";
}
echo "</pre>";
