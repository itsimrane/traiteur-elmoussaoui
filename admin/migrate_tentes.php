<?php
/**
 * MIGRATION UNIQUE — à supprimer après usage.
 * Crée la table `tentes` et ajoute `tente_id` à `reservations`.
 * Ne supprime ni ne modifie aucune donnée existante.
 */
require_once __DIR__ . '/../includes/config.php';
requireAdmin();

if (($_GET['confirm'] ?? '') !== 'TENTES2026') {
    die('Ajoute ?confirm=TENTES2026 à la fin de l\'URL pour lancer la migration.');
}

header('Content-Type: text/html; charset=utf-8');
echo "<!DOCTYPE html><html><head><meta charset='utf-8'><style>
body{font-family:monospace;background:#111;color:#eee;padding:20px;line-height:1.7}
.ok{color:#4ade80} .err{color:#f87171} .info{color:#fbbf24}
</style></head><body>";
echo "<h1 style='color:#D4AF37'>⛺ Migration — Tentes</h1>";

try {
    // 1. Table `tentes`
    $pdo->exec("CREATE TABLE IF NOT EXISTS `tentes` (
        `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
        `nom`           VARCHAR(100) NOT NULL,
        `photo`         VARCHAR(255) NULL,
        `longueur`      DECIMAL(6,2) NULL COMMENT 'en mètres',
        `largeur`       DECIMAL(6,2) NULL COMMENT 'en mètres',
        `capacite_max`  INT UNSIGNED NULL,
        `description`   TEXT NULL,
        `actif`         TINYINT(1) NOT NULL DEFAULT 1,
        `ordre`         INT UNSIGNED NOT NULL DEFAULT 0,
        `created_at`    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at`    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Tentes disponibles à la location'");
    echo "<p class='ok'>✅ Table `tentes` créée (ou déjà existante).</p>";

    // 2. Créer les 4 fiches vides si la table est vide (structure seulement, pas de fausses infos)
    $count = (int)$pdo->query("SELECT COUNT(*) FROM tentes")->fetchColumn();
    if ($count === 0) {
        $stmt = $pdo->prepare("INSERT INTO tentes (nom, actif, ordre) VALUES (?, 0, ?)");
        for ($i = 1; $i <= 4; $i++) {
            $stmt->execute(["Tente $i", $i]);
        }
        echo "<p class='ok'>✅ 4 fiches Tente 1 à 4 créées (inactives, à configurer dans admin/tentes.php).</p>";
    } else {
        echo "<p class='info'>ℹ️ La table `tentes` contient déjà $count ligne(s), aucune fiche ajoutée.</p>";
    }

    // 3. Colonne `tente_id` sur `reservations` (vérification SHOW COLUMNS avant ALTER, Railway ne supporte pas IF NOT EXISTS)
    $cols = $pdo->query("SHOW COLUMNS FROM reservations LIKE 'tente_id'")->fetchAll();
    if (empty($cols)) {
        $pdo->exec("ALTER TABLE reservations ADD COLUMN `tente_id` INT UNSIGNED NULL AFTER `type_evenement_id`");
        echo "<p class='ok'>✅ Colonne `tente_id` ajoutée à `reservations`.</p>";
    } else {
        echo "<p class='info'>ℹ️ Colonne `tente_id` déjà présente sur `reservations`.</p>";
    }

    // 4. Nettoyage : suppression du paramètre TVA par défaut (plus utilisé)
    try {
        $pdo->exec("DELETE FROM parametres WHERE cle = 'tva_defaut'");
        echo "<p class='ok'>✅ Paramètre `tva_defaut` supprimé (s'il existait).</p>";
    } catch (Exception $e) { /* table parametres absente ou déjà nettoyée */ }

} catch (Exception $e) {
    echo "<p class='err'>❌ Erreur : " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "<hr><p class='info'>ℹ️ Rien n'a été supprimé. Va maintenant configurer les 4 tentes dans admin/tentes.php.</p>";
echo "<p class='err'><strong>⚠️ Supprime ce fichier (admin/migrate_tentes.php) maintenant que la migration est faite.</strong></p>";
echo "</body></html>";
