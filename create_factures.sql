-- ════════════════════════════════════════════════════════════
-- CRÉATION TABLE : factures
-- Traiteur EL MOUSSAOUI
-- À exécuter dans phpMyAdmin → db_traiteur_elmoussaoui → SQL
-- ════════════════════════════════════════════════════════════

CREATE TABLE IF NOT EXISTS `factures` (
  `id`              INT UNSIGNED     NOT NULL AUTO_INCREMENT,
  `numero`          VARCHAR(30)      NOT NULL UNIQUE COMMENT 'FAC-2026-0001',
  `client_id`       INT UNSIGNED     NULL,
  `reservation_id`  INT UNSIGNED     NULL,
  `nom_client`      VARCHAR(200)     NOT NULL,
  `email_client`    VARCHAR(191)     NULL,
  `telephone_client`VARCHAR(20)      NULL,
  `type_evenement`  VARCHAR(100)     NULL,
  `date_evenement`  DATE             NULL,
  `nb_personnes`    INT              NULL,
  `package_nom`     VARCHAR(100)     NULL,
  `montant_ht`      DECIMAL(10,2)    NOT NULL DEFAULT 0,
  `tva`             DECIMAL(5,2)     NOT NULL DEFAULT 0 COMMENT 'Taux TVA %',
  `montant_tva`     DECIMAL(10,2)    NOT NULL DEFAULT 0,
  `montant_ttc`     DECIMAL(10,2)    NOT NULL DEFAULT 0,
  `acompte`         DECIMAL(10,2)    NOT NULL DEFAULT 0,
  `reste_a_payer`   DECIMAL(10,2)    NOT NULL DEFAULT 0,
  `statut`          ENUM('brouillon','envoyee','payee','partiellement_payee','annulee')
                    NOT NULL DEFAULT 'brouillon',
  `notes`           TEXT             NULL,
  `date_echeance`   DATE             NULL,
  `date_paiement`   DATE             NULL,
  `created_at`      TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`      TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_factures_client` (`client_id`),
  KEY `idx_factures_statut` (`statut`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Factures Traiteur EL MOUSSAOUI';

-- Données exemple
INSERT IGNORE INTO `factures`
  (numero, nom_client, email_client, telephone_client, type_evenement, date_evenement, nb_personnes, package_nom, montant_ht, montant_ttc, acompte, reste_a_payer, statut, date_echeance)
VALUES
  ('FAC-2026-0001', 'Mohammed Alami',   'malami@gmail.com',   '0661234567', 'Mariage',      '2026-08-15', 200, 'Or',      18000, 18000, 5400,  12600, 'partiellement_payee', '2026-08-12'),
  ('FAC-2026-0002', 'Fatima Benali',    'fbenali@gmail.com',  '0662345678', 'Fiançailles',  '2026-09-05', 100, 'Argent',  10000, 10000, 3000,  7000,  'envoyee',             '2026-09-02'),
  ('FAC-2026-0003', 'Youssef El Amri', 'yelamri@gmail.com',  '0663456789', 'Anniversaire', '2026-07-20', 80,  'Bronze',  5000,  5000,  5000,  0,     'payee',               '2026-07-17'),
  ('FAC-2026-0004', 'Aicha Mansouri',  'amansouri@gmail.com','0664567890', 'Mariage',      '2026-10-10', 300, 'Platine', 30000, 30000, 9000,  21000, 'brouillon',           '2026-10-07');

SELECT id, numero, nom_client, montant_ttc, statut FROM factures ORDER BY created_at DESC;
