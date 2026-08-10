-- ════════════════════════════════════════════════════════════
-- MISE À JOUR : Table `packages`
-- Traiteur EL MOUSSAOUI
-- À exécuter dans phpMyAdmin → base db_traiteur_elmoussaoui → onglet SQL
-- ════════════════════════════════════════════════════════════

-- 1. Ajouter les colonnes manquantes (IF NOT EXISTS évite les erreurs si déjà présentes)
ALTER TABLE `packages`
    ADD COLUMN IF NOT EXISTS `contenu`       JSON          NULL          COMMENT 'Liste des inclusions (JSON array)',
    ADD COLUMN IF NOT EXISTS `duree_heures`  DECIMAL(4,1)  DEFAULT 5     COMMENT 'Durée de la prestation en heures',
    ADD COLUMN IF NOT EXISTS `min_personnes` INT           DEFAULT 50    COMMENT 'Nombre minimum d invités',
    ADD COLUMN IF NOT EXISTS `max_personnes` INT           DEFAULT 200   COMMENT 'Nombre maximum d invités',
    ADD COLUMN IF NOT EXISTS `mis_en_avant`  TINYINT(1)    DEFAULT 0     COMMENT 'Badge Recommandé affiché',
    ADD COLUMN IF NOT EXISTS `couleur_badge` VARCHAR(20)   DEFAULT '#D4AF37' COMMENT 'Couleur du badge (hex)',
    ADD COLUMN IF NOT EXISTS `ordre`         INT           DEFAULT 0     COMMENT 'Ordre d affichage',
    ADD COLUMN IF NOT EXISTS `updated_at`    TIMESTAMP     NULL ON UPDATE CURRENT_TIMESTAMP;

-- 2. Remplir le contenu de chaque package avec les inclusions par défaut
UPDATE `packages` SET
    `contenu`       = '["Restauration de base","Thé & pâtisseries marocaines","Décoration simple","1 serveur professionnel","Tables & chaises incluses"]',
    `duree_heures`  = 5,
    `min_personnes` = 50,
    `max_personnes` = 100,
    `mis_en_avant`  = 0,
    `couleur_badge` = '#CD7F32',
    `ordre`         = 1
WHERE `nom` = 'Bronze' OR `slug` = 'bronze';

UPDATE `packages` SET
    `contenu`       = '["Restauration complète","Buffet marocain & international","Décoration florale","3 serveurs professionnels","Gâteau d anniversaire inclus","Sonorisation de base"]',
    `duree_heures`  = 7,
    `min_personnes` = 80,
    `max_personnes` = 150,
    `mis_en_avant`  = 0,
    `couleur_badge` = '#C0C0C0',
    `ordre`         = 2
WHERE `nom` = 'Argent' OR `slug` = 'argent';

UPDATE `packages` SET
    `contenu`       = '["Repas gastronomique premium","Buffet complet 20 plats","Décoration florale premium","5 serveurs en tenue","Gâteau personnalisé 3 étages","DJ + équipement son/lumière","Photographe professionnel"]',
    `duree_heures`  = 9,
    `min_personnes` = 120,
    `max_personnes` = 250,
    `mis_en_avant`  = 1,
    `couleur_badge` = '#D4AF37',
    `ordre`         = 3
WHERE `nom` = 'Or' OR `slug` = 'or';

UPDATE `packages` SET
    `contenu`       = '["Tout en Formule Or","Tente de réception 500 places","Limousine décorée + cortège","Photographe + Vidéaste HD","Animateur professionnel live","Invitations imprimées (500)","Habillement & maquillage","Coordinateur dédié J-1","Gâteau 5 étages sur mesure"]',
    `duree_heures`  = 12,
    `min_personnes` = 200,
    `max_personnes` = 500,
    `mis_en_avant`  = 0,
    `couleur_badge` = '#E8E8FF',
    `ordre`         = 4
WHERE `nom` = 'Platine' OR `slug` = 'platine';

-- 3. Si aucun package n'existe encore, les créer de zéro
INSERT IGNORE INTO `packages` (`nom`, `nom_ar`, `slug`, `prix`, `description`, `contenu`, `duree_heures`, `min_personnes`, `max_personnes`, `mis_en_avant`, `couleur_badge`, `ordre`, `actif`)
VALUES
(
    'Bronze', 'برونزية', 'bronze', 5000,
    'Formule essentielle pour les petites fêtes familiales',
    '["Restauration de base","Thé & pâtisseries marocaines","Décoration simple","1 serveur professionnel","Tables & chaises incluses"]',
    5, 50, 100, 0, '#CD7F32', 1, 1
),
(
    'Argent', 'فضية', 'argent', 10000,
    'Formule intermédiaire pour des événements réussis',
    '["Restauration complète","Buffet marocain & international","Décoration florale","3 serveurs professionnels","Gâteau inclus","Sonorisation de base"]',
    7, 80, 150, 0, '#C0C0C0', 2, 1
),
(
    'Or', 'ذهبية', 'or', 18000,
    'Notre formule la plus populaire pour les mariages et grandes fêtes',
    '["Repas gastronomique premium","Buffet complet 20 plats","Décoration florale premium","5 serveurs en tenue","Gâteau personnalisé 3 étages","DJ + son/lumière","Photographe professionnel"]',
    9, 120, 250, 1, '#D4AF37', 3, 1
),
(
    'Platine', 'بلاتينية', 'platine', 30000,
    'L expérience ultime tout inclus pour les événements d exception',
    '["Tout en Formule Or","Tente de réception 500 places","Limousine décorée","Photographe + Vidéaste HD","Animateur live","Invitations (500)","Coordinateur dédié J-1","Gâteau 5 étages"]',
    12, 200, 500, 0, '#E8E8FF', 4, 1
);

-- 4. Vérification finale
SELECT id, nom, prix, duree_heures, min_personnes, max_personnes, mis_en_avant, ordre
FROM `packages`
ORDER BY ordre;
