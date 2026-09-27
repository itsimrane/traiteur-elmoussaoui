<?php
/**
 * MIGRATION UNIQUE — à supprimer après usage.
 * Ajoute le support des réservations multi-jours :
 * - crée la table `reservation_dates` (une ligne par date choisie)
 * - reporte la date unique de chaque réservation existante dans cette
 *   table (aucune donnée perdue, les anciennes réservations deviennent
 *   automatiquement des réservations "1 jour")
 * `reservations.date_evenement` est CONSERVÉE telle quelle : elle sert
 * désormais de "date principale" (la plus proche), utilisée pour le tri
 * et l'affichage rapide partout où le code existant l'attend déjà.
 * `reservation_dates` est la source de vérité pour la liste complète.
 */
require_once __DIR__ . '/../includes/config.php';
requireAdmin();

if (($_GET['confirm'] ?? '') !== 'MULTIDATES2026') {
    die('Ajoute ?confirm=MULTIDATES2026 à la fin de l\'URL pour lancer la migration.');
}

header('Content-Type: text/html; charset=utf-8');
echo "<!DOCTYPE html><html><head><meta charset='utf-8'><style>
body{font-family:monospace;background:#111;color:#eee;padding:20px;line-height:1.7}
.ok{color:#4ade80} .err{color:#f87171} .info{color:#fbbf24}
</style></head><body>";
echo "<h1 style='color:#D4AF37'>📅 Migration — Réservations multi-jours</h1>";

try {
    // 1. Table `reservation_dates`
    $pdo->exec("CREATE TABLE IF NOT EXISTS `reservation_dates` (
        `id`              INT UNSIGNED NOT NULL AUTO_INCREMENT,
        `reservation_id`  INT UNSIGNED NOT NULL,
        `date_evenement`  DATE NOT NULL,
        `created_at`      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `uniq_resa_date` (`reservation_id`, `date_evenement`),
        KEY `idx_reservation` (`reservation_id`),
        KEY `idx_date` (`date_evenement`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Dates (une ou plusieurs) associées à une réservation'");
    echo "<p class='ok'>✅ Table `reservation_dates` créée (ou déjà existante).</p>";

    // 2. Reporter la date de chaque réservation existante qui n'y est pas encore
    $stmt = $pdo->query("
        SELECT r.id, r.date_evenement
        FROM reservations r
        LEFT JOIN reservation_dates rd ON rd.reservation_id = r.id
        WHERE r.date_evenement IS NOT NULL AND rd.id IS NULL
        GROUP BY r.id
    ");
    $aReporter = $stmt->fetchAll();

    $ins = $pdo->prepare("INSERT IGNORE INTO reservation_dates (reservation_id, date_evenement) VALUES (?, ?)");
    $count = 0;
    foreach ($aReporter as $row) {
        $ins->execute([$row['id'], $row['date_evenement']]);
        $count++;
    }
    echo "<p class='ok'>✅ $count réservation(s) existante(s) reportée(s) dans `reservation_dates` (1 jour chacune).</p>";

} catch (Exception $e) {
    echo "<p class='err'>❌ Erreur : " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "<hr><p class='info'>ℹ️ Rien n'a été supprimé. `reservations.date_evenement` reste utilisée comme date principale.</p>";
echo "<p class='err'><strong>⚠️ Supprime ce fichier (admin/migrate_reservation_dates.php) maintenant que la migration est faite.</strong></p>";
echo "</body></html>";
