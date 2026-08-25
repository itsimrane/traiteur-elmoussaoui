-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1
-- Généré le : mar. 25 août 2026 à 19:08
-- Version du serveur : 10.4.32-MariaDB
-- Version de PHP : 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `db_traiteur_elmoussaoui`
--

-- --------------------------------------------------------

--
-- Structure de la table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED DEFAULT NULL,
  `action` varchar(100) NOT NULL COMMENT 'Ex: create, update, delete, login',
  `module` varchar(100) DEFAULT NULL COMMENT 'Ex: reservations, devis, clients',
  `description` text DEFAULT NULL,
  `entite_type` varchar(100) DEFAULT NULL,
  `entite_id` int(10) UNSIGNED DEFAULT NULL,
  `donnees_avant` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Données avant modification' CHECK (json_valid(`donnees_avant`)),
  `donnees_apres` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Données après modification' CHECK (json_valid(`donnees_apres`)),
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(500) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Journal d''activité administrateur';

-- --------------------------------------------------------

--
-- Structure de la table `blog_articles`
--

CREATE TABLE `blog_articles` (
  `id` int(10) UNSIGNED NOT NULL,
  `categorie_id` int(10) UNSIGNED NOT NULL,
  `auteur_id` int(10) UNSIGNED DEFAULT NULL,
  `titre` varchar(255) NOT NULL,
  `titre_ar` varchar(255) DEFAULT NULL,
  `slug` varchar(280) NOT NULL,
  `extrait` text DEFAULT NULL,
  `contenu` longtext NOT NULL,
  `image_principale` varchar(255) DEFAULT NULL,
  `tags` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`tags`)),
  `statut` enum('brouillon','publie','archive') NOT NULL DEFAULT 'brouillon',
  `date_publication` timestamp NULL DEFAULT NULL,
  `vues` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `meta_titre` varchar(160) DEFAULT NULL,
  `meta_description` varchar(320) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Articles du blog';

-- --------------------------------------------------------

--
-- Structure de la table `calendrier_disponibilites`
--

CREATE TABLE `calendrier_disponibilites` (
  `id` int(10) UNSIGNED NOT NULL,
  `date` date NOT NULL,
  `statut` enum('disponible','occupe','bloque','maintenance') NOT NULL DEFAULT 'disponible',
  `reservation_id` int(10) UNSIGNED DEFAULT NULL,
  `note` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Calendrier des disponibilités';

-- --------------------------------------------------------

--
-- Structure de la table `categories_blog`
--

CREATE TABLE `categories_blog` (
  `id` int(10) UNSIGNED NOT NULL,
  `nom` varchar(100) NOT NULL,
  `nom_ar` varchar(100) DEFAULT NULL,
  `slug` varchar(120) NOT NULL,
  `ordre` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `actif` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `categories_blog`
--

INSERT INTO `categories_blog` (`id`, `nom`, `nom_ar`, `slug`, `ordre`, `actif`) VALUES
(1, 'Conseils Mariage', 'نصائح الزواج', 'conseils-mariage', 1, 1),
(2, 'Tendances Décoration', 'ترندات الديكور', 'tendances-decoration', 2, 1),
(3, 'Reportages', 'ريبورتاجات', 'reportages', 3, 1),
(4, 'Recettes & Buffets', 'وصفات وبوفيه', 'recettes-buffets', 4, 1),
(5, 'Actualités', 'أخبار', 'actualites', 5, 1);

-- --------------------------------------------------------

--
-- Structure de la table `categories_galerie`
--

CREATE TABLE `categories_galerie` (
  `id` int(10) UNSIGNED NOT NULL,
  `nom` varchar(100) NOT NULL,
  `nom_ar` varchar(100) DEFAULT NULL,
  `slug` varchar(120) NOT NULL,
  `ordre` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `actif` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Catégories de la galerie';

--
-- Déchargement des données de la table `categories_galerie`
--

INSERT INTO `categories_galerie` (`id`, `nom`, `nom_ar`, `slug`, `ordre`, `actif`) VALUES
(1, 'Mariages', 'الأعراس', 'mariages', 1, 1),
(2, 'Fiançailles', 'الخطوبة', 'fiancailles', 2, 1),
(3, 'Décoration', 'الديكور', 'decoration', 3, 1),
(4, 'Buffets', 'البوفيه', 'buffets', 4, 1),
(5, 'Anniversaires', 'أعياد الميلاد', 'anniversaires', 5, 1),
(6, 'Tentes & Salles', 'الخيام والقاعات', 'tentes-salles', 6, 1),
(7, 'Équipe', 'الفريق', 'equipe', 7, 1);

-- --------------------------------------------------------

--
-- Structure de la table `clients`
--

CREATE TABLE `clients` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED DEFAULT NULL COMMENT 'NULL si client sans compte',
  `civilite` enum('M.','Mme','Dr','Prof') NOT NULL DEFAULT 'M.',
  `nom` varchar(100) NOT NULL,
  `prenom` varchar(100) NOT NULL,
  `email` varchar(191) NOT NULL,
  `telephone` varchar(20) NOT NULL,
  `telephone2` varchar(20) DEFAULT NULL,
  `adresse` varchar(255) DEFAULT NULL,
  `ville` varchar(100) DEFAULT 'Errachidia',
  `code_postal` varchar(10) DEFAULT NULL,
  `pays` varchar(50) NOT NULL DEFAULT 'Maroc',
  `cin` varchar(20) DEFAULT NULL COMMENT 'Carte nationale identité',
  `notes_internes` text DEFAULT NULL,
  `source` enum('site_web','telephone','reference','facebook','instagram','autre') DEFAULT 'site_web',
  `actif` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Fiches clients de Traiteur EL MOUSSAOUI';

--
-- Déchargement des données de la table `clients`
--

INSERT INTO `clients` (`id`, `user_id`, `civilite`, `nom`, `prenom`, `email`, `telephone`, `telephone2`, `adresse`, `ville`, `code_postal`, `pays`, `cin`, `notes_internes`, `source`, `actif`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, NULL, 'M.', 'NADIM', 'Imrane', 'imrane10nadim@gmail.com', '0697374762', NULL, NULL, 'Alnif', NULL, 'Maroc', NULL, NULL, 'site_web', 1, '2026-08-16 17:46:52', '2026-08-16 17:46:52', NULL),
(2, NULL, 'M.', 'hhhh', 'jjj', '', '06777777', NULL, NULL, 'Errachidia', NULL, 'Maroc', NULL, NULL, 'site_web', 1, '2026-08-17 12:19:55', '2026-08-17 12:19:55', NULL);

-- --------------------------------------------------------

--
-- Structure de la table `commentaires`
--

CREATE TABLE `commentaires` (
  `id` int(10) UNSIGNED NOT NULL,
  `article_id` int(10) UNSIGNED NOT NULL,
  `parent_id` int(10) UNSIGNED DEFAULT NULL COMMENT 'Pour les réponses',
  `nom` varchar(100) NOT NULL,
  `email` varchar(191) NOT NULL,
  `contenu` text NOT NULL,
  `statut` enum('en_attente','approuve','spam') NOT NULL DEFAULT 'en_attente',
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `contacts`
--

CREATE TABLE `contacts` (
  `id` int(10) UNSIGNED NOT NULL,
  `nom` varchar(100) NOT NULL,
  `prenom` varchar(100) DEFAULT NULL,
  `email` varchar(191) NOT NULL,
  `telephone` varchar(20) DEFAULT NULL,
  `sujet` varchar(200) DEFAULT NULL,
  `message` text NOT NULL,
  `statut` enum('nouveau','lu','traite','archive') NOT NULL DEFAULT 'nouveau',
  `ip_address` varchar(45) DEFAULT NULL,
  `lu_le` timestamp NULL DEFAULT NULL,
  `repondu_le` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Messages du formulaire de contact';

-- --------------------------------------------------------

--
-- Structure de la table `demandes_informations`
--

CREATE TABLE `demandes_informations` (
  `id` int(10) UNSIGNED NOT NULL,
  `nom` varchar(150) NOT NULL,
  `telephone` varchar(20) NOT NULL,
  `email` varchar(191) DEFAULT NULL,
  `type_evenement_id` int(10) UNSIGNED DEFAULT NULL,
  `date_evenement` date DEFAULT NULL,
  `nbr_invites` int(10) UNSIGNED DEFAULT NULL,
  `message` text DEFAULT NULL,
  `statut` enum('nouveau','contacte','transforme_devis','ferme') NOT NULL DEFAULT 'nouveau',
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Demandes de renseignements rapides (formulaire accueil)';

-- --------------------------------------------------------

--
-- Structure de la table `devis`
--

CREATE TABLE `devis` (
  `id` int(10) UNSIGNED NOT NULL,
  `reference` varchar(30) NOT NULL COMMENT 'Ex: DEV-2025-0001',
  `client_id` int(10) UNSIGNED DEFAULT NULL COMMENT 'NULL si prospect sans fiche',
  `nom_prospect` varchar(200) DEFAULT NULL COMMENT 'Si pas encore client',
  `email_prospect` varchar(191) DEFAULT NULL,
  `telephone_prospect` varchar(20) DEFAULT NULL,
  `type_evenement_id` int(10) UNSIGNED DEFAULT NULL,
  `date_evenement` date DEFAULT NULL,
  `nbr_invites` int(10) UNSIGNED DEFAULT NULL,
  `lieu` varchar(255) DEFAULT NULL,
  `message` text DEFAULT NULL COMMENT 'Demande initiale du client',
  `notes_internes` text DEFAULT NULL,
  `statut` enum('recu','en_traitement','envoye','accepte','refuse','expire') NOT NULL DEFAULT 'recu',
  `date_expiration` date DEFAULT NULL COMMENT 'Validité du devis',
  `montant_ht` decimal(12,2) NOT NULL DEFAULT 0.00,
  `tva_pct` decimal(5,2) NOT NULL DEFAULT 20.00 COMMENT 'TVA en %',
  `montant_tva` decimal(12,2) GENERATED ALWAYS AS (`montant_ht` * `tva_pct` / 100) STORED,
  `montant_ttc` decimal(12,2) GENERATED ALWAYS AS (`montant_ht` + `montant_ht` * `tva_pct` / 100) STORED,
  `reservation_id` int(10) UNSIGNED DEFAULT NULL COMMENT 'Si converti en réservation',
  `user_id_traitant` int(10) UNSIGNED DEFAULT NULL COMMENT 'Admin qui traite ce devis',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Demandes et documents de devis';

-- --------------------------------------------------------

--
-- Structure de la table `devis_generes`
--

CREATE TABLE `devis_generes` (
  `id` int(10) UNSIGNED NOT NULL,
  `numero` varchar(30) NOT NULL,
  `nom_client` varchar(200) NOT NULL,
  `prenom` varchar(100) DEFAULT NULL,
  `nom` varchar(100) DEFAULT NULL,
  `telephone` varchar(20) DEFAULT NULL,
  `email` varchar(191) DEFAULT NULL,
  `type_evenement` varchar(100) DEFAULT NULL,
  `date_evenement` date DEFAULT NULL,
  `ville` varchar(100) DEFAULT NULL,
  `nb_personnes` int(11) DEFAULT NULL,
  `services_json` text DEFAULT NULL COMMENT 'JSON: [{id, nom, prix}]',
  `montant_total` decimal(10,2) NOT NULL DEFAULT 0.00,
  `notes` text DEFAULT NULL,
  `statut` enum('nouveau','en_cours','accepte','refuse') DEFAULT 'nouveau',
  `pdf_path` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `devis_generes`
--

INSERT INTO `devis_generes` (`id`, `numero`, `nom_client`, `prenom`, `nom`, `telephone`, `email`, `type_evenement`, `date_evenement`, `ville`, `nb_personnes`, `services_json`, `montant_total`, `notes`, `statut`, `pdf_path`, `created_at`, `updated_at`) VALUES
(1, 'DEV-2026-0001', 'Imrane NADIM', 'Imrane', 'NADIM', '0697374762', 'imrane10nadim@gmail.com', 'mariage', '2026-07-28', 'Errachidia', 150, '[{\"id\":11,\"nom\":\"Restauration & Traiteur\",\"prix\":3500,\"tier\":\"or\"}]', 3500.00, 'mrc', 'nouveau', NULL, '2026-07-20 23:20:27', '2026-08-21 11:28:24'),
(2, 'DEV-2026-0002', 'Imrane NADIM', 'Imrane', 'NADIM', '0697374762', 'imrane10nadim@gmail.com', 'fiancailles', '2026-09-02', 'Errachidia', 60, '[{\"id\":15,\"nom\":\"Gâteau de Cérémonie\",\"nomAr\":\"كعكة الاحتفال\",\"prix\":800,\"tier\":\"bronze\"},{\"id\":16,\"nom\":\"Tables & Chaises\",\"nomAr\":\"الطاولات والكراسي\",\"prix\":600,\"tier\":\"bronze\"},{\"id\":12,\"nom\":\"Décoration & Scénographie\",\"nomAr\":\"الزينة والديكور\",\"prix\":1500,\"tier\":\"argent\"},{\"id\":4,\"nom\":\"Animation & Musique\",\"nomAr\":\"الموسيقى والترفيه\",\"prix\":1000,\"tier\":\"argent\"},{\"id\":5,\"nom\":\"Photographe & Vidéaste\",\"nomAr\":\"التصوير الفوتوغرافي والفيديو\",\"prix\":2000,\"tier\":\"argent\"}]', 5900.00, 'i hope the best for you', 'refuse', NULL, '2026-07-31 11:25:41', '2026-08-21 11:28:24'),
(3, 'DEV-2026-0003', 'Imrane NADIM', 'Imrane', 'NADIM', '0697374762', 'imrane10nadim@gmail.com', 'buffet', '2026-08-28', 'Merzouga', 100, '[{\"id\":21,\"nom\":\"Café & Thé Service\",\"nomAr\":\"خدمة القهوة والشاي\",\"prix\":400,\"tier\":\"bronze\"}]', 400.00, 'ffff', '', NULL, '2026-08-03 14:53:54', '2026-08-21 11:28:24'),
(4, 'DEV-2026-0004', 'Imrane NADIM', 'Imrane', 'NADIM', '0697374762', 'imrane10nadim@gmail.com', 'mariage', '2026-08-19', 'Merzouga', 150, '[{\"id\":17,\"nom\":\"Vaisselle & Couverts\",\"nomAr\":\"الأواني وأدوات المائدة\",\"prix\":500,\"tier\":\"bronze\"},{\"id\":16,\"nom\":\"Tables & Chaises\",\"nomAr\":\"الطاولات والكراسي\",\"prix\":600,\"tier\":\"bronze\"},{\"id\":12,\"nom\":\"Décoration & Scénographie\",\"nomAr\":\"الزينة والديكور\",\"prix\":1500,\"tier\":\"argent\"},{\"id\":15,\"nom\":\"Gâteau de Cérémonie\",\"nomAr\":\"كعكة الاحتفال\",\"prix\":800,\"tier\":\"bronze\"},{\"id\":14,\"nom\":\"Animation Musicale\",\"nomAr\":\"الموسيقى والترفيه\",\"prix\":1500,\"tier\":\"argent\"},{\"id\":18,\"nom\":\"Hôtesse & Personnel de Service\",\"nomAr\":\"الصوت والإضاءة\",\"prix\":1200,\"tier\":\"argent\"},{\"id\":11,\"nom\":\"Restauration & Traiteur\",\"nomAr\":\"التموين والمطعم\",\"prix\":3500,\"tier\":\"or\"}]', 9600.00, 'mariage', '', NULL, '2026-08-11 11:27:07', '2026-08-21 11:28:24'),
(5, 'DEV-2026-0005', 'Imrane NADIM', 'Imrane', 'NADIM', '0697374762', 'imrane10nadim@gmail.com', 'religieux', '2026-09-03', 'Merzouga', 445, '[{\"id\":11,\"nom\":\"Restauration & Traiteur\",\"prix\":3500,\"tier\":\"or\"}]', 3500.00, '..', '', NULL, '2026-08-11 11:59:19', '2026-08-21 11:28:24'),
(6, 'DEV-2026-0006', 'Imrane NADIM', 'Imrane', 'NADIM', '0697374762', 'imrane10nadim@gmail.com', 'religieux', '2026-09-03', 'Merzouga', 445, '[{\"id\":11,\"nom\":\"Restauration & Traiteur\",\"prix\":3500,\"tier\":\"or\"}]', 3500.00, '..', 'refuse', NULL, '2026-08-11 11:59:28', '2026-08-21 11:28:24'),
(7, 'DEV-2026-0007', 'Imrane NADIM', 'Imrane', 'NADIM', '0697374762', 'imrane10nadim@gmail.com', 'circoncision', '2026-08-24', 'Alnif', 121, '[{\"id\":17,\"nom\":\"Vaisselle & Couverts\",\"prix\":500,\"tier\":\"bronze\"},{\"id\":16,\"nom\":\"Tables & Chaises\",\"prix\":600,\"tier\":\"bronze\"},{\"id\":15,\"nom\":\"Gâteau de Cérémonie\",\"prix\":800,\"tier\":\"bronze\"},{\"id\":21,\"nom\":\"Café & Thé Service\",\"prix\":400,\"tier\":\"bronze\"},{\"id\":2,\"nom\":\"Décoration & Scénographie\",\"prix\":1500,\"tier\":\"argent\"},{\"id\":6,\"nom\":\"Service & Personnel\",\"prix\":1000,\"tier\":\"argent\"},{\"id\":11,\"nom\":\"Restauration & Traiteur\",\"prix\":3500,\"tier\":\"or\"},{\"id\":18,\"nom\":\"Hôtesse & Personnel de Service\",\"prix\":1200,\"tier\":\"argent\"}]', 9500.00, 'vrai', '', NULL, '2026-08-16 17:46:52', '2026-08-21 11:28:24'),
(8, 'DEV-2026-0008', 'Imrane NADIM', 'Imrane', 'NADIM', '0697374762', 'imrane10nadim@gmail.com', 'circoncision', '2026-08-24', 'Alnif', 121, '[{\"id\":17,\"nom\":\"Vaisselle & Couverts\",\"prix\":500,\"tier\":\"bronze\"},{\"id\":16,\"nom\":\"Tables & Chaises\",\"prix\":600,\"tier\":\"bronze\"},{\"id\":15,\"nom\":\"Gâteau de Cérémonie\",\"prix\":800,\"tier\":\"bronze\"},{\"id\":21,\"nom\":\"Café & Thé Service\",\"prix\":400,\"tier\":\"bronze\"},{\"id\":2,\"nom\":\"Décoration & Scénographie\",\"prix\":1500,\"tier\":\"argent\"},{\"id\":6,\"nom\":\"Service & Personnel\",\"prix\":1000,\"tier\":\"argent\"},{\"id\":11,\"nom\":\"Restauration & Traiteur\",\"prix\":3500,\"tier\":\"or\"},{\"id\":18,\"nom\":\"Hôtesse & Personnel de Service\",\"prix\":1200,\"tier\":\"argent\"}]', 9500.00, 'vrai', '', NULL, '2026-08-16 17:47:00', '2026-08-21 11:28:24'),
(9, 'DEV-2026-0009', 'jjj hhhh', 'jjj', 'hhhh', '06777777', '', 'religieux', '2026-08-27', 'Errachidia', 14, '[{\"id\":13,\"nom\":\"Tente & Structure\",\"prix\":2000,\"tier\":\"or\"},{\"id\":1,\"nom\":\"Restauration & Traiteur\",\"prix\":3500,\"tier\":\"or\"},{\"id\":12,\"nom\":\"Décoration & Scénographie\",\"prix\":1500,\"tier\":\"argent\"},{\"id\":11,\"nom\":\"Restauration & Traiteur\",\"prix\":3500,\"tier\":\"or\"},{\"id\":2,\"nom\":\"Décoration & Scénographie\",\"prix\":1500,\"tier\":\"argent\"},{\"id\":21,\"nom\":\"Café & Thé Service\",\"prix\":400,\"tier\":\"bronze\"},{\"id\":20,\"nom\":\"Chapiteau Funéraire\",\"prix\":800,\"tier\":\"bronze\"}]', 13200.00, '', '', NULL, '2026-08-17 12:19:55', '2026-08-21 11:28:24'),
(12, 'DEV-2026-0010', 'KHALIL MOH', 'KHALIL', 'MOH', '06777777', '', 'religieux', '2026-08-26', 'Erfoud', 11, '[{\"id\":2,\"nom\":\"Décoration & Scénographie\",\"prix\":1500,\"tier\":\"argent\"}]', 1500.00, '', '', NULL, '2026-08-17 12:21:45', '2026-08-21 11:28:24'),
(13, 'DEV-2026-0013', 'KHALIL MOH', 'KHALIL', 'MOH', '06777777', '', 'religieux', '2026-08-26', 'Erfoud', 11, '[{\"id\":2,\"nom\":\"Décoration & Scénographie\",\"prix\":1500,\"tier\":\"argent\"}]', 1500.00, '', '', NULL, '2026-08-17 12:21:58', '2026-08-21 11:28:24');

-- --------------------------------------------------------

--
-- Structure de la table `devis_lignes`
--

CREATE TABLE `devis_lignes` (
  `id` int(10) UNSIGNED NOT NULL,
  `devis_id` int(10) UNSIGNED NOT NULL,
  `service_id` int(10) UNSIGNED DEFAULT NULL,
  `designation` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `quantite` decimal(8,2) NOT NULL DEFAULT 1.00,
  `unite` varchar(50) DEFAULT NULL,
  `prix_unitaire` decimal(10,2) NOT NULL DEFAULT 0.00,
  `remise_pct` decimal(5,2) NOT NULL DEFAULT 0.00,
  `montant_ht` decimal(12,2) GENERATED ALWAYS AS (`quantite` * `prix_unitaire` * (1 - `remise_pct` / 100)) STORED,
  `ordre` int(10) UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Lignes de détail des devis';

-- --------------------------------------------------------

--
-- Structure de la table `factures`
--

CREATE TABLE `factures` (
  `id` int(10) UNSIGNED NOT NULL,
  `reference` varchar(30) NOT NULL COMMENT 'Ex: FAC-2025-0001',
  `reservation_id` int(10) UNSIGNED NOT NULL,
  `client_id` int(10) UNSIGNED NOT NULL,
  `date_facture` date NOT NULL DEFAULT curdate(),
  `date_echeance` date DEFAULT NULL,
  `statut` enum('en_attente','partiellement_payee','payee','annulee') NOT NULL DEFAULT 'en_attente',
  `montant_ht` decimal(12,2) NOT NULL DEFAULT 0.00,
  `tva_pct` decimal(5,2) NOT NULL DEFAULT 20.00,
  `montant_tva` decimal(12,2) GENERATED ALWAYS AS (`montant_ht` * `tva_pct` / 100) STORED,
  `montant_ttc` decimal(12,2) GENERATED ALWAYS AS (`montant_ht` + `montant_ht` * `tva_pct` / 100) STORED,
  `montant_paye` decimal(12,2) NOT NULL DEFAULT 0.00,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Factures des prestations';

-- --------------------------------------------------------

--
-- Structure de la table `galerie`
--

CREATE TABLE `galerie` (
  `id` int(10) UNSIGNED NOT NULL,
  `categorie_id` int(10) UNSIGNED NOT NULL,
  `type` enum('photo','video') NOT NULL DEFAULT 'photo',
  `titre` varchar(200) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `fichier` varchar(255) DEFAULT NULL COMMENT 'Chemin fichier image',
  `url_video` varchar(500) DEFAULT NULL COMMENT 'URL YouTube/Vimeo si vidéo',
  `miniature` varchar(255) DEFAULT NULL COMMENT 'Chemin miniature',
  `alt_text` varchar(255) DEFAULT NULL COMMENT 'Texte alternatif SEO',
  `tags` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`tags`)),
  `ordre` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `vues` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `en_vedette` tinyint(1) NOT NULL DEFAULT 0,
  `actif` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Photos et vidéos des événements réalisés';

--
-- Déchargement des données de la table `galerie`
--

INSERT INTO `galerie` (`id`, `categorie_id`, `type`, `titre`, `description`, `fichier`, `url_video`, `miniature`, `alt_text`, `tags`, `ordre`, `vues`, `en_vedette`, `actif`, `created_at`, `updated_at`) VALUES
(16, 3, 'photo', 'tente', NULL, 'galerie/img_6a6f83bd837a0_1785693117.jpg', NULL, NULL, 'tente', NULL, 0, 0, 0, 1, '2026-08-02 17:51:57', '2026-08-02 17:51:57'),
(19, 3, 'photo', 'tente', NULL, 'galerie/img_6a6f8439b911b_1785693241.jpg', NULL, NULL, 'tente', NULL, 0, 0, 0, 1, '2026-08-02 17:54:01', '2026-08-02 17:54:01'),
(20, 3, 'photo', 'tente', NULL, 'galerie/img_6a6f84604ae38_1785693280.jpg', NULL, NULL, 'tente', NULL, 0, 0, 0, 1, '2026-08-02 17:54:40', '2026-08-02 17:54:40'),
(23, 3, 'photo', 'tente', NULL, 'galerie/img_6a6f84a884dbb_1785693352.jpg', NULL, NULL, 'tente', NULL, 0, 0, 0, 1, '2026-08-02 17:55:52', '2026-08-02 17:55:52'),
(24, 3, 'photo', 'tente', NULL, 'galerie/img_6a6f84e3e9a32_1785693411.jpg', NULL, NULL, 'tente', NULL, 0, 0, 0, 1, '2026-08-02 17:56:51', '2026-08-02 17:56:51'),
(25, 3, 'photo', 'tente', NULL, 'galerie/img_6a6f8512be6df_1785693458.jpg', NULL, NULL, 'tente', NULL, 0, 0, 0, 1, '2026-08-02 17:57:38', '2026-08-02 17:57:38'),
(27, 3, 'photo', 'tente', NULL, 'galerie/img_6a6f8544e994c_1785693508.jpg', NULL, NULL, 'tente', NULL, 0, 0, 0, 1, '2026-08-02 17:58:28', '2026-08-02 17:58:28'),
(29, 3, 'photo', 'tente', NULL, 'galerie/img_6a6f85dbf0e6d_1785693659.jpg', NULL, NULL, 'tente', NULL, 0, 0, 0, 1, '2026-08-02 18:00:59', '2026-08-02 18:00:59'),
(30, 3, 'photo', 'tente', NULL, 'galerie/img_6a6f85f2ebcc8_1785693682.jpg', NULL, NULL, 'tente', NULL, 0, 0, 0, 1, '2026-08-02 18:01:22', '2026-08-02 18:01:22'),
(31, 3, 'photo', 'tente', NULL, 'galerie/img_6a6f865bd7a73_1785693787.jpg', NULL, NULL, 'tente', NULL, 0, 0, 0, 1, '2026-08-02 18:03:07', '2026-08-02 18:03:07'),
(32, 1, 'photo', 'Tente Réception', NULL, 'accueil/accueil_6a6f86f2a896c_1785693938.jpg', NULL, NULL, 'Tente Réception', NULL, 0, 0, 1, 1, '2026-08-02 18:05:38', '2026-08-02 18:05:38');

-- --------------------------------------------------------

--
-- Structure de la table `notifications`
--

CREATE TABLE `notifications` (
  `id` char(36) NOT NULL COMMENT 'UUID',
  `type` varchar(100) NOT NULL COMMENT 'Classe de notification',
  `notifiable_type` varchar(100) NOT NULL,
  `notifiable_id` int(10) UNSIGNED NOT NULL,
  `data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`data`)),
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Notifications système';

-- --------------------------------------------------------

--
-- Structure de la table `packages`
--

CREATE TABLE `packages` (
  `id` int(10) UNSIGNED NOT NULL,
  `nom` varchar(100) NOT NULL,
  `nom_ar` varchar(100) DEFAULT NULL,
  `slug` varchar(120) NOT NULL,
  `description` text DEFAULT NULL,
  `description_ar` text DEFAULT NULL,
  `couleur_badge` varchar(7) DEFAULT NULL COMMENT 'Couleur hex du badge',
  `prix` decimal(10,2) NOT NULL COMMENT 'Prix total du package (MAD)',
  `min_personnes` int(10) UNSIGNED DEFAULT NULL,
  `max_personnes` int(10) UNSIGNED DEFAULT NULL,
  `duree_heures` decimal(4,1) DEFAULT NULL COMMENT 'Durée incluse en heures',
  `contenu` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Liste des éléments inclus' CHECK (json_valid(`contenu`)),
  `contenu_ar` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`contenu_ar`)),
  `mis_en_avant` tinyint(1) NOT NULL DEFAULT 0 COMMENT '1 = recommandé (badge)',
  `ordre` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `actif` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Packages tarifaires proposés';

--
-- Déchargement des données de la table `packages`
--

INSERT INTO `packages` (`id`, `nom`, `nom_ar`, `slug`, `description`, `description_ar`, `couleur_badge`, `prix`, `min_personnes`, `max_personnes`, `duree_heures`, `contenu`, `contenu_ar`, `mis_en_avant`, `ordre`, `actif`, `created_at`, `updated_at`) VALUES
(1, 'Formule Bronze', 'الباقة البرونزية', 'bronze', 'L&#039;essentiel pour une belle fête', 'صيغة أساسية للحفلات العائلية الصغيرة', '#CD7F32', 6500.00, 80, 100, 5.0, '[\"Restauration de base\",\"Thé & pâtisseries marocaines\",\"Décoration simple\",\"1 serveur professionnel\",\"Tables & chaises incluses\"]', '[\"تموين أساسي\",\"شاي ومعجنات مغربية\",\"زينة بسيطة\",\"نادل محترف واحد\",\"طاولات وكراسي شاملة\"]', 0, 1, 1, '2026-06-10 18:05:38', '2026-07-26 22:47:04'),
(2, 'Formule Argent', 'الباقة الفضية', 'argent', 'Confort et élégance réunis', 'صيغة متوسطة لمناسبات ناجحة', '#C0C0C0', 10000.00, 80, 150, 7.0, '[\"Restauration complète\",\"Buffet marocain & international\",\"Décoration florale\",\"3 serveurs professionnels\",\"Gâteau d anniversaire inclus\",\"Sonorisation de base\"]', '[\"تموين متكامل\",\"بوفيه مغربي ودولي\",\"زينة زهرية\",\"3 نادلين محترفين\",\"كعكة مجانية\",\"تجهيز صوتي أساسي\"]', 0, 2, 1, '2026-06-10 18:05:38', '2026-07-26 22:47:04'),
(3, 'Formule Or', 'الباقة الذهبية', 'or', 'L\'excellence à votre service', 'صيغتنا الأكثر شعبية لحفلات الزفاف والمناسبات الكبرى', '#D4AF37', 18000.00, 120, 250, 9.0, '[\"Repas gastronomique premium\",\"Buffet complet 20 plats\",\"Décoration florale premium\",\"5 serveurs en tenue\",\"Gâteau personnalisé 3 étages\",\"DJ + équipement son/lumière\",\"Photographe professionnel\"]', '[\"وجبة غاسترونومية فاخرة\",\"بوفيه كامل 20 طبق\",\"زينة زهرية فاخرة\",\"5 نادلين بزي رسمي\",\"كعكة مخصصة 3 طوابق\",\"DJ + صوت وإضاءة\",\"مصور فوتوغرافي محترف\"]', 1, 3, 1, '2026-06-10 18:05:38', '2026-07-26 22:47:04'),
(4, 'Formule Platine', 'الباقة البلاتينية', 'platine', 'Le tout inclus, zéro souci', 'التجربة الكاملة الشاملة للمناسبات الاستثنائية', '#E8E8FF', 30000.00, 200, 500, 12.0, '[\"Tout en Formule Or\",\"Tente de réception 500 places\",\"Limousine décorée + cortège\",\"Photographe + Vidéaste HD\",\"Animateur professionnel live\",\"Invitations imprimées (500)\",\"Habillement & maquillage\",\"Coordinateur dédié J-1\",\"Gâteau 5 étages sur mesure\"]', '[\"كل شيء في صيغة الذهب\",\"خيمة استقبال 500 شخص\",\"ليموزين مزينة + موكب\",\"مصور + مصور فيديو HD\",\"منشط محترف\",\"دعوات مطبوعة (500)\",\"هندام ومكياج\",\"منسق مخصص يوم الحفل\",\"كعكة 5 طوابق مخصصة\"]', 0, 4, 1, '2026-06-10 18:05:38', '2026-07-26 22:47:04');

-- --------------------------------------------------------

--
-- Structure de la table `packages_services`
--

CREATE TABLE `packages_services` (
  `package_id` int(10) UNSIGNED NOT NULL,
  `service_id` int(10) UNSIGNED NOT NULL,
  `quantite` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `note` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Services inclus dans chaque package';

-- --------------------------------------------------------

--
-- Structure de la table `paiements`
--

CREATE TABLE `paiements` (
  `id` int(10) UNSIGNED NOT NULL,
  `facture_id` int(10) UNSIGNED NOT NULL,
  `montant` decimal(12,2) NOT NULL,
  `mode` enum('especes','virement','cheque','cmi','wave','whatsapp_pay','autre') NOT NULL DEFAULT 'especes',
  `reference_pmt` varchar(100) DEFAULT NULL COMMENT 'Numéro de virement, chèque, etc.',
  `date_paiement` date NOT NULL DEFAULT curdate(),
  `recu_par` int(10) UNSIGNED DEFAULT NULL COMMENT 'FK user admin',
  `note` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Paiements reçus';

-- --------------------------------------------------------

--
-- Structure de la table `parametres`
--

CREATE TABLE `parametres` (
  `id` int(10) UNSIGNED NOT NULL,
  `cle` varchar(100) NOT NULL COMMENT 'Clé de configuration',
  `valeur` text DEFAULT NULL,
  `groupe` varchar(50) NOT NULL DEFAULT 'general',
  `label` varchar(150) DEFAULT NULL,
  `type` enum('text','textarea','number','boolean','json','color','email','url','password') DEFAULT 'text',
  `ordre` int(10) UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Paramètres de configuration du site';

--
-- Déchargement des données de la table `parametres`
--

INSERT INTO `parametres` (`id`, `cle`, `valeur`, `groupe`, `label`, `type`, `ordre`) VALUES
(1, 'site_nom', 'Traiteur EL MOUSSAOUI', 'general', 'Nom du site', 'text', 1),
(2, 'site_slogan', 'Organisation des Évènements et des Fêtes', 'general', 'Slogan', 'text', 2),
(3, 'site_slogan_ar', 'تنظيم وتجهيز جميع المناسبات والحفلات', 'general', 'Slogan (Arabe)', 'text', 3),
(4, 'site_description', 'Votre traiteur de confiance à Errachidia pour tous vos événements et fêtes.', 'general', 'Description courte', 'textarea', 4),
(5, 'site_couleur_primaire', '#D4AF37', 'general', 'Couleur principale', 'color', 5),
(6, 'site_logo', 'assets/img/logo.png', 'general', 'Logo principal', 'text', 6),
(7, 'contact_telephone', '0626986533', 'contact', 'Téléphone principal', 'text', 1),
(8, 'contact_whatsapp', '+212626986533', 'contact', 'Numéro WhatsApp', 'text', 2),
(9, 'contact_email', 'contact@traiteur-elmoussaoui.ma', 'contact', 'E-mail de contact', 'email', 3),
(10, 'contact_adresse', 'Errachidia, Région Drâa-Tafilalet, Maroc', 'contact', 'Adresse', 'textarea', 4),
(11, 'contact_maps_lat', '31.9314', 'contact', 'Latitude Google Maps', 'text', 5),
(12, 'contact_maps_lng', '-4.4264', 'contact', 'Longitude Google Maps', 'text', 6),
(13, 'horaires_ouverture', 'Lun–Sam : 08h–20h | Dim : 09h–18h', 'contact', 'Horaires d\'ouverture', 'text', 7),
(14, 'rs_facebook', 'https://facebook.com/traiteur.elmoussaoui', 'social', 'Facebook', 'url', 1),
(15, 'rs_instagram', 'https://instagram.com/elmoussaoui_traiteur', 'social', 'Instagram', 'url', 2),
(16, 'rs_tiktok', '', 'social', 'TikTok', 'url', 3),
(17, 'rs_youtube', '', 'social', 'YouTube', 'url', 4),
(18, 'seo_meta_titre', 'Traiteur EL MOUSSAOUI — Errachidia | Organisation Mariages & Fêtes', 'seo', 'Meta Titre', 'text', 1),
(19, 'seo_meta_description', 'Traiteur EL MOUSSAOUI, spécialiste organisation mariages, fiançailles et événements à Errachidia, Maroc. Contact : 0626 986 533', 'seo', 'Meta Description', 'textarea', 2),
(20, 'smtp_host', 'smtp.gmail.com', 'email', 'Serveur SMTP', 'text', 1),
(21, 'smtp_port', '587', 'email', 'Port SMTP', 'number', 2),
(22, 'smtp_encryption', 'tls', 'email', 'Chiffrement', 'text', 3),
(23, 'smtp_utilisateur', 'noreply@traiteur-elmoussaoui.ma', 'email', 'Utilisateur SMTP', 'email', 4),
(24, 'smtp_mot_de_passe', '', 'email', 'Mot de passe SMTP', 'password', 5),
(25, 'email_expediteur', 'noreply@traiteur-elmoussaoui.ma', 'email', 'E-mail expéditeur', 'email', 6),
(26, 'email_nom_expediteur', 'Traiteur EL MOUSSAOUI', 'email', 'Nom expéditeur', 'text', 7),
(27, 'devis_tva_defaut', '20', 'reservation', 'TVA par défaut (%)', 'number', 1),
(28, 'devis_validite_jours', '30', 'reservation', 'Validité devis (j)', 'number', 2),
(29, 'reservation_acompte_pct', '30', 'reservation', 'Acompte requis (%)', 'number', 3),
(30, 'maintenance_mode', '0', 'system', 'Mode maintenance', 'boolean', 1);

-- --------------------------------------------------------

--
-- Structure de la table `reservations`
--

CREATE TABLE `reservations` (
  `id` int(10) UNSIGNED NOT NULL,
  `reference` varchar(30) NOT NULL COMMENT 'Ex: RES-2025-0001',
  `client_id` int(10) UNSIGNED NOT NULL,
  `type_evenement_id` int(10) UNSIGNED NOT NULL,
  `package_id` int(10) UNSIGNED DEFAULT NULL,
  `salle_id` int(10) UNSIGNED DEFAULT NULL,
  `date_evenement` date NOT NULL,
  `heure_debut` time NOT NULL DEFAULT '18:00:00',
  `heure_fin` time DEFAULT NULL,
  `lieu` varchar(255) DEFAULT NULL COMMENT 'Si salle hors liste',
  `nbr_invites` int(10) UNSIGNED NOT NULL DEFAULT 100,
  `statut` enum('en_attente','confirmee','en_cours','terminee','annulee') NOT NULL DEFAULT 'en_attente',
  `motif_annulation` text DEFAULT NULL,
  `notes_client` text DEFAULT NULL,
  `notes_internes` text DEFAULT NULL,
  `montant_total` decimal(12,2) NOT NULL DEFAULT 0.00,
  `montant_acompte` decimal(12,2) NOT NULL DEFAULT 0.00,
  `acompte_paye` tinyint(1) NOT NULL DEFAULT 0,
  `solde_restant` decimal(12,2) GENERATED ALWAYS AS (`montant_total` - `montant_acompte`) VIRTUAL,
  `user_id_createur` int(10) UNSIGNED DEFAULT NULL COMMENT 'Admin ayant créé/saisi la réservation',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Réservations d''événements';

--
-- Déchargement des données de la table `reservations`
--

INSERT INTO `reservations` (`id`, `reference`, `client_id`, `type_evenement_id`, `package_id`, `salle_id`, `date_evenement`, `heure_debut`, `heure_fin`, `lieu`, `nbr_invites`, `statut`, `motif_annulation`, `notes_client`, `notes_internes`, `montant_total`, `montant_acompte`, `acompte_paye`, `user_id_createur`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 'RES-2026-0007', 1, 3, NULL, NULL, '2026-08-24', '18:00:00', NULL, 'Alnif', 121, 'en_attente', NULL, 'vrai', NULL, 0.00, 0.00, 0, NULL, '2026-08-16 17:46:52', '2026-08-16 17:46:52', NULL),
(2, 'RES-2026-0008', 1, 3, NULL, NULL, '2026-08-24', '18:00:00', NULL, 'Alnif', 121, 'en_attente', NULL, 'vrai', NULL, 0.00, 0.00, 0, NULL, '2026-08-16 17:47:00', '2026-08-16 17:47:00', NULL),
(3, 'RES-2026-0009', 2, 1, NULL, NULL, '2026-08-27', '18:00:00', NULL, 'Errachidia', 14, 'en_attente', NULL, '', NULL, 0.00, 0.00, 0, NULL, '2026-08-17 12:19:55', '2026-08-17 12:19:55', NULL),
(4, 'RES-2026-0012', 2, 1, NULL, NULL, '2026-08-26', '18:00:00', NULL, 'Erfoud', 11, 'en_attente', NULL, '', NULL, 0.00, 0.00, 0, NULL, '2026-08-17 12:21:45', '2026-08-17 12:21:45', NULL),
(5, 'RES-2026-0013', 2, 1, NULL, NULL, '2026-08-26', '18:00:00', NULL, 'Erfoud', 11, 'en_attente', NULL, '', NULL, 0.00, 0.00, 0, NULL, '2026-08-17 12:21:58', '2026-08-17 12:21:58', NULL);

-- --------------------------------------------------------

--
-- Structure de la table `reservation_services`
--

CREATE TABLE `reservation_services` (
  `id` int(10) UNSIGNED NOT NULL,
  `reservation_id` int(10) UNSIGNED NOT NULL,
  `service_id` int(10) UNSIGNED NOT NULL,
  `quantite` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `prix_unitaire` decimal(10,2) NOT NULL DEFAULT 0.00,
  `prix_total` decimal(10,2) GENERATED ALWAYS AS (`quantite` * `prix_unitaire`) STORED,
  `note` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Détail des services par réservation';

-- --------------------------------------------------------

--
-- Structure de la table `roles`
--

CREATE TABLE `roles` (
  `id` int(10) UNSIGNED NOT NULL,
  `nom` varchar(50) NOT NULL COMMENT 'Ex: super_admin, admin, gestionnaire, client',
  `label` varchar(100) NOT NULL COMMENT 'Libellé affiché',
  `permissions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Liste des permissions JSON' CHECK (json_valid(`permissions`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Rôles et permissions des utilisateurs';

--
-- Déchargement des données de la table `roles`
--

INSERT INTO `roles` (`id`, `nom`, `label`, `permissions`, `created_at`, `updated_at`) VALUES
(1, 'super_admin', 'Super Administrateur', '[\"all\"]', '2026-06-10 18:05:37', '2026-06-10 18:05:37'),
(2, 'admin', 'Administrateur', '[\"reservations\",\"devis\",\"clients\",\"galerie\",\"blog\",\"parametres\"]', '2026-06-10 18:05:37', '2026-06-10 18:05:37'),
(3, 'gestionnaire', 'Gestionnaire', '[\"reservations\",\"devis\",\"clients\"]', '2026-06-10 18:05:37', '2026-06-10 18:05:37'),
(4, 'client', 'Client', '[\"profile\",\"reservations_view\",\"devis_view\"]', '2026-06-10 18:05:37', '2026-06-10 18:05:37');

-- --------------------------------------------------------

--
-- Structure de la table `salles`
--

CREATE TABLE `salles` (
  `id` int(10) UNSIGNED NOT NULL,
  `nom` varchar(150) NOT NULL,
  `adresse` varchar(255) DEFAULT NULL,
  `ville` varchar(100) NOT NULL DEFAULT 'Errachidia',
  `capacite_min` int(10) UNSIGNED DEFAULT NULL,
  `capacite_max` int(10) UNSIGNED DEFAULT NULL,
  `description` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `telephone` varchar(20) DEFAULT NULL,
  `est_partenaire` tinyint(1) NOT NULL DEFAULT 0,
  `actif` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Salles et espaces disponibles pour les événements';

-- --------------------------------------------------------

--
-- Structure de la table `services`
--

CREATE TABLE `services` (
  `id` int(10) UNSIGNED NOT NULL,
  `nom` varchar(150) NOT NULL,
  `nom_ar` varchar(150) DEFAULT NULL,
  `slug` varchar(170) NOT NULL,
  `description` text DEFAULT NULL,
  `description_ar` text DEFAULT NULL,
  `prix_base` decimal(10,2) DEFAULT NULL COMMENT 'Prix de base unitaire (MAD)',
  `unite` varchar(50) DEFAULT NULL COMMENT 'Ex: par personne, forfait, par heure',
  `image` varchar(255) DEFAULT NULL,
  `icone` varchar(100) DEFAULT NULL,
  `ordre` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `actif` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `prix` decimal(10,2) DEFAULT NULL COMMENT 'Prix en MAD',
  `categorie_tarif` enum('bronze','argent','or') DEFAULT 'bronze',
  `types_evenements` text DEFAULT NULL COMMENT 'JSON: mariage,fiancailles,etc.'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Services proposés par Traiteur EL MOUSSAOUI';

--
-- Déchargement des données de la table `services`
--

INSERT INTO `services` (`id`, `nom`, `nom_ar`, `slug`, `description`, `description_ar`, `prix_base`, `unite`, `image`, `icone`, `ordre`, `actif`, `created_at`, `updated_at`, `prix`, `categorie_tarif`, `types_evenements`) VALUES
(1, 'Restauration & Traiteur', 'التموين والمطعم', 'restauration-traiteur', 'Préparation et service de repas marocains et internationaux', 'بوفيه متكامل، أطباق مغربية تقليدية وعصرية، حلويات، عصائر طازجة.', 80.00, 'par personne', NULL, 'fa-utensils', 1, 1, '2026-06-10 18:05:38', '2026-07-26 12:02:16', 3500.00, 'or', '[\"mariage\",\"fiancailles\",\"circoncision\",\"anniversaire\",\"reception_pro\",\"buffet\",\"religieux\"]'),
(2, 'Décoration & Scénographie', 'الزينة والديكور', 'decoration-sceno', 'Décoration florale, ballons, mise en scène, thèmes personnalisés', 'زينة زهرية، أقمشة، إضاءة LED وتزيين كامل للقاعة.', 2000.00, 'forfait', NULL, 'fa-leaf', 3, 1, '2026-06-10 18:05:38', '2026-07-26 16:58:12', 1500.00, 'argent', '[\"mariage\",\"fiancailles\",\"circoncision\",\"anniversaire\",\"reception_pro\",\"religieux\"]'),
(3, 'Tentes & Mobilier', 'الخيمة والهيكل', 'tentes-mobilier', 'Location de tentes de réception, tables, chaises, nappage', 'تأجير وتركيب خيام الاستقبال بجميع الأحجام والهياكل والمظلات.', 500.00, 'par unité', NULL, 'fa-campground', 5, 1, '2026-06-10 18:05:38', '2026-07-26 12:02:16', NULL, 'bronze', NULL),
(4, 'Animation & Musique', 'الموسيقى والترفيه', 'animation-musique', 'DJ, groupe musical traditionnel (gnaoua, chaabi), animateur', 'فرقة موسيقية، DJ، مغني أندلسي أو كناوي حسب تفضيلاتكم.', 3000.00, 'forfait', NULL, 'fa-music', 7, 1, '2026-06-10 18:05:38', '2026-07-26 16:58:12', 1000.00, 'argent', '[\"mariage\",\"fiancailles\",\"circoncision\",\"anniversaire\"]'),
(5, 'Photographe & Vidéaste', 'التصوير الفوتوغرافي والفيديو', 'photo-video', 'Reportage photo et vidéo professionnel de l\'événement', 'مصور ومصور فيديو محترفان، تغطية كاملة للحفل، طائرة مسيّرة متاحة.', 2500.00, 'forfait', NULL, 'fa-camera', 9, 1, '2026-06-10 18:05:38', '2026-07-26 12:02:16', 2000.00, 'argent', '[\"mariage\",\"fiancailles\",\"anniversaire\",\"reception_pro\"]'),
(6, 'Service & Personnel', 'الصوت والإضاءة', 'service-personnel', 'Serveurs, maître d\'hôtel, coordinateurs d\'événements', 'معدات صوت احترافية، إضاءة LED متطورة وتجهيزات المسرح الكاملة.', 200.00, 'par personne', NULL, 'fa-user-tie', 10, 1, '2026-06-10 18:05:38', '2026-07-26 16:58:12', 1000.00, 'argent', '[\"mariage\",\"fiancailles\",\"circoncision\",\"anniversaire\"]'),
(7, 'Gâteau de fête', 'كعكة الاحتفال', 'gateau-fete', 'Pièce montée et gâteaux personnalisés sur commande', 'كعكات مخصصة بعدة طوابق مزينة حسب ذوقكم وطلبكم.', 800.00, 'forfait', NULL, 'fa-birthday-cake', 12, 1, '2026-06-10 18:05:38', '2026-07-26 16:58:12', NULL, 'bronze', NULL),
(8, 'Habillement & Coiffure', 'تجميل وتزيين', 'habillement-coiffure', 'Services de maquillage, henné et coiffure pour la mariée', 'خدمات المكياج والحناء وتصفيف الشعر للعروس وضيفاتها.', 1500.00, 'forfait', NULL, 'fa-spa', 14, 1, '2026-06-10 18:05:38', '2026-07-26 16:58:12', NULL, 'bronze', NULL),
(9, 'Transport & Limousine', 'النقل والليموزين', 'transport-limousine', 'Voitures décorées pour le cortège et transport des invités', 'سيارات مزينة وليموزين فاخرة لنقل العروسين وضيوفهم.', 1200.00, 'par véhicule', NULL, 'fa-car', 16, 1, '2026-06-10 18:05:38', '2026-07-26 16:58:12', NULL, 'bronze', NULL),
(10, 'Invitations & Papeterie', 'بطاقات الدعوة', 'invitations', 'Conception et impression des cartons d\'invitation', 'تصميم وطباعة بطاقات دعوة فاخرة مخصصة حسب طلبكم.', 15.00, 'par unité', NULL, 'fa-envelope', 18, 1, '2026-06-10 18:05:38', '2026-07-26 16:58:12', NULL, 'bronze', NULL),
(11, 'Restauration & Traiteur', 'التموين والمطعم', 'restauration', 'Buffet complet, plats marocains traditionnels et modernes.', 'بوفيه متكامل، أطباق مغربية تقليدية وعصرية، حلويات، عصائر طازجة.', NULL, NULL, NULL, 'fa-utensils', 2, 1, '2026-07-20 23:13:25', '2026-07-26 12:02:16', 3500.00, 'or', '[\"mariage\",\"fiancailles\",\"circoncision\",\"anniversaire\",\"reception_pro\",\"buffet\",\"religieux\"]'),
(12, 'Décoration & Scénographie', 'الزينة والديكور', 'decoration', 'Décoration florale, tissus, éclairages et mise en scène de la salle.', 'زينة زهرية، أقمشة، إضاءة LED وتزيين كامل للقاعة.', NULL, NULL, NULL, 'fa-paint-brush', 4, 1, '2026-07-20 23:13:25', '2026-07-26 16:58:12', 1500.00, 'argent', '[\"mariage\",\"fiancailles\",\"circoncision\",\"anniversaire\",\"reception_pro\",\"religieux\"]'),
(13, 'Tente & Structure', 'الخيمة والهيكل', 'tente', 'Location et installation de tentes de réception toutes tailles.', 'تأجير وتركيب خيام الاستقبال بجميع الأحجام والهياكل والمظلات.', NULL, NULL, NULL, 'fa-tent', 6, 1, '2026-07-20 23:13:25', '2026-07-26 12:02:16', 2000.00, 'or', '[\"mariage\",\"fiancailles\",\"circoncision\",\"anniversaire\",\"reception_pro\",\"religieux\"]'),
(14, 'Animation Musicale', 'الموسيقى والترفيه', 'animation', 'Groupe musical, DJ, chanteur andalou ou gnaoua.', 'فرقة موسيقية، DJ، مغني أندلسي أو كناوي حسب تفضيلاتكم.', NULL, NULL, NULL, 'fa-music', 8, 1, '2026-07-20 23:13:25', '2026-07-26 16:58:12', 1500.00, 'argent', '[\"mariage\",\"fiancailles\",\"anniversaire\"]'),
(15, 'Gâteau de Cérémonie', 'كعكة الاحتفال', 'gateau', 'Pièces montées, gâteaux personnalisés et desserts assortis.', 'كعكات مخصصة بعدة طوابق مزينة حسب ذوقكم وطلبكم.', NULL, NULL, NULL, 'fa-birthday-cake', 11, 1, '2026-07-20 23:13:25', '2026-07-26 16:58:12', 800.00, 'bronze', '[\"mariage\",\"fiancailles\",\"circoncision\",\"anniversaire\"]'),
(16, 'Tables & Chaises', 'الطاولات والكراسي', 'mobilier', 'Location de tables rondes, chaises Napoléon, housses et nappes.', 'تأجير طاولات مستديرة وكراسي نابليون وأغطية وأغطية الطاولات.', NULL, NULL, NULL, 'fa-chair', 13, 1, '2026-07-20 23:13:25', '2026-07-26 16:58:12', 600.00, 'bronze', '[\"mariage\",\"fiancailles\",\"circoncision\",\"anniversaire\",\"reception_pro\",\"buffet\"]'),
(17, 'Vaisselle & Couverts', 'الأواني وأدوات المائدة', 'vaisselle', 'Service complet de vaisselle, verres et couverts pour tous les convives.', 'خدمة كاملة للأواني والكؤوس وأدوات المائدة لجميع الضيوف.', NULL, NULL, NULL, 'fa-concierge-bell', 15, 1, '2026-07-20 23:13:25', '2026-07-26 16:58:12', 500.00, 'bronze', '[\"mariage\",\"fiancailles\",\"circoncision\",\"anniversaire\",\"reception_pro\",\"buffet\"]'),
(18, 'Hôtesse & Personnel de Service', 'الصوت والإضاءة', 'personnel', 'Serveurs professionnels, hôtesses d\'accueil et maître de cérémonie.', 'معدات صوت احترافية، إضاءة LED متطورة وتجهيزات المسرح الكاملة.', NULL, NULL, NULL, 'fa-user-tie', 17, 1, '2026-07-20 23:13:25', '2026-07-26 16:58:12', 1200.00, 'argent', '[\"mariage\",\"fiancailles\",\"circoncision\",\"anniversaire\",\"reception_pro\"]'),
(19, 'Sonorisation & Éclairage', 'الصوت والإضاءة', 'sono', 'Système audio professionnel, éclairage de scène et effets lumineux.', 'معدات صوت احترافية، إضاءة LED متطورة وتجهيزات المسرح الكاملة.', NULL, NULL, NULL, 'fa-microphone', 19, 1, '2026-07-20 23:13:25', '2026-07-26 16:58:12', 1000.00, 'argent', '[\"mariage\",\"fiancailles\",\"anniversaire\",\"reception_pro\"]'),
(20, 'Chapiteau Funéraire', 'خيمة العزاء', 'chapiteau-funera', 'Tente traditionnelle pour cérémonies religieuses de condoléances.', 'خيمة تقليدية مخصصة لمراسم العزاء والتأبين.', NULL, NULL, NULL, 'fa-star-and-crescent', 20, 1, '2026-07-20 23:13:25', '2026-07-26 17:02:13', 800.00, 'bronze', '[\"religieux\"]'),
(21, 'Café & Thé Service', 'خدمة القهوة والشاي', 'cafe-the', 'Service de café marocain, thé à la menthe et jus de fruits.', 'خدمة القهوة والشاي المغربي والنعناع وعصائر الفواكه الطازجة.', NULL, NULL, NULL, 'fa-coffee', 21, 1, '2026-07-20 23:13:25', '2026-07-26 17:02:13', 400.00, 'bronze', '[\"religieux\",\"reception_pro\",\"buffet\",\"circoncision\"]'),
(22, 'Transport & Navette', 'النقل والليموزين', 'transport', 'Service de navette pour les invités et transport des équipements.', 'سيارات مزينة وليموزين فاخرة لنقل العروسين وضيوفهم.', NULL, NULL, NULL, 'fa-car', 22, 1, '2026-07-20 23:13:25', '2026-07-26 16:58:12', 1000.00, 'argent', '[\"mariage\",\"fiancailles\"]'),
(23, 'Coordinateur d\'Événement', 'تنسيق الحفل', 'coordination', 'Chef de projet dédié pour coordonner tous les prestataires le jour J.', 'مدير مشروع مخصص لتنسيق جميع مزودي الخدمات يوم مناسبتك.', NULL, NULL, NULL, 'fa-clipboard-list', 23, 1, '2026-07-20 23:13:25', '2026-07-26 12:02:17', 2500.00, 'or', '[\"mariage\",\"fiancailles\",\"anniversaire\",\"reception_pro\"]');

-- --------------------------------------------------------

--
-- Structure de la table `temoignages`
--

CREATE TABLE `temoignages` (
  `id` int(10) UNSIGNED NOT NULL,
  `client_id` int(10) UNSIGNED DEFAULT NULL,
  `reservation_id` int(10) UNSIGNED DEFAULT NULL,
  `nom_client` varchar(150) NOT NULL,
  `ville` varchar(100) DEFAULT NULL,
  `contenu` text NOT NULL,
  `note` tinyint(3) UNSIGNED NOT NULL DEFAULT 5 COMMENT 'Note /5',
  `type_evenement` varchar(100) DEFAULT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `statut` enum('en_attente','publie','refuse') NOT NULL DEFAULT 'en_attente',
  `en_vedette` tinyint(1) NOT NULL DEFAULT 0,
  `ordre` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Témoignages et avis des clients';

--
-- Déchargement des données de la table `temoignages`
--

INSERT INTO `temoignages` (`id`, `client_id`, `reservation_id`, `nom_client`, `ville`, `contenu`, `note`, `type_evenement`, `photo`, `statut`, `en_vedette`, `ordre`, `created_at`) VALUES
(1, NULL, NULL, 'Fatima Zahra B.', 'Errachidia', 'Un service exceptionnel pour notre mariage ! L\'équipe d\'EL MOUSSAOUI a tout géré avec professionnalisme. La décoration était magnifique et le buffet délicieux. Nous recommandons vivement !', 5, 'Mariage', NULL, 'publie', 1, 1, '2026-06-10 18:05:40'),
(2, NULL, NULL, 'Mohammed K.', 'Errachidia', 'Très satisfait de l\'organisation de notre cérémonie de fiançailles. Équipe réactive, prix raisonnables et résultat au-delà de nos espérances. Merci !', 5, 'Fiançailles', NULL, 'publie', 1, 2, '2026-06-10 18:05:40'),
(3, NULL, NULL, 'Aicha M.', 'Goulmima', 'Service de qualité pour notre buffet familial. Livraison à temps, plats chauds et savoureux. Je referai appel à Traiteur EL MOUSSAOUI sans hésitation.', 4, 'Buffet', NULL, 'publie', 0, 3, '2026-06-10 18:05:40'),
(4, NULL, NULL, 'Hassan El A.', 'Erfoud', 'Notre mariage était un vrai conte de fée grâce à leur travail. La tente, la décoration, la musique... tout était parfait. Bravo à toute l\'équipe !', 5, 'Mariage', NULL, 'publie', 1, 4, '2026-06-10 18:05:40');

-- --------------------------------------------------------

--
-- Structure de la table `types_evenements`
--

CREATE TABLE `types_evenements` (
  `id` int(10) UNSIGNED NOT NULL,
  `nom` varchar(100) NOT NULL,
  `nom_ar` varchar(100) DEFAULT NULL COMMENT 'Nom en arabe',
  `slug` varchar(120) NOT NULL,
  `description` text DEFAULT NULL,
  `icone` varchar(100) DEFAULT NULL COMMENT 'Classe CSS icône (Font Awesome)',
  `couleur` varchar(7) DEFAULT NULL COMMENT 'Couleur hex ex: #B8860B',
  `image` varchar(255) DEFAULT NULL,
  `ordre` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `actif` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Types d''événements organisés';

--
-- Déchargement des données de la table `types_evenements`
--

INSERT INTO `types_evenements` (`id`, `nom`, `nom_ar`, `slug`, `description`, `icone`, `couleur`, `image`, `ordre`, `actif`) VALUES
(1, 'Mariage', 'عرس', 'mariage', 'Organisation complète de cérémonies de mariage traditionnelles et modernes', 'fa-heart', '#D4AF37', NULL, 1, 1),
(2, 'Fiançailles', 'خطوبة', 'fiancailles', 'Cérémonie de fiançailles élégante et mémorable', 'fa-ring', '#B8860B', NULL, 2, 1),
(3, 'Circoncision', 'عقيقة وختان', 'circoncision', 'Fêtes traditionnelles de circoncision avec décoration et buffet', 'fa-baby', '#C0A000', NULL, 3, 1),
(4, 'Anniversaire', 'عيد ميلاد', 'anniversaire', 'Célébrations d\'anniversaire pour tous les âges', 'fa-birthday-cake', '#FFD700', NULL, 4, 1),
(5, 'Réception d\'entreprise', 'تظاهرة مهنية', 'reception-pro', 'Séminaires, conférences, galas et réceptions professionnelles', 'fa-briefcase', '#8B7500', NULL, 5, 1),
(6, 'Buffet & Banquet', 'بوفيه وضيافة', 'buffet-banquet', 'Service de buffet froid/chaud et banquets pour toutes occasions', 'fa-utensils', '#A0800B', NULL, 6, 1),
(7, 'Cérémonie religieuse', 'مناسبة دينية', 'ceremonie-reli', 'Fêtes religieuses : Aid, Mouloud, Laylat Al-Qadr...', 'fa-mosque', '#6B5B00', NULL, 7, 1),
(8, 'Autre', 'مناسبة أخرى', 'autre', 'Tout autre type de célébration ou d\'événement', 'fa-star', '#9A8000', NULL, 8, 1);

-- --------------------------------------------------------

--
-- Structure de la table `users`
--

CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `role_id` int(10) UNSIGNED NOT NULL DEFAULT 4 COMMENT 'FK vers roles',
  `nom` varchar(100) NOT NULL,
  `prenom` varchar(100) NOT NULL,
  `email` varchar(191) NOT NULL,
  `email_verifie_le` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL COMMENT 'Hash bcrypt',
  `telephone` varchar(20) DEFAULT NULL,
  `avatar` varchar(255) DEFAULT NULL COMMENT 'Chemin image profil',
  `langue` enum('fr','ar','en') NOT NULL DEFAULT 'fr',
  `remember_token` varchar(100) DEFAULT NULL,
  `actif` tinyint(1) NOT NULL DEFAULT 1,
  `derniere_connexion` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL COMMENT 'Soft delete'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Utilisateurs du système (admins et clients connectés)';

--
-- Déchargement des données de la table `users`
--

INSERT INTO `users` (`id`, `role_id`, `nom`, `prenom`, `email`, `email_verifie_le`, `password`, `telephone`, `avatar`, `langue`, `remember_token`, `actif`, `derniere_connexion`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 1, 'MOUSSAOUI', 'Admin', 'admin@traiteur-elmoussaoui.ma', NULL, '$2y$12$eImiTXuWVxfM37uY4JANjQ==hashedpassword_change_this', '0626986533', NULL, 'fr', NULL, 1, NULL, '2026-06-10 18:05:37', '2026-06-10 18:05:37', NULL);

-- --------------------------------------------------------

--
-- Doublure de structure pour la vue `v_reservations_detail`
-- (Voir ci-dessous la vue réelle)
--
CREATE TABLE `v_reservations_detail` (
`id` int(10) unsigned
,`reference` varchar(30)
,`date_evenement` date
,`heure_debut` time
,`nbr_invites` int(10) unsigned
,`statut` enum('en_attente','confirmee','en_cours','terminee','annulee')
,`montant_total` decimal(12,2)
,`montant_acompte` decimal(12,2)
,`client_nom_complet` varchar(201)
,`client_email` varchar(191)
,`client_telephone` varchar(20)
,`type_evenement` varchar(100)
,`package_nom` varchar(100)
,`package_prix` decimal(10,2)
,`salle_nom` varchar(150)
,`created_at` timestamp
);

-- --------------------------------------------------------

--
-- Doublure de structure pour la vue `v_stats_dashboard`
-- (Voir ci-dessous la vue réelle)
--
CREATE TABLE `v_stats_dashboard` (
`total_reservations` bigint(21)
,`reservations_en_attente` bigint(21)
,`reservations_confirmees` bigint(21)
,`devis_en_attente` bigint(21)
,`total_clients` bigint(21)
,`messages_nouveaux` bigint(21)
,`ca_total` decimal(34,2)
,`ca_mois_courant` decimal(34,2)
,`evenements_aujourd_hui` bigint(21)
);

-- --------------------------------------------------------

--
-- Structure de la vue `v_reservations_detail`
--
DROP TABLE IF EXISTS `v_reservations_detail`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_reservations_detail`  AS SELECT `r`.`id` AS `id`, `r`.`reference` AS `reference`, `r`.`date_evenement` AS `date_evenement`, `r`.`heure_debut` AS `heure_debut`, `r`.`nbr_invites` AS `nbr_invites`, `r`.`statut` AS `statut`, `r`.`montant_total` AS `montant_total`, `r`.`montant_acompte` AS `montant_acompte`, concat(`c`.`prenom`,' ',`c`.`nom`) AS `client_nom_complet`, `c`.`email` AS `client_email`, `c`.`telephone` AS `client_telephone`, `te`.`nom` AS `type_evenement`, `p`.`nom` AS `package_nom`, `p`.`prix` AS `package_prix`, `s`.`nom` AS `salle_nom`, `r`.`created_at` AS `created_at` FROM ((((`reservations` `r` join `clients` `c` on(`r`.`client_id` = `c`.`id`)) join `types_evenements` `te` on(`r`.`type_evenement_id` = `te`.`id`)) left join `packages` `p` on(`r`.`package_id` = `p`.`id`)) left join `salles` `s` on(`r`.`salle_id` = `s`.`id`)) WHERE `r`.`deleted_at` is null ;

-- --------------------------------------------------------

--
-- Structure de la vue `v_stats_dashboard`
--
DROP TABLE IF EXISTS `v_stats_dashboard`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_stats_dashboard`  AS SELECT (select count(0) from `reservations` where `reservations`.`statut` <> 'annulee' and `reservations`.`deleted_at` is null) AS `total_reservations`, (select count(0) from `reservations` where `reservations`.`statut` = 'en_attente' and `reservations`.`deleted_at` is null) AS `reservations_en_attente`, (select count(0) from `reservations` where `reservations`.`statut` = 'confirmee' and `reservations`.`deleted_at` is null) AS `reservations_confirmees`, (select count(0) from `devis` where `devis`.`statut` in ('recu','en_traitement')) AS `devis_en_attente`, (select count(0) from `clients` where `clients`.`deleted_at` is null) AS `total_clients`, (select count(0) from `contacts` where `contacts`.`statut` = 'nouveau') AS `messages_nouveaux`, (select coalesce(sum(`reservations`.`montant_total`),0) from `reservations` where `reservations`.`statut` in ('confirmee','en_cours','terminee') and `reservations`.`deleted_at` is null) AS `ca_total`, (select coalesce(sum(`reservations`.`montant_total`),0) from `reservations` where `reservations`.`statut` in ('confirmee','en_cours','terminee') and month(`reservations`.`date_evenement`) = month(curdate()) and year(`reservations`.`date_evenement`) = year(curdate()) and `reservations`.`deleted_at` is null) AS `ca_mois_courant`, (select count(0) from `reservations` where `reservations`.`date_evenement` = curdate() and `reservations`.`statut` <> 'annulee' and `reservations`.`deleted_at` is null) AS `evenements_aujourd_hui` ;

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_log_user` (`user_id`),
  ADD KEY `idx_log_action` (`action`),
  ADD KEY `idx_log_module` (`module`),
  ADD KEY `idx_log_date` (`created_at`);

--
-- Index pour la table `blog_articles`
--
ALTER TABLE `blog_articles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_blog_slug` (`slug`),
  ADD KEY `idx_blog_cat` (`categorie_id`),
  ADD KEY `idx_blog_statut` (`statut`),
  ADD KEY `idx_blog_date_pub` (`date_publication`),
  ADD KEY `fk_blog_auteur` (`auteur_id`);

--
-- Index pour la table `calendrier_disponibilites`
--
ALTER TABLE `calendrier_disponibilites`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_cal_date` (`date`),
  ADD KEY `idx_cal_statut` (`statut`),
  ADD KEY `fk_cal_reservation` (`reservation_id`);

--
-- Index pour la table `categories_blog`
--
ALTER TABLE `categories_blog`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_catblog_slug` (`slug`);

--
-- Index pour la table `categories_galerie`
--
ALTER TABLE `categories_galerie`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_catgal_slug` (`slug`);

--
-- Index pour la table `clients`
--
ALTER TABLE `clients`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_clients_email` (`email`),
  ADD KEY `idx_clients_user` (`user_id`),
  ADD KEY `idx_clients_ville` (`ville`);

--
-- Index pour la table `commentaires`
--
ALTER TABLE `commentaires`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_com_article` (`article_id`),
  ADD KEY `idx_com_parent` (`parent_id`),
  ADD KEY `idx_com_statut` (`statut`);

--
-- Index pour la table `contacts`
--
ALTER TABLE `contacts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_contacts_statut` (`statut`),
  ADD KEY `idx_contacts_date` (`created_at`);

--
-- Index pour la table `demandes_informations`
--
ALTER TABLE `demandes_informations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_dem_statut` (`statut`),
  ADD KEY `fk_dem_type_evt` (`type_evenement_id`);

--
-- Index pour la table `devis`
--
ALTER TABLE `devis`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_devis_ref` (`reference`),
  ADD KEY `idx_devis_client` (`client_id`),
  ADD KEY `idx_devis_statut` (`statut`),
  ADD KEY `idx_devis_date_evt` (`date_evenement`),
  ADD KEY `fk_devis_type_evt` (`type_evenement_id`),
  ADD KEY `fk_devis_reservation` (`reservation_id`),
  ADD KEY `fk_devis_traitant` (`user_id_traitant`);

--
-- Index pour la table `devis_generes`
--
ALTER TABLE `devis_generes`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `devis_lignes`
--
ALTER TABLE `devis_lignes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_devlig_devis` (`devis_id`),
  ADD KEY `idx_devlig_service` (`service_id`);

--
-- Index pour la table `factures`
--
ALTER TABLE `factures`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_factures_ref` (`reference`),
  ADD KEY `idx_fac_reservation` (`reservation_id`),
  ADD KEY `idx_fac_client` (`client_id`),
  ADD KEY `idx_fac_statut` (`statut`);

--
-- Index pour la table `galerie`
--
ALTER TABLE `galerie`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_galerie_cat` (`categorie_id`),
  ADD KEY `idx_galerie_type` (`type`),
  ADD KEY `idx_galerie_vedette` (`en_vedette`);

--
-- Index pour la table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_notif_notifiable` (`notifiable_type`,`notifiable_id`),
  ADD KEY `idx_notif_read` (`read_at`);

--
-- Index pour la table `packages`
--
ALTER TABLE `packages`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_packages_slug` (`slug`);

--
-- Index pour la table `packages_services`
--
ALTER TABLE `packages_services`
  ADD PRIMARY KEY (`package_id`,`service_id`),
  ADD KEY `fk_pkgsvc_service` (`service_id`);

--
-- Index pour la table `paiements`
--
ALTER TABLE `paiements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_pmt_facture` (`facture_id`);

--
-- Index pour la table `parametres`
--
ALTER TABLE `parametres`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_param_cle` (`cle`),
  ADD KEY `idx_param_groupe` (`groupe`);

--
-- Index pour la table `reservations`
--
ALTER TABLE `reservations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_reservations_ref` (`reference`),
  ADD KEY `idx_res_client` (`client_id`),
  ADD KEY `idx_res_type_event` (`type_evenement_id`),
  ADD KEY `idx_res_date` (`date_evenement`),
  ADD KEY `idx_res_statut` (`statut`),
  ADD KEY `fk_res_package` (`package_id`),
  ADD KEY `fk_res_salle` (`salle_id`),
  ADD KEY `fk_res_createur` (`user_id_createur`);

--
-- Index pour la table `reservation_services`
--
ALTER TABLE `reservation_services`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ressvc_reservation` (`reservation_id`),
  ADD KEY `idx_ressvc_service` (`service_id`);

--
-- Index pour la table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_roles_nom` (`nom`);

--
-- Index pour la table `salles`
--
ALTER TABLE `salles`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `services`
--
ALTER TABLE `services`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_services_slug` (`slug`);

--
-- Index pour la table `temoignages`
--
ALTER TABLE `temoignages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_tem_client` (`client_id`),
  ADD KEY `idx_tem_statut` (`statut`),
  ADD KEY `fk_tem_reservation` (`reservation_id`);

--
-- Index pour la table `types_evenements`
--
ALTER TABLE `types_evenements`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_types_slug` (`slug`);

--
-- Index pour la table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_users_email` (`email`),
  ADD KEY `idx_users_role` (`role_id`),
  ADD KEY `idx_users_actif` (`actif`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `blog_articles`
--
ALTER TABLE `blog_articles`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `calendrier_disponibilites`
--
ALTER TABLE `calendrier_disponibilites`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `categories_blog`
--
ALTER TABLE `categories_blog`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT pour la table `categories_galerie`
--
ALTER TABLE `categories_galerie`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT pour la table `clients`
--
ALTER TABLE `clients`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT pour la table `commentaires`
--
ALTER TABLE `commentaires`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `contacts`
--
ALTER TABLE `contacts`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `demandes_informations`
--
ALTER TABLE `demandes_informations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `devis`
--
ALTER TABLE `devis`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `devis_generes`
--
ALTER TABLE `devis_generes`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT pour la table `devis_lignes`
--
ALTER TABLE `devis_lignes`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `factures`
--
ALTER TABLE `factures`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `galerie`
--
ALTER TABLE `galerie`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT pour la table `packages`
--
ALTER TABLE `packages`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT pour la table `paiements`
--
ALTER TABLE `paiements`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `parametres`
--
ALTER TABLE `parametres`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT pour la table `reservations`
--
ALTER TABLE `reservations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT pour la table `reservation_services`
--
ALTER TABLE `reservation_services`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT pour la table `salles`
--
ALTER TABLE `salles`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `services`
--
ALTER TABLE `services`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT pour la table `temoignages`
--
ALTER TABLE `temoignages`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT pour la table `types_evenements`
--
ALTER TABLE `types_evenements`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT pour la table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD CONSTRAINT `fk_log_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `blog_articles`
--
ALTER TABLE `blog_articles`
  ADD CONSTRAINT `fk_blog_auteur` FOREIGN KEY (`auteur_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_blog_cat` FOREIGN KEY (`categorie_id`) REFERENCES `categories_blog` (`id`);

--
-- Contraintes pour la table `calendrier_disponibilites`
--
ALTER TABLE `calendrier_disponibilites`
  ADD CONSTRAINT `fk_cal_reservation` FOREIGN KEY (`reservation_id`) REFERENCES `reservations` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `clients`
--
ALTER TABLE `clients`
  ADD CONSTRAINT `fk_clients_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Contraintes pour la table `commentaires`
--
ALTER TABLE `commentaires`
  ADD CONSTRAINT `fk_com_article` FOREIGN KEY (`article_id`) REFERENCES `blog_articles` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_com_parent` FOREIGN KEY (`parent_id`) REFERENCES `commentaires` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `demandes_informations`
--
ALTER TABLE `demandes_informations`
  ADD CONSTRAINT `fk_dem_type_evt` FOREIGN KEY (`type_evenement_id`) REFERENCES `types_evenements` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `devis`
--
ALTER TABLE `devis`
  ADD CONSTRAINT `fk_devis_client` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_devis_reservation` FOREIGN KEY (`reservation_id`) REFERENCES `reservations` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_devis_traitant` FOREIGN KEY (`user_id_traitant`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_devis_type_evt` FOREIGN KEY (`type_evenement_id`) REFERENCES `types_evenements` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `devis_lignes`
--
ALTER TABLE `devis_lignes`
  ADD CONSTRAINT `fk_devlig_devis` FOREIGN KEY (`devis_id`) REFERENCES `devis` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_devlig_service` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `factures`
--
ALTER TABLE `factures`
  ADD CONSTRAINT `fk_fac_client` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_fac_reservation` FOREIGN KEY (`reservation_id`) REFERENCES `reservations` (`id`) ON UPDATE CASCADE;

--
-- Contraintes pour la table `galerie`
--
ALTER TABLE `galerie`
  ADD CONSTRAINT `fk_galerie_cat` FOREIGN KEY (`categorie_id`) REFERENCES `categories_galerie` (`id`) ON UPDATE CASCADE;

--
-- Contraintes pour la table `packages_services`
--
ALTER TABLE `packages_services`
  ADD CONSTRAINT `fk_pkgsvc_package` FOREIGN KEY (`package_id`) REFERENCES `packages` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_pkgsvc_service` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `paiements`
--
ALTER TABLE `paiements`
  ADD CONSTRAINT `fk_pmt_facture` FOREIGN KEY (`facture_id`) REFERENCES `factures` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `reservations`
--
ALTER TABLE `reservations`
  ADD CONSTRAINT `fk_res_client` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_res_createur` FOREIGN KEY (`user_id_createur`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_res_package` FOREIGN KEY (`package_id`) REFERENCES `packages` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_res_salle` FOREIGN KEY (`salle_id`) REFERENCES `salles` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_res_type_evt` FOREIGN KEY (`type_evenement_id`) REFERENCES `types_evenements` (`id`) ON UPDATE CASCADE;

--
-- Contraintes pour la table `reservation_services`
--
ALTER TABLE `reservation_services`
  ADD CONSTRAINT `fk_ressvc_reservation` FOREIGN KEY (`reservation_id`) REFERENCES `reservations` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_ressvc_service` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON UPDATE CASCADE;

--
-- Contraintes pour la table `temoignages`
--
ALTER TABLE `temoignages`
  ADD CONSTRAINT `fk_tem_client` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_tem_reservation` FOREIGN KEY (`reservation_id`) REFERENCES `reservations` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `fk_users_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
