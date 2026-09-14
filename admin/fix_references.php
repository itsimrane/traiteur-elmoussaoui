<?php
require_once __DIR__ . '/../includes/config.php';
requireAdmin();

if (($_GET['confirm'] ?? '') !== 'FIXREFS2026') {
    die('Ajoute ?confirm=FIXREFS2026 à la fin de l\'URL pour lancer la correction.');
}

header('Content-Type: text/html; charset=utf-8');
echo "<pre style='background:#111;color:#0f0;padding:20px;font-family:monospace'>";
echo "<span style='color:#D4AF37'>=== Correction des références incohérentes ===</span>\n\n";

function corrigerTable(PDO $pdo, string $table, string $prefixe): void {
    echo "--- Table $table ---\n";
    $rows = $pdo->query("SELECT id, reference FROM $table")->fetchAll();
    $corriges = 0;

    foreach ($rows as $r) {
        $attendu = $prefixe . '-' . date('Y', strtotime('now')) . '-' . str_pad($r['id'], 4, '0', STR_PAD_LEFT);
        // On ne recalcule que le numéro, pas l'année (on garde l'année déjà présente dans la référence existante si possible)
        if (preg_match('/^' . preg_quote($prefixe, '/') . '-(\d{4})-\d+$/', $r['reference'], $m)) {
            $anneeExistante = $m[1];
            $attendu = $prefixe . '-' . $anneeExistante . '-' . str_pad($r['id'], 4, '0', STR_PAD_LEFT);
        }

        if ($r['reference'] !== $attendu) {
            // On vérifie qu'on ne crée pas nous-mêmes un nouveau doublon avant de corriger
            $existeDeja = $pdo->prepare("SELECT id FROM $table WHERE reference = ? AND id != ?");
            $existeDeja->execute([$attendu, $r['id']]);
            if ($existeDeja->fetch()) {
                echo "⚠️  id={$r['id']} : {$r['reference']} → $attendu IGNORÉ (collision potentielle, à vérifier manuellement)\n";
                continue;
            }
            $pdo->prepare("UPDATE $table SET reference = ? WHERE id = ?")->execute([$attendu, $r['id']]);
            echo "✅ id={$r['id']} : {$r['reference']} → $attendu\n";
            $corriges++;
        }
    }
    echo "$corriges ligne(s) corrigée(s) sur " . count($rows) . ".\n\n";
}

try {
    corrigerTable($pdo, 'devis', 'DEV');
    corrigerTable($pdo, 'reservations', 'RES');
} catch (Exception $e) {
    echo "❌ Erreur : " . htmlspecialchars($e->getMessage()) . "\n";
}

echo "</pre>";
