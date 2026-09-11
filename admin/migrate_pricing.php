<?php
/**
 * MIGRATION UNIQUE — à supprimer après usage.
 * Ajoute la tarification dynamique aux services, sans rien supprimer.
 */
require_once __DIR__ . '/../includes/config.php';
requireAdmin();

if (($_GET['confirm'] ?? '') !== 'PRICING2026') {
    die('Ajoute ?confirm=PRICING2026 à la fin de l\'URL pour lancer la migration.');
}

header('Content-Type: text/html; charset=utf-8');
echo "<!DOCTYPE html><html><head><meta charset='utf-8'><style>
body{font-family:monospace;background:#111;color:#eee;padding:20px;line-height:1.7}
.ok{color:#4ade80} .err{color:#f87171} .info{color:#fbbf24}
</style></head><body>";
echo "<h1 style='color:#D4AF37'>💰 Migration — Tarification dynamique</h1>";

try {
    // 1. Nouvelles colonnes sur services (ADD COLUMN IF NOT EXISTS)
    $pdo->exec("ALTER TABLE services
        ADD COLUMN IF NOT EXISTS price_type ENUM('fixe','par_personne','par_unite','par_tranche','sur_devis') NOT NULL DEFAULT 'fixe' COMMENT 'Mode de calcul du prix',
        ADD COLUMN IF NOT EXISTS personnes_par_unite INT UNSIGNED NULL COMMENT 'Ex: 10 = 1 unité (table, etc.) pour 10 invités'
    ");
    echo "<p class='ok'>✅ Colonnes price_type et personnes_par_unite ajoutées à `services`.</p>";

    // 2. Table des tranches
    $pdo->exec("CREATE TABLE IF NOT EXISTS `service_tranches` (
        `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
        `service_id` INT UNSIGNED NOT NULL,
        `min_invites` INT UNSIGNED NOT NULL,
        `max_invites` INT UNSIGNED NULL COMMENT 'NULL = illimité',
        `prix_par_personne` DECIMAL(10,2) NOT NULL,
        `ordre` INT UNSIGNED NOT NULL DEFAULT 0,
        PRIMARY KEY (`id`),
        KEY `idx_service` (`service_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Paliers de prix par nombre d\'invités'");
    echo "<p class='ok'>✅ Table service_tranches créée.</p>";

    // 3. Déduire price_type depuis la colonne "unite" existante (best-effort, une fois)
    $services = $pdo->query("SELECT id, unite, prix, prix_base FROM services")->fetchAll();
    $maj = 0;
    foreach ($services as $s) {
        $unite = mb_strtolower(trim($s['unite'] ?? ''));
        $type = 'fixe';
        if (str_contains($unite, 'personne')) $type = 'par_personne';
        elseif (str_contains($unite, 'unité') || str_contains($unite, 'unite')) $type = 'par_unite';

        // Si aucun prix du tout -> sur devis
        if (empty($s['prix']) && empty($s['prix_base'])) $type = 'sur_devis';

        // prix_base manquant mais prix présent -> on copie (sans écraser une vraie valeur existante)
        if (empty($s['prix_base']) && !empty($s['prix'])) {
            $pdo->prepare("UPDATE services SET prix_base = ? WHERE id = ? AND prix_base IS NULL")
                ->execute([$s['prix'], $s['id']]);
        }

        $pdo->prepare("UPDATE services SET price_type = ? WHERE id = ?")->execute([$type, $s['id']]);
        $maj++;
        echo "<p class='info'>ℹ️ Service #{$s['id']} → price_type = $type (déduit de \"{$s['unite']}\")</p>";
    }
    echo "<p class='ok'>✅ $maj service(s) mis à jour.</p>";

} catch (Exception $e) {
    echo "<p class='err'>❌ Erreur : " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "<hr><p class='info'>ℹ️ Rien n'a été supprimé. Tu peux maintenant ajuster précisément chaque service (type de tarif, tranches) depuis admin/services-admin.php.</p>";
echo "<p class='err'><strong>⚠️ Supprime ce fichier (admin/migrate_pricing.php) maintenant que la migration est faite.</strong></p>";
echo "</body></html>";
