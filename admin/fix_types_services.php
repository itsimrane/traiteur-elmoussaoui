<?php
/**
 * MIGRATION UNIQUE — à supprimer après usage.
 * Remplace les anciens identifiants de types d'événements
 * (buffet, reception_pro, religieux) par les nouveaux qui
 * correspondent réellement à la table types_evenements
 * (buffet-banquet, reception-pro, ceremonie-reli) dans la
 * colonne JSON types_evenements de chaque service.
 */
require_once __DIR__ . '/../includes/config.php';
requireAdmin();

if (($_GET['confirm'] ?? '') !== 'FIXTYPES2026') {
    die('Ajoute ?confirm=FIXTYPES2026 à la fin de l\'URL pour lancer la migration.');
}

header('Content-Type: text/html; charset=utf-8');
echo "<!DOCTYPE html><html><head><meta charset='utf-8'><style>body{font-family:monospace;background:#111;color:#eee;padding:20px}.ok{color:#4ade80}.err{color:#f87171}</style></head><body>";
echo "<h1 style='color:#D4AF37'>Migration — identifiants types d'événements des services</h1>";

$remplacements = [
    'buffet'        => 'buffet-banquet',
    'reception_pro' => 'reception-pro',
    'religieux'     => 'ceremonie-reli',
];

try {
    $services = $pdo->query("SELECT id, nom, types_evenements FROM services")->fetchAll();
    $corrigés = 0;

    foreach ($services as $s) {
        $types = json_decode($s['types_evenements'] ?? '[]', true) ?: [];
        if (!is_array($types) || empty($types)) continue;

        $nouveauxTypes = array_map(fn($t) => $remplacements[$t] ?? $t, $types);

        if ($nouveauxTypes !== $types) {
            $pdo->prepare("UPDATE services SET types_evenements = ? WHERE id = ?")
                ->execute([json_encode($nouveauxTypes), $s['id']]);
            echo "<p class='ok'>✅ {$s['nom']} : " . implode(',', $types) . " → " . implode(',', $nouveauxTypes) . "</p>";
            $corrigés++;
        }
    }

    echo "<hr><p class='ok'><strong>{$corrigés} service(s) corrigé(s) sur " . count($services) . " au total.</strong></p>";

} catch (Exception $e) {
    echo "<p class='err'>❌ " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "<hr><p class='err'><strong>⚠️ Supprime ce fichier (admin/fix_types_services.php) maintenant.</strong></p></body></html>";
