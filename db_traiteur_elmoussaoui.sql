-- ============================================================
--  BASE DE DONNÉES : db_traiteur_elmoussaoui
--  Projet   : Traiteur EL MOUSSAOUI — أفراح المساوي
--  Ville    : Errachidia (الراشيدية), Maroc
--  Contact  : 0626 986 533
--  Version  : 1.0 — Juin 2025
--  Serveur  : XAMPP (MySQL 8.x / MariaDB)
--  Auteur   : Équipe Développement
-- ============================================================
-- UTILISATION :
--   1. Ouvrir phpMyAdmin (http://localhost/phpmyadmin)
--   2. Cliquer sur "Importer" > sélectionner ce fichier
--   3. Ou via terminal : mysql -u root -p < db_traiteur_elmoussaoui.sql
-- ============================================================
SET
  SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";

SET
  time_zone = "+01:00";

-- GMT+1 Maroc
SET
  NAMES utf8mb4;

SET
  FOREIGN_KEY_CHECKS = 0;

-- ─────────────────────────────────────────────────────────────
--  CRÉATION ET SÉLECTION DE LA BASE
-- ─────────────────────────────────────────────────────────────
CREATE DATABASE IF NOT EXISTS `db_traiteur_elmoussaoui` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE `db_traiteur_elmoussaoui`;

-- ============================================================
--  TABLE : roles
--  Rôles des utilisateurs du système
-- ============================================================
CREATE TABLE `roles` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nom` VARCHAR(50) NOT NULL COMMENT 'Ex: super_admin, admin, gestionnaire, client',
  `label` VARCHAR(100) NOT NULL COMMENT 'Libellé affiché',
  `permissions` JSON NULL COMMENT 'Liste des permissions JSON',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_roles_nom` (`nom`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = 'Rôles et permissions des utilisateurs';

-- Données initiales
INSERT INTO
  `roles` (`nom`, `label`, `permissions`)
VALUES
  (
    'super_admin',
    'Super Administrateur',
    '["all"]'
  ),
  (
    'admin',
    'Administrateur',
    '["reservations","devis","clients","galerie","blog","parametres"]'
  ),
  (
    'gestionnaire',
    'Gestionnaire',
    '["reservations","devis","clients"]'
  ),
  (
    'client',
    'Client',
    '["profile","reservations_view","devis_view"]'
  );

-- ============================================================
--  TABLE : users
--  Tous les utilisateurs (admins + clients avec compte)
-- ============================================================
CREATE TABLE `users` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `role_id` INT UNSIGNED NOT NULL DEFAULT 4 COMMENT 'FK vers roles',
  `nom` VARCHAR(100) NOT NULL,
  `prenom` VARCHAR(100) NOT NULL,
  `email` VARCHAR(191) NOT NULL,
  `email_verifie_le` TIMESTAMP NULL,
  `password` VARCHAR(255) NOT NULL COMMENT 'Hash bcrypt',
  `telephone` VARCHAR(20) NULL,
  `avatar` VARCHAR(255) NULL COMMENT 'Chemin image profil',
  `langue` ENUM('fr', 'ar', 'en') NOT NULL DEFAULT 'fr',
  `remember_token` VARCHAR(100) NULL,
  `actif` TINYINT(1) NOT NULL DEFAULT 1,
  `derniere_connexion` TIMESTAMP NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` TIMESTAMP NULL COMMENT 'Soft delete',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_users_email` (`email`),
  KEY `idx_users_role` (`role_id`),
  KEY `idx_users_actif` (`actif`),
  CONSTRAINT `fk_users_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON UPDATE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = 'Utilisateurs du système (admins et clients connectés)';

-- Compte super admin initial (mot de passe : Admin@2025! — À CHANGER EN PRODUCTION)
INSERT INTO
  `users` (
    `role_id`,
    `nom`,
    `prenom`,
    `email`,
    `password`,
    `telephone`,
    `actif`
  )
VALUES
  (
    1,
    'MOUSSAOUI',
    'Admin',
    'admin@traiteur-elmoussaoui.ma',
    '$2y$12$eImiTXuWVxfM37uY4JANjQ==hashedpassword_change_this',
    '0626986533',
    1
  );

-- ============================================================
--  TABLE : clients
--  Fiches clients détaillées (liées ou non à un compte user)
-- ============================================================
CREATE TABLE `clients` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NULL COMMENT 'NULL si client sans compte',
  `civilite` ENUM('M.', 'Mme', 'Dr', 'Prof') NOT NULL DEFAULT 'M.',
  `nom` VARCHAR(100) NOT NULL,
  `prenom` VARCHAR(100) NOT NULL,
  `email` VARCHAR(191) NOT NULL,
  `telephone` VARCHAR(20) NOT NULL,
  `telephone2` VARCHAR(20) NULL,
  `adresse` VARCHAR(255) NULL,
  `ville` VARCHAR(100) NULL DEFAULT 'Errachidia',
  `code_postal` VARCHAR(10) NULL,
  `pays` VARCHAR(50) NOT NULL DEFAULT 'Maroc',
  `cin` VARCHAR(20) NULL COMMENT 'Carte nationale identité',
  `notes_internes` TEXT NULL,
  `source` ENUM(
    'site_web',
    'telephone',
    'reference',
    'facebook',
    'instagram',
    'autre'
  ) DEFAULT 'site_web',
  `actif` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_clients_email` (`email`),
  KEY `idx_clients_user` (`user_id`),
  KEY `idx_clients_ville` (`ville`),
  CONSTRAINT `fk_clients_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE
  SET
    NULL ON UPDATE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = 'Fiches clients de Traiteur EL MOUSSAOUI';

-- ============================================================
--  TABLE : types_evenements
--  Catégories d'événements organisés
-- ============================================================
CREATE TABLE `types_evenements` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nom` VARCHAR(100) NOT NULL,
  `nom_ar` VARCHAR(100) NULL COMMENT 'Nom en arabe',
  `slug` VARCHAR(120) NOT NULL,
  `description` TEXT NULL,
  `icone` VARCHAR(100) NULL COMMENT 'Classe CSS icône (Font Awesome)',
  `couleur` VARCHAR(7) NULL COMMENT 'Couleur hex ex: #B8860B',
  `image` VARCHAR(255) NULL,
  `ordre` INT UNSIGNED NOT NULL DEFAULT 0,
  `actif` TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_types_slug` (`slug`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = 'Types d''événements organisés';

INSERT INTO
  `types_evenements` (
    `nom`,
    `nom_ar`,
    `slug`,
    `description`,
    `icone`,
    `couleur`,
    `ordre`,
    `actif`
  )
VALUES
  (
    'Mariage',
    'عرس',
    'mariage',
    'Organisation complète de cérémonies de mariage traditionnelles et modernes',
    'fa-heart',
    '#D4AF37',
    1,
    1
  ),
  (
    'Fiançailles',
    'خطوبة',
    'fiancailles',
    'Cérémonie de fiançailles élégante et mémorable',
    'fa-ring',
    '#B8860B',
    2,
    1
  ),
  (
    'Circoncision',
    'عقيقة وختان',
    'circoncision',
    'Fêtes traditionnelles de circoncision avec décoration et buffet',
    'fa-baby',
    '#C0A000',
    3,
    1
  ),
  (
    'Anniversaire',
    'عيد ميلاد',
    'anniversaire',
    'Célébrations d''anniversaire pour tous les âges',
    'fa-birthday-cake',
    '#FFD700',
    4,
    1
  ),
  (
    'Réception d''entreprise',
    'تظاهرة مهنية',
    'reception-pro',
    'Séminaires, conférences, galas et réceptions professionnelles',
    'fa-briefcase',
    '#8B7500',
    5,
    1
  ),
  (
    'Buffet & Banquet',
    'بوفيه وضيافة',
    'buffet-banquet',
    'Service de buffet froid/chaud et banquets pour toutes occasions',
    'fa-utensils',
    '#A0800B',
    6,
    1
  ),
  (
    'Cérémonie religieuse',
    'مناسبة دينية',
    'ceremonie-reli',
    'Fêtes religieuses : Aid, Mouloud, Laylat Al-Qadr...',
    'fa-mosque',
    '#6B5B00',
    7,
    1
  ),
  (
    'Autre',
    'مناسبة أخرى',
    'autre',
    'Tout autre type de célébration ou d''événement',
    'fa-star',
    '#9A8000',
    8,
    1
  );

-- ============================================================
--  TABLE : services
--  Services proposés par le traiteur
-- ============================================================
CREATE TABLE `services` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nom` VARCHAR(150) NOT NULL,
  `nom_ar` VARCHAR(150) NULL,
  `slug` VARCHAR(170) NOT NULL,
  `description` TEXT NULL,
  `description_ar` TEXT NULL,
  `prix_base` DECIMAL(10, 2) NULL COMMENT 'Prix de base unitaire (MAD)',
  `unite` VARCHAR(50) NULL COMMENT 'Ex: par personne, forfait, par heure',
  `image` VARCHAR(255) NULL,
  `icone` VARCHAR(100) NULL,
  `ordre` INT UNSIGNED NOT NULL DEFAULT 0,
  `actif` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_services_slug` (`slug`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = 'Services proposés par Traiteur EL MOUSSAOUI';

INSERT INTO
  `services` (
    `nom`,
    `nom_ar`,
    `slug`,
    `description`,
    `prix_base`,
    `unite`,
    `icone`,
    `ordre`,
    `actif`
  )
VALUES
  (
    'Restauration & Traiteur',
    'خدمات الطبخ والضيافة',
    'restauration-traiteur',
    'Préparation et service de repas marocains et internationaux',
    80.00,
    'par personne',
    'fa-utensils',
    1,
    1
  ),
  (
    'Décoration & Scénographie',
    'الديكور والزينة',
    'decoration-sceno',
    'Décoration florale, ballons, mise en scène, thèmes personnalisés',
    2000.00,
    'forfait',
    'fa-leaf',
    2,
    1
  ),
  (
    'Tentes & Mobilier',
    'الخيام والأثاث',
    'tentes-mobilier',
    'Location de tentes de réception, tables, chaises, nappage',
    500.00,
    'par unité',
    'fa-campground',
    3,
    1
  ),
  (
    'Animation & Musique',
    'التنشيط والموسيقى',
    'animation-musique',
    'DJ, groupe musical traditionnel (gnaoua, chaabi), animateur',
    3000.00,
    'forfait',
    'fa-music',
    4,
    1
  ),
  (
    'Photographe & Vidéaste',
    'التصوير الفوتوغرافي',
    'photo-video',
    'Reportage photo et vidéo professionnel de l''événement',
    2500.00,
    'forfait',
    'fa-camera',
    5,
    1
  ),
  (
    'Service & Personnel',
    'فريق الخدمة',
    'service-personnel',
    'Serveurs, maître d''hôtel, coordinateurs d''événements',
    200.00,
    'par personne',
    'fa-user-tie',
    6,
    1
  ),
  (
    'Gâteau de fête',
    'كعكة الاحتفال',
    'gateau-fete',
    'Pièce montée et gâteaux personnalisés sur commande',
    800.00,
    'forfait',
    'fa-birthday-cake',
    7,
    1
  ),
  (
    'Habillement & Coiffure',
    'تجميل وتزيين',
    'habillement-coiffure',
    'Services de maquillage, henné et coiffure pour la mariée',
    1500.00,
    'forfait',
    'fa-spa',
    8,
    1
  ),
  (
    'Transport & Limousine',
    'النقل والليموزين',
    'transport-limousine',
    'Voitures décorées pour le cortège et transport des invités',
    1200.00,
    'par véhicule',
    'fa-car',
    9,
    1
  ),
  (
    'Invitations & Papeterie',
    'الدعوات والطباعة',
    'invitations',
    'Conception et impression des cartons d''invitation',
    15.00,
    'par unité',
    'fa-envelope',
    10,
    1
  );

-- ============================================================
--  TABLE : packages
--  Formules tarifaires (Bronze, Argent, Or, Platine)
-- ============================================================
CREATE TABLE `packages` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nom` VARCHAR(100) NOT NULL,
  `nom_ar` VARCHAR(100) NULL,
  `slug` VARCHAR(120) NOT NULL,
  `description` TEXT NULL,
  `couleur_badge` VARCHAR(7) NULL COMMENT 'Couleur hex du badge',
  `prix` DECIMAL(10, 2) NOT NULL COMMENT 'Prix total du package (MAD)',
  `min_personnes` INT UNSIGNED NULL,
  `max_personnes` INT UNSIGNED NULL,
  `duree_heures` DECIMAL(4, 1) NULL COMMENT 'Durée incluse en heures',
  `contenu` JSON NULL COMMENT 'Liste des éléments inclus',
  `mis_en_avant` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1 = recommandé (badge)',
  `ordre` INT UNSIGNED NOT NULL DEFAULT 0,
  `actif` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_packages_slug` (`slug`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = 'Packages tarifaires proposés';

INSERT INTO
  `packages` (
    `nom`,
    `nom_ar`,
    `slug`,
    `description`,
    `couleur_badge`,
    `prix`,
    `min_personnes`,
    `max_personnes`,
    `duree_heures`,
    `contenu`,
    `mis_en_avant`,
    `ordre`,
    `actif`
  )
VALUES
  (
    'Formule Bronze',
    'الباقة البرونزية',
    'bronze',
    'L''essentiel pour une belle fête',
    '#CD7F32',
    5000.00,
    50,
    100,
    5.0,
    '["Restauration basique","Décoration simple","1 serveur","Thé et pâtisseries"]',
    0,
    1,
    1
  ),
  (
    'Formule Argent',
    'الباقة الفضية',
    'argent',
    'Confort et élégance réunis',
    '#C0C0C0',
    10000.00,
    80,
    150,
    7.0,
    '["Restauration complète","Décoration florale","3 serveurs","Gâteau","Son de base"]',
    0,
    2,
    1
  ),
  (
    'Formule Or',
    'الباقة الذهبية',
    'or',
    'L''excellence à votre service',
    '#D4AF37',
    18000.00,
    120,
    250,
    9.0,
    '["Repas gastronomique","Décoration premium","5 serveurs","Gâteau","DJ","Photo"]',
    1,
    3,
    1
  ),
  (
    'Formule Platine',
    'الباقة البلاتينية',
    'platine',
    'Le tout inclus, zéro souci',
    '#E5E4E2',
    30000.00,
    200,
    500,
    12.0,
    '["Tout service","Tente","Limousine","Animateur","Photo & Vidéo","Invitations"]',
    0,
    4,
    1
  );

-- ============================================================
--  TABLE : packages_services  (pivot)
--  Services inclus dans chaque package
-- ============================================================
CREATE TABLE `packages_services` (
  `package_id` INT UNSIGNED NOT NULL,
  `service_id` INT UNSIGNED NOT NULL,
  `quantite` INT UNSIGNED NOT NULL DEFAULT 1,
  `note` VARCHAR(255) NULL,
  PRIMARY KEY (`package_id`, `service_id`),
  CONSTRAINT `fk_pkgsvc_package` FOREIGN KEY (`package_id`) REFERENCES `packages` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_pkgsvc_service` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = 'Services inclus dans chaque package';

-- ============================================================
--  TABLE : salles
--  Salles et espaces de réception gérés ou partenaires
-- ============================================================
CREATE TABLE `salles` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nom` VARCHAR(150) NOT NULL,
  `adresse` VARCHAR(255) NULL,
  `ville` VARCHAR(100) NOT NULL DEFAULT 'Errachidia',
  `capacite_min` INT UNSIGNED NULL,
  `capacite_max` INT UNSIGNED NULL,
  `description` TEXT NULL,
  `image` VARCHAR(255) NULL,
  `telephone` VARCHAR(20) NULL,
  `est_partenaire` TINYINT(1) NOT NULL DEFAULT 0,
  `actif` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = 'Salles et espaces disponibles pour les événements';

-- ============================================================
--  TABLE : reservations
--  Réservations confirmées des clients
-- ============================================================
CREATE TABLE `reservations` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `reference` VARCHAR(30) NOT NULL COMMENT 'Ex: RES-2025-0001',
  `client_id` INT UNSIGNED NOT NULL,
  `type_evenement_id` INT UNSIGNED NOT NULL,
  `package_id` INT UNSIGNED NULL,
  `salle_id` INT UNSIGNED NULL,
  `date_evenement` DATE NOT NULL,
  `heure_debut` TIME NOT NULL DEFAULT '18:00:00',
  `heure_fin` TIME NULL,
  `lieu` VARCHAR(255) NULL COMMENT 'Si salle hors liste',
  `nbr_invites` INT UNSIGNED NOT NULL DEFAULT 100,
  `statut` ENUM(
    'en_attente',
    'confirmee',
    'en_cours',
    'terminee',
    'annulee'
  ) NOT NULL DEFAULT 'en_attente',
  `motif_annulation` TEXT NULL,
  `notes_client` TEXT NULL,
  `notes_internes` TEXT NULL,
  `montant_total` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
  `montant_acompte` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
  `acompte_paye` TINYINT(1) NOT NULL DEFAULT 0,
  `solde_restant` DECIMAL(12, 2) GENERATED ALWAYS AS (`montant_total` - `montant_acompte`) VIRTUAL,
  `user_id_createur` INT UNSIGNED NULL COMMENT 'Admin ayant créé/saisi la réservation',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_reservations_ref` (`reference`),
  KEY `idx_res_client` (`client_id`),
  KEY `idx_res_type_event` (`type_evenement_id`),
  KEY `idx_res_date` (`date_evenement`),
  KEY `idx_res_statut` (`statut`),
  CONSTRAINT `fk_res_client` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_res_type_evt` FOREIGN KEY (`type_evenement_id`) REFERENCES `types_evenements` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_res_package` FOREIGN KEY (`package_id`) REFERENCES `packages` (`id`) ON DELETE
  SET
    NULL,
    CONSTRAINT `fk_res_salle` FOREIGN KEY (`salle_id`) REFERENCES `salles` (`id`) ON DELETE
  SET
    NULL,
    CONSTRAINT `fk_res_createur` FOREIGN KEY (`user_id_createur`) REFERENCES `users` (`id`) ON DELETE
  SET
    NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = 'Réservations d''événements';

-- ============================================================
--  TABLE : reservation_services  (pivot)
--  Services commandés dans une réservation
-- ============================================================
CREATE TABLE `reservation_services` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `reservation_id` INT UNSIGNED NOT NULL,
  `service_id` INT UNSIGNED NOT NULL,
  `quantite` INT UNSIGNED NOT NULL DEFAULT 1,
  `prix_unitaire` DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
  `prix_total` DECIMAL(10, 2) GENERATED ALWAYS AS (`quantite` * `prix_unitaire`) STORED,
  `note` VARCHAR(255) NULL,
  PRIMARY KEY (`id`),
  KEY `idx_ressvc_reservation` (`reservation_id`),
  KEY `idx_ressvc_service` (`service_id`),
  CONSTRAINT `fk_ressvc_reservation` FOREIGN KEY (`reservation_id`) REFERENCES `reservations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ressvc_service` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON UPDATE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = 'Détail des services par réservation';

-- ============================================================
--  TABLE : devis
--  Demandes de devis et devis générés
-- ============================================================
CREATE TABLE `devis` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `reference` VARCHAR(30) NOT NULL COMMENT 'Ex: DEV-2025-0001',
  `client_id` INT UNSIGNED NULL COMMENT 'NULL si prospect sans fiche',
  `nom_prospect` VARCHAR(200) NULL COMMENT 'Si pas encore client',
  `email_prospect` VARCHAR(191) NULL,
  `telephone_prospect` VARCHAR(20) NULL,
  `type_evenement_id` INT UNSIGNED NULL,
  `date_evenement` DATE NULL,
  `nbr_invites` INT UNSIGNED NULL,
  `lieu` VARCHAR(255) NULL,
  `message` TEXT NULL COMMENT 'Demande initiale du client',
  `notes_internes` TEXT NULL,
  `statut` ENUM(
    'recu',
    'en_traitement',
    'envoye',
    'accepte',
    'refuse',
    'expire'
  ) NOT NULL DEFAULT 'recu',
  `date_expiration` DATE NULL COMMENT 'Validité du devis',
  `montant_ht` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
  `tva_pct` DECIMAL(5, 2) NOT NULL DEFAULT 20.00 COMMENT 'TVA en %',
  `montant_tva` DECIMAL(12, 2) GENERATED ALWAYS AS (`montant_ht` * `tva_pct` / 100) STORED,
  `montant_ttc` DECIMAL(12, 2) GENERATED ALWAYS AS (`montant_ht` + `montant_ht` * `tva_pct` / 100) STORED,
  `reservation_id` INT UNSIGNED NULL COMMENT 'Si converti en réservation',
  `user_id_traitant` INT UNSIGNED NULL COMMENT 'Admin qui traite ce devis',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_devis_ref` (`reference`),
  KEY `idx_devis_client` (`client_id`),
  KEY `idx_devis_statut` (`statut`),
  KEY `idx_devis_date_evt` (`date_evenement`),
  CONSTRAINT `fk_devis_client` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE
  SET
    NULL,
    CONSTRAINT `fk_devis_type_evt` FOREIGN KEY (`type_evenement_id`) REFERENCES `types_evenements` (`id`) ON DELETE
  SET
    NULL,
    CONSTRAINT `fk_devis_reservation` FOREIGN KEY (`reservation_id`) REFERENCES `reservations` (`id`) ON DELETE
  SET
    NULL,
    CONSTRAINT `fk_devis_traitant` FOREIGN KEY (`user_id_traitant`) REFERENCES `users` (`id`) ON DELETE
  SET
    NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = 'Demandes et documents de devis';

-- ============================================================
--  TABLE : devis_lignes
--  Lignes de détail d'un devis
-- ============================================================
CREATE TABLE `devis_lignes` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `devis_id` INT UNSIGNED NOT NULL,
  `service_id` INT UNSIGNED NULL,
  `designation` VARCHAR(255) NOT NULL,
  `description` TEXT NULL,
  `quantite` DECIMAL(8, 2) NOT NULL DEFAULT 1,
  `unite` VARCHAR(50) NULL,
  `prix_unitaire` DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
  `remise_pct` DECIMAL(5, 2) NOT NULL DEFAULT 0.00,
  `montant_ht` DECIMAL(12, 2) GENERATED ALWAYS AS (
    `quantite` * `prix_unitaire` * (1 - `remise_pct` / 100)
  ) STORED,
  `ordre` INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_devlig_devis` (`devis_id`),
  KEY `idx_devlig_service` (`service_id`),
  CONSTRAINT `fk_devlig_devis` FOREIGN KEY (`devis_id`) REFERENCES `devis` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_devlig_service` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE
  SET
    NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = 'Lignes de détail des devis';

-- ============================================================
--  TABLE : factures
--  Factures liées aux réservations
-- ============================================================
CREATE TABLE `factures` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `reference` VARCHAR(30) NOT NULL COMMENT 'Ex: FAC-2025-0001',
  `reservation_id` INT UNSIGNED NOT NULL,
  `client_id` INT UNSIGNED NOT NULL,
  `date_facture` DATE NOT NULL DEFAULT (CURDATE()),
  `date_echeance` DATE NULL,
  `statut` ENUM(
    'en_attente',
    'partiellement_payee',
    'payee',
    'annulee'
  ) NOT NULL DEFAULT 'en_attente',
  `montant_ht` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
  `tva_pct` DECIMAL(5, 2) NOT NULL DEFAULT 20.00,
  `montant_tva` DECIMAL(12, 2) GENERATED ALWAYS AS (`montant_ht` * `tva_pct` / 100) STORED,
  `montant_ttc` DECIMAL(12, 2) GENERATED ALWAYS AS (`montant_ht` + `montant_ht` * `tva_pct` / 100) STORED,
  `montant_paye` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
  `notes` TEXT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_factures_ref` (`reference`),
  KEY `idx_fac_reservation` (`reservation_id`),
  KEY `idx_fac_client` (`client_id`),
  KEY `idx_fac_statut` (`statut`),
  CONSTRAINT `fk_fac_reservation` FOREIGN KEY (`reservation_id`) REFERENCES `reservations` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_fac_client` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON UPDATE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = 'Factures des prestations';

-- ============================================================
--  TABLE : paiements
--  Enregistrement des paiements reçus
-- ============================================================
CREATE TABLE `paiements` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `facture_id` INT UNSIGNED NOT NULL,
  `montant` DECIMAL(12, 2) NOT NULL,
  `mode` ENUM(
    'especes',
    'virement',
    'cheque',
    'cmi',
    'wave',
    'whatsapp_pay',
    'autre'
  ) NOT NULL DEFAULT 'especes',
  `reference_pmt` VARCHAR(100) NULL COMMENT 'Numéro de virement, chèque, etc.',
  `date_paiement` DATE NOT NULL DEFAULT (CURDATE()),
  `recu_par` INT UNSIGNED NULL COMMENT 'FK user admin',
  `note` TEXT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_pmt_facture` (`facture_id`),
  CONSTRAINT `fk_pmt_facture` FOREIGN KEY (`facture_id`) REFERENCES `factures` (`id`) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = 'Paiements reçus';

-- ============================================================
--  TABLE : categories_galerie
--  Catégories pour classer les photos/vidéos
-- ============================================================
CREATE TABLE `categories_galerie` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nom` VARCHAR(100) NOT NULL,
  `nom_ar` VARCHAR(100) NULL,
  `slug` VARCHAR(120) NOT NULL,
  `ordre` INT UNSIGNED NOT NULL DEFAULT 0,
  `actif` TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_catgal_slug` (`slug`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = 'Catégories de la galerie';

INSERT INTO
  `categories_galerie` (`nom`, `nom_ar`, `slug`, `ordre`, `actif`)
VALUES
  (
    'Mariages',
    'الأعراس',
    'mariages',
    1,
    1
  ),
  (
    'Fiançailles',
    'الخطوبة',
    'fiancailles',
    2,
    1
  ),
  (
    'Décoration',
    'الديكور',
    'decoration',
    3,
    1
  ),
  (
    'Buffets',
    'البوفيه',
    'buffets',
    4,
    1
  ),
  (
    'Anniversaires',
    'أعياد الميلاد',
    'anniversaires',
    5,
    1
  ),
  (
    'Tentes & Salles',
    'الخيام والقاعات',
    'tentes-salles',
    6,
    1
  ),
  (
    'Équipe',
    'الفريق',
    'equipe',
    7,
    1
  );

-- ============================================================
--  TABLE : galerie
--  Médias (photos et vidéos) d'événements
-- ============================================================
CREATE TABLE `galerie` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `categorie_id` INT UNSIGNED NOT NULL,
  `type` ENUM('photo', 'video') NOT NULL DEFAULT 'photo',
  `titre` VARCHAR(200) NULL,
  `description` TEXT NULL,
  `fichier` VARCHAR(255) NULL COMMENT 'Chemin fichier image',
  `url_video` VARCHAR(500) NULL COMMENT 'URL YouTube/Vimeo si vidéo',
  `miniature` VARCHAR(255) NULL COMMENT 'Chemin miniature',
  `alt_text` VARCHAR(255) NULL COMMENT 'Texte alternatif SEO',
  `tags` JSON NULL,
  `ordre` INT UNSIGNED NOT NULL DEFAULT 0,
  `vues` INT UNSIGNED NOT NULL DEFAULT 0,
  `en_vedette` TINYINT(1) NOT NULL DEFAULT 0,
  `actif` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_galerie_cat` (`categorie_id`),
  KEY `idx_galerie_type` (`type`),
  KEY `idx_galerie_vedette` (`en_vedette`),
  CONSTRAINT `fk_galerie_cat` FOREIGN KEY (`categorie_id`) REFERENCES `categories_galerie` (`id`) ON UPDATE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = 'Photos et vidéos des événements réalisés';

-- ============================================================
--  TABLE : categories_blog
-- ============================================================
CREATE TABLE `categories_blog` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nom` VARCHAR(100) NOT NULL,
  `nom_ar` VARCHAR(100) NULL,
  `slug` VARCHAR(120) NOT NULL,
  `ordre` INT UNSIGNED NOT NULL DEFAULT 0,
  `actif` TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_catblog_slug` (`slug`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

INSERT INTO
  `categories_blog` (`nom`, `nom_ar`, `slug`, `ordre`)
VALUES
  (
    'Conseils Mariage',
    'نصائح الزواج',
    'conseils-mariage',
    1
  ),
  (
    'Tendances Décoration',
    'ترندات الديكور',
    'tendances-decoration',
    2
  ),
  (
    'Reportages',
    'ريبورتاجات',
    'reportages',
    3
  ),
  (
    'Recettes & Buffets',
    'وصفات وبوفيه',
    'recettes-buffets',
    4
  ),
  (
    'Actualités',
    'أخبار',
    'actualites',
    5
  );

-- ============================================================
--  TABLE : blog_articles
-- ============================================================
CREATE TABLE `blog_articles` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `categorie_id` INT UNSIGNED NOT NULL,
  `auteur_id` INT UNSIGNED NULL,
  `titre` VARCHAR(255) NOT NULL,
  `titre_ar` VARCHAR(255) NULL,
  `slug` VARCHAR(280) NOT NULL,
  `extrait` TEXT NULL,
  `contenu` LONGTEXT NOT NULL,
  `image_principale` VARCHAR(255) NULL,
  `tags` JSON NULL,
  `statut` ENUM('brouillon', 'publie', 'archive') NOT NULL DEFAULT 'brouillon',
  `date_publication` TIMESTAMP NULL,
  `vues` INT UNSIGNED NOT NULL DEFAULT 0,
  `meta_titre` VARCHAR(160) NULL,
  `meta_description` VARCHAR(320) NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_blog_slug` (`slug`),
  KEY `idx_blog_cat` (`categorie_id`),
  KEY `idx_blog_statut` (`statut`),
  KEY `idx_blog_date_pub` (`date_publication`),
  CONSTRAINT `fk_blog_cat` FOREIGN KEY (`categorie_id`) REFERENCES `categories_blog` (`id`),
  CONSTRAINT `fk_blog_auteur` FOREIGN KEY (`auteur_id`) REFERENCES `users` (`id`) ON DELETE
  SET
    NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = 'Articles du blog';

-- ============================================================
--  TABLE : commentaires
-- ============================================================
CREATE TABLE `commentaires` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `article_id` INT UNSIGNED NOT NULL,
  `parent_id` INT UNSIGNED NULL COMMENT 'Pour les réponses',
  `nom` VARCHAR(100) NOT NULL,
  `email` VARCHAR(191) NOT NULL,
  `contenu` TEXT NOT NULL,
  `statut` ENUM('en_attente', 'approuve', 'spam') NOT NULL DEFAULT 'en_attente',
  `ip_address` VARCHAR(45) NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_com_article` (`article_id`),
  KEY `idx_com_parent` (`parent_id`),
  KEY `idx_com_statut` (`statut`),
  CONSTRAINT `fk_com_article` FOREIGN KEY (`article_id`) REFERENCES `blog_articles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_com_parent` FOREIGN KEY (`parent_id`) REFERENCES `commentaires` (`id`) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- ============================================================
--  TABLE : temoignages
--  Avis et témoignages clients
-- ============================================================
CREATE TABLE `temoignages` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `client_id` INT UNSIGNED NULL,
  `reservation_id` INT UNSIGNED NULL,
  `nom_client` VARCHAR(150) NOT NULL,
  `ville` VARCHAR(100) NULL,
  `contenu` TEXT NOT NULL,
  `note` TINYINT UNSIGNED NOT NULL DEFAULT 5 COMMENT 'Note /5',
  `type_evenement` VARCHAR(100) NULL,
  `photo` VARCHAR(255) NULL,
  `statut` ENUM('en_attente', 'publie', 'refuse') NOT NULL DEFAULT 'en_attente',
  `en_vedette` TINYINT(1) NOT NULL DEFAULT 0,
  `ordre` INT UNSIGNED NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_tem_client` (`client_id`),
  KEY `idx_tem_statut` (`statut`),
  CONSTRAINT `fk_tem_client` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE
  SET
    NULL,
    CONSTRAINT `fk_tem_reservation` FOREIGN KEY (`reservation_id`) REFERENCES `reservations` (`id`) ON DELETE
  SET
    NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = 'Témoignages et avis des clients';

-- Témoignages de démonstration
INSERT INTO
  `temoignages` (
    `nom_client`,
    `ville`,
    `contenu`,
    `note`,
    `type_evenement`,
    `statut`,
    `en_vedette`,
    `ordre`
  )
VALUES
  (
    'Fatima Zahra B.',
    'Errachidia',
    'Un service exceptionnel pour notre mariage ! L''équipe d''EL MOUSSAOUI a tout géré avec professionnalisme. La décoration était magnifique et le buffet délicieux. Nous recommandons vivement !',
    5,
    'Mariage',
    'publie',
    1,
    1
  ),
  (
    'Mohammed K.',
    'Errachidia',
    'Très satisfait de l''organisation de notre cérémonie de fiançailles. Équipe réactive, prix raisonnables et résultat au-delà de nos espérances. Merci !',
    5,
    'Fiançailles',
    'publie',
    1,
    2
  ),
  (
    'Aicha M.',
    'Goulmima',
    'Service de qualité pour notre buffet familial. Livraison à temps, plats chauds et savoureux. Je referai appel à Traiteur EL MOUSSAOUI sans hésitation.',
    4,
    'Buffet',
    'publie',
    0,
    3
  ),
  (
    'Hassan El A.',
    'Erfoud',
    'Notre mariage était un vrai conte de fée grâce à leur travail. La tente, la décoration, la musique... tout était parfait. Bravo à toute l''équipe !',
    5,
    'Mariage',
    'publie',
    1,
    4
  );

-- ============================================================
--  TABLE : contacts
--  Messages reçus via le formulaire de contact
-- ============================================================
CREATE TABLE `contacts` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nom` VARCHAR(100) NOT NULL,
  `prenom` VARCHAR(100) NULL,
  `email` VARCHAR(191) NOT NULL,
  `telephone` VARCHAR(20) NULL,
  `sujet` VARCHAR(200) NULL,
  `message` TEXT NOT NULL,
  `statut` ENUM('nouveau', 'lu', 'traite', 'archive') NOT NULL DEFAULT 'nouveau',
  `ip_address` VARCHAR(45) NULL,
  `lu_le` TIMESTAMP NULL,
  `repondu_le` TIMESTAMP NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_contacts_statut` (`statut`),
  KEY `idx_contacts_date` (`created_at`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = 'Messages du formulaire de contact';

-- ============================================================
--  TABLE : notifications
--  Notifications internes pour les admins
-- ============================================================
CREATE TABLE `notifications` (
  `id` CHAR(36) NOT NULL COMMENT 'UUID',
  `type` VARCHAR(100) NOT NULL COMMENT 'Classe de notification',
  `notifiable_type` VARCHAR(100) NOT NULL,
  `notifiable_id` INT UNSIGNED NOT NULL,
  `data` JSON NOT NULL,
  `read_at` TIMESTAMP NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_notif_notifiable` (`notifiable_type`, `notifiable_id`),
  KEY `idx_notif_read` (`read_at`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = 'Notifications système';

-- ============================================================
--  TABLE : parametres
--  Configuration générale du site web
-- ============================================================
CREATE TABLE `parametres` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `cle` VARCHAR(100) NOT NULL COMMENT 'Clé de configuration',
  `valeur` TEXT NULL,
  `groupe` VARCHAR(50) NOT NULL DEFAULT 'general',
  `label` VARCHAR(150) NULL,
  `type` ENUM(
    'text',
    'textarea',
    'number',
    'boolean',
    'json',
    'color',
    'email',
    'url',
    'password'
  ) DEFAULT 'text',
  `ordre` INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_param_cle` (`cle`),
  KEY `idx_param_groupe` (`groupe`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = 'Paramètres de configuration du site';

INSERT INTO
  `parametres` (
    `cle`,
    `valeur`,
    `groupe`,
    `label`,
    `type`,
    `ordre`
  )
VALUES
  -- Informations générales
  (
    'site_nom',
    'Traiteur EL MOUSSAOUI',
    'general',
    'Nom du site',
    'text',
    1
  ),
  (
    'site_slogan',
    'Organisation des Évènements et des Fêtes',
    'general',
    'Slogan',
    'text',
    2
  ),
  (
    'site_slogan_ar',
    'تنظيم وتجهيز جميع المناسبات والحفلات',
    'general',
    'Slogan (Arabe)',
    'text',
    3
  ),
  (
    'site_description',
    'Votre traiteur de confiance à Errachidia pour tous vos événements et fêtes.',
    'general',
    'Description courte',
    'textarea',
    4
  ),
  (
    'site_couleur_primaire',
    '#D4AF37',
    'general',
    'Couleur principale',
    'color',
    5
  ),
  (
    'site_logo',
    'assets/img/logo.png',
    'general',
    'Logo principal',
    'text',
    6
  ),
  -- Contact
  (
    'contact_telephone',
    '0626986533',
    'contact',
    'Téléphone principal',
    'text',
    1
  ),
  (
    'contact_whatsapp',
    '+212626986533',
    'contact',
    'Numéro WhatsApp',
    'text',
    2
  ),
  (
    'contact_email',
    'contact@traiteur-elmoussaoui.ma',
    'contact',
    'E-mail de contact',
    'email',
    3
  ),
  (
    'contact_adresse',
    'Errachidia, Région Drâa-Tafilalet, Maroc',
    'contact',
    'Adresse',
    'textarea',
    4
  ),
  (
    'contact_maps_lat',
    '31.9314',
    'contact',
    'Latitude Google Maps',
    'text',
    5
  ),
  (
    'contact_maps_lng',
    '-4.4264',
    'contact',
    'Longitude Google Maps',
    'text',
    6
  ),
  (
    'horaires_ouverture',
    'Lun–Sam : 08h–20h | Dim : 09h–18h',
    'contact',
    'Horaires d''ouverture',
    'text',
    7
  ),
  -- Réseaux sociaux
  (
    'rs_facebook',
    'https://facebook.com/traiteur.elmoussaoui',
    'social',
    'Facebook',
    'url',
    1
  ),
  (
    'rs_instagram',
    'https://instagram.com/elmoussaoui_traiteur',
    'social',
    'Instagram',
    'url',
    2
  ),
  (
    'rs_tiktok',
    '',
    'social',
    'TikTok',
    'url',
    3
  ),
  (
    'rs_youtube',
    '',
    'social',
    'YouTube',
    'url',
    4
  ),
  -- SEO
  (
    'seo_meta_titre',
    'Traiteur EL MOUSSAOUI — Errachidia | Organisation Mariages & Fêtes',
    'seo',
    'Meta Titre',
    'text',
    1
  ),
  (
    'seo_meta_description',
    'Traiteur EL MOUSSAOUI, spécialiste organisation mariages, fiançailles et événements à Errachidia, Maroc. Contact : 0626 986 533',
    'seo',
    'Meta Description',
    'textarea',
    2
  ),
  -- Email SMTP
  (
    'smtp_host',
    'smtp.gmail.com',
    'email',
    'Serveur SMTP',
    'text',
    1
  ),
  (
    'smtp_port',
    '587',
    'email',
    'Port SMTP',
    'number',
    2
  ),
  (
    'smtp_encryption',
    'tls',
    'email',
    'Chiffrement',
    'text',
    3
  ),
  (
    'smtp_utilisateur',
    'noreply@traiteur-elmoussaoui.ma',
    'email',
    'Utilisateur SMTP',
    'email',
    4
  ),
  (
    'smtp_mot_de_passe',
    '',
    'email',
    'Mot de passe SMTP',
    'password',
    5
  ),
  (
    'email_expediteur',
    'noreply@traiteur-elmoussaoui.ma',
    'email',
    'E-mail expéditeur',
    'email',
    6
  ),
  (
    'email_nom_expediteur',
    'Traiteur EL MOUSSAOUI',
    'email',
    'Nom expéditeur',
    'text',
    7
  ),
  -- Paramètres réservation
  (
    'devis_tva_defaut',
    '20',
    'reservation',
    'TVA par défaut (%)',
    'number',
    1
  ),
  (
    'devis_validite_jours',
    '30',
    'reservation',
    'Validité devis (j)',
    'number',
    2
  ),
  (
    'reservation_acompte_pct',
    '30',
    'reservation',
    'Acompte requis (%)',
    'number',
    3
  ),
  (
    'maintenance_mode',
    '0',
    'system',
    'Mode maintenance',
    'boolean',
    1
  );

-- ============================================================
--  TABLE : activity_logs
--  Journal d'activité des administrateurs
-- ============================================================
CREATE TABLE `activity_logs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NULL,
  `action` VARCHAR(100) NOT NULL COMMENT 'Ex: create, update, delete, login',
  `module` VARCHAR(100) NULL COMMENT 'Ex: reservations, devis, clients',
  `description` TEXT NULL,
  `entite_type` VARCHAR(100) NULL,
  `entite_id` INT UNSIGNED NULL,
  `donnees_avant` JSON NULL COMMENT 'Données avant modification',
  `donnees_apres` JSON NULL COMMENT 'Données après modification',
  `ip_address` VARCHAR(45) NULL,
  `user_agent` VARCHAR(500) NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_log_user` (`user_id`),
  KEY `idx_log_action` (`action`),
  KEY `idx_log_module` (`module`),
  KEY `idx_log_date` (`created_at`),
  CONSTRAINT `fk_log_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE
  SET
    NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = 'Journal d''activité administrateur';

-- ============================================================
--  TABLE : calendrier_disponibilites
--  Gestion des disponibilités pour les réservations
-- ============================================================
CREATE TABLE `calendrier_disponibilites` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `date` DATE NOT NULL,
  `statut` ENUM('disponible', 'occupe', 'bloque', 'maintenance') NOT NULL DEFAULT 'disponible',
  `reservation_id` INT UNSIGNED NULL,
  `note` VARCHAR(255) NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_cal_date` (`date`),
  KEY `idx_cal_statut` (`statut`),
  CONSTRAINT `fk_cal_reservation` FOREIGN KEY (`reservation_id`) REFERENCES `reservations` (`id`) ON DELETE
  SET
    NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = 'Calendrier des disponibilités';

-- ============================================================
--  TABLE : demandes_informations
--  Formulaire de demande de renseignements rapide
-- ============================================================
CREATE TABLE `demandes_informations` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nom` VARCHAR(150) NOT NULL,
  `telephone` VARCHAR(20) NOT NULL,
  `email` VARCHAR(191) NULL,
  `type_evenement_id` INT UNSIGNED NULL,
  `date_evenement` DATE NULL,
  `nbr_invites` INT UNSIGNED NULL,
  `message` TEXT NULL,
  `statut` ENUM(
    'nouveau',
    'contacte',
    'transforme_devis',
    'ferme'
  ) NOT NULL DEFAULT 'nouveau',
  `ip_address` VARCHAR(45) NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_dem_statut` (`statut`),
  CONSTRAINT `fk_dem_type_evt` FOREIGN KEY (`type_evenement_id`) REFERENCES `types_evenements` (`id`) ON DELETE
  SET
    NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = 'Demandes de renseignements rapides (formulaire accueil)';

-- ============================================================
--  VUES UTILES
-- ============================================================
-- Vue : Réservations avec infos client et type d'événement
CREATE
OR REPLACE VIEW `v_reservations_detail` AS
SELECT
  r.id,
  r.reference,
  r.date_evenement,
  r.heure_debut,
  r.nbr_invites,
  r.statut,
  r.montant_total,
  r.montant_acompte,
  CONCAT(c.prenom, ' ', c.nom) AS client_nom_complet,
  c.email AS client_email,
  c.telephone AS client_telephone,
  te.nom AS type_evenement,
  p.nom AS package_nom,
  p.prix AS package_prix,
  s.nom AS salle_nom,
  r.created_at
FROM
  `reservations` r
  JOIN `clients` c ON r.client_id = c.id
  JOIN `types_evenements` te ON r.type_evenement_id = te.id
  LEFT JOIN `packages` p ON r.package_id = p.id
  LEFT JOIN `salles` s ON r.salle_id = s.id
WHERE
  r.deleted_at IS NULL;

-- Vue : Statistiques du tableau de bord
CREATE
OR REPLACE VIEW `v_stats_dashboard` AS
SELECT
  (
    SELECT
      COUNT(*)
    FROM
      `reservations`
    WHERE
      statut NOT IN ('annulee')
      AND deleted_at IS NULL
  ) AS total_reservations,
  (
    SELECT
      COUNT(*)
    FROM
      `reservations`
    WHERE
      statut = 'en_attente'
      AND deleted_at IS NULL
  ) AS reservations_en_attente,
  (
    SELECT
      COUNT(*)
    FROM
      `reservations`
    WHERE
      statut = 'confirmee'
      AND deleted_at IS NULL
  ) AS reservations_confirmees,
  (
    SELECT
      COUNT(*)
    FROM
      `devis`
    WHERE
      statut IN ('recu', 'en_traitement')
  ) AS devis_en_attente,
  (
    SELECT
      COUNT(*)
    FROM
      `clients`
    WHERE
      deleted_at IS NULL
  ) AS total_clients,
  (
    SELECT
      COUNT(*)
    FROM
      `contacts`
    WHERE
      statut = 'nouveau'
  ) AS messages_nouveaux,
  (
    SELECT
      COALESCE(SUM(montant_total), 0)
    FROM
      `reservations`
    WHERE
      statut IN ('confirmee', 'en_cours', 'terminee')
      AND deleted_at IS NULL
  ) AS ca_total,
  (
    SELECT
      COALESCE(SUM(montant_total), 0)
    FROM
      `reservations`
    WHERE
      statut IN ('confirmee', 'en_cours', 'terminee')
      AND MONTH(date_evenement) = MONTH(CURDATE())
      AND YEAR(date_evenement) = YEAR(CURDATE())
      AND deleted_at IS NULL
  ) AS ca_mois_courant,
  (
    SELECT
      COUNT(*)
    FROM
      `reservations`
    WHERE
      date_evenement = CURDATE()
      AND statut NOT IN ('annulee')
      AND deleted_at IS NULL
  ) AS evenements_aujourd_hui;

-- ============================================================
--  RÉACTIVATION DES CLÉS ÉTRANGÈRES
-- ============================================================
SET
  FOREIGN_KEY_CHECKS = 1;

-- ============================================================
--  RÉSUMÉ DES TABLES CRÉÉES
-- ============================================================
-- 1.  roles                      — Rôles utilisateurs
-- 2.  users                      — Comptes utilisateurs (admin + client)
-- 3.  clients                    — Fiches clients détaillées
-- 4.  types_evenements           — Catégories d'événements
-- 5.  services                   — Services proposés
-- 6.  packages                   — Formules tarifaires
-- 7.  packages_services          — Pivot packages ↔ services
-- 8.  salles                     — Salles et espaces disponibles
-- 9.  reservations               — Réservations confirmées
-- 10. reservation_services       — Pivot réservations ↔ services
-- 11. devis                      — Demandes et documents de devis
-- 12. devis_lignes               — Lignes de détail devis
-- 13. factures                   — Factures
-- 14. paiements                  — Paiements reçus
-- 15. categories_galerie         — Catégories galerie
-- 16. galerie                    — Photos & vidéos
-- 17. categories_blog            — Catégories blog
-- 18. blog_articles              — Articles blog
-- 19. commentaires               — Commentaires articles
-- 20. temoignages                — Avis clients
-- 21. contacts                   — Messages formulaire contact
-- 22. notifications              — Notifications système
-- 23. parametres                 — Configuration site
-- 24. activity_logs              — Journal d'activité admin
-- 25. calendrier_disponibilites  — Calendrier réservations
-- 26. demandes_informations      — Formulaire renseignements rapide
--
-- VUES :
--  v_reservations_detail         — Réservations avec détails
--  v_stats_dashboard             — Statistiques tableau de bord
--
-- ============================================================
--  FIN DU SCRIPT — db_traiteur_elmoussaoui
--  Traiteur EL MOUSSAOUI | Errachidia, Maroc | 0626 986 533
-- ============================================================