<?php
/**
 * includes/pricing.php
 * Calcul du prix d'un service selon son mode de tarification et le
 * nombre d'invités. Utilisé à la fois pour l'affichage (aperçu) et
 * pour l'enregistrement serveur (source de vérité, ne jamais faire
 * confiance au prix envoyé par le navigateur).
 *
 * Retourne toujours :
 *   ['prix_unitaire'=>float, 'quantite'=>float, 'total'=>float,
 *    'label_calcul'=>string, 'sur_devis'=>bool]
 */
function calculerPrixService(array $service, int $nbInvites, PDO $pdo): array
{
    $type = $service['price_type'] ?? 'fixe';
    $prixBase = (float)($service['prix_base'] ?? $service['prix'] ?? 0);
    $nbInvites = max(0, $nbInvites);

    switch ($type) {

        case 'par_personne':
            if ($prixBase <= 0) {
                return ['prix_unitaire'=>0,'quantite'=>0,'total'=>0,'label_calcul'=>'Sur devis','sur_devis'=>true];
            }
            $total = $prixBase * $nbInvites;
            return [
                'prix_unitaire' => $prixBase, 'quantite' => $nbInvites, 'total' => $total,
                'label_calcul' => number_format($prixBase,0,',',' ').' MAD × '.$nbInvites.' invités',
                'sur_devis' => false,
            ];

        case 'par_unite':
            $ratio = (int)($service['personnes_par_unite'] ?? 0);
            if ($prixBase <= 0 || $ratio <= 0 || $nbInvites <= 0) {
                return ['prix_unitaire'=>0,'quantite'=>0,'total'=>0,'label_calcul'=>'Sur devis','sur_devis'=>true];
            }
            $qte = (int)ceil($nbInvites / $ratio);
            $total = $prixBase * $qte;
            return [
                'prix_unitaire' => $prixBase, 'quantite' => $qte, 'total' => $total,
                'label_calcul' => $qte.' unité(s) (1 pour '.$ratio.' invités) × '.number_format($prixBase,0,',',' ').' MAD',
                'sur_devis' => false,
            ];

        case 'par_tranche':
            $stmt = $pdo->prepare("
                SELECT prix_par_personne FROM service_tranches
                WHERE service_id = ? AND min_invites <= ?
                  AND (max_invites IS NULL OR max_invites >= ?)
                ORDER BY min_invites DESC LIMIT 1
            ");
            $stmt->execute([$service['id'], $nbInvites, $nbInvites]);
            $prixTranche = $stmt->fetchColumn();
            if ($prixTranche === false || $nbInvites <= 0) {
                return ['prix_unitaire'=>0,'quantite'=>0,'total'=>0,'label_calcul'=>'Sur devis','sur_devis'=>true];
            }
            $prixTranche = (float)$prixTranche;
            $total = $prixTranche * $nbInvites;
            return [
                'prix_unitaire' => $prixTranche, 'quantite' => $nbInvites, 'total' => $total,
                'label_calcul' => number_format($prixTranche,0,',',' ').' MAD × '.$nbInvites.' invités (tarif par palier)',
                'sur_devis' => false,
            ];

        case 'sur_devis':
            return ['prix_unitaire'=>0,'quantite'=>0,'total'=>0,'label_calcul'=>'Sur devis','sur_devis'=>true];

        case 'fixe':
        default:
            if ($prixBase <= 0) {
                return ['prix_unitaire'=>0,'quantite'=>0,'total'=>0,'label_calcul'=>'Sur devis','sur_devis'=>true];
            }
            return [
                'prix_unitaire' => $prixBase, 'quantite' => 1, 'total' => $prixBase,
                'label_calcul' => 'Forfait fixe',
                'sur_devis' => false,
            ];
    }
}

/**
 * Recalcule le total d'une liste de services sélectionnés (par ID),
 * en repartant TOUJOURS des vraies données de la base — jamais des
 * prix envoyés par le navigateur.
 *
 * $serviceIds : liste d'IDs de services choisis par le client.
 * Retourne : ['lignes'=>[...], 'total'=>float]
 */
function recalculerDevis(array $serviceIds, int $nbInvites, PDO $pdo): array
{
    $lignes = [];
    $total = 0;

    if (empty($serviceIds)) return ['lignes'=>[], 'total'=>0];

    $placeholders = implode(',', array_fill(0, count($serviceIds), '?'));
    $stmt = $pdo->prepare("SELECT * FROM services WHERE id IN ($placeholders) AND actif=1");
    $stmt->execute($serviceIds);
    $services = $stmt->fetchAll();

    foreach ($services as $s) {
        $calc = calculerPrixService($s, $nbInvites, $pdo);
        $lignes[] = [
            'service_id'    => $s['id'],
            'nom'           => $s['nom'],
            'price_type'    => $s['price_type'] ?? 'fixe',
            'prix_unitaire' => $calc['prix_unitaire'],
            'quantite'      => $calc['quantite'],
            'total'         => $calc['total'],
            'label_calcul'  => $calc['label_calcul'],
            'sur_devis'     => $calc['sur_devis'],
        ];
        $total += $calc['total'];
    }

    return ['lignes' => $lignes, 'total' => $total];
}
