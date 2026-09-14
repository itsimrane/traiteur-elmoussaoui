<?php
require_once __DIR__ . '/../includes/config.php';
requireAdmin();

header('Content-Type: text/html; charset=utf-8');
echo "<pre style='background:#111;color:#0f0;padding:20px;font-family:monospace'>";

echo "=== TRIGGERS sur la base ===\n\n";
try {
    $triggers = $pdo->query("SHOW TRIGGERS")->fetchAll();
    if (empty($triggers)) {
        echo "Aucun trigger trouvé sur toute la base.\n";
    } else {
        foreach ($triggers as $t) {
            echo "Trigger: {$t['Trigger']} | Table: {$t['Table']} | Event: {$t['Event']} | Timing: {$t['Timing']}\n";
        }
    }
} catch (Exception $e) {
    echo "Erreur: " . $e->getMessage() . "\n";
}

echo "\n=== Structure réelle de la table paiements ===\n\n";
try {
    $cols = $pdo->query("SHOW COLUMNS FROM paiements")->fetchAll();
    foreach ($cols as $c) {
        echo "{$c['Field']} | {$c['Type']} | Null: {$c['Null']} | Default: " . var_export($c['Default'], true) . "\n";
    }
} catch (Exception $e) {
    echo "Erreur: " . $e->getMessage() . "\n";
}

echo "\n=== Structure réelle de la table factures (colonnes NOT NULL sans défaut) ===\n\n";
try {
    $cols = $pdo->query("SHOW COLUMNS FROM factures")->fetchAll();
    foreach ($cols as $c) {
        if ($c['Null'] === 'NO' && $c['Default'] === null && strpos($c['Extra'], 'auto_increment') === false) {
            echo "⚠️  {$c['Field']} | {$c['Type']} | OBLIGATOIRE sans défaut\n";
        }
    }
} catch (Exception $e) {
    echo "Erreur: " . $e->getMessage() . "\n";
}

echo "</pre>";
