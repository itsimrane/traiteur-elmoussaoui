-- ════════════════════════════════════════════════════════════
-- CORRECTIF : tables factures + paiements
-- (syntaxe DEFAULT curdate() corrigée en DEFAULT (curdate())
--  pour être compatible avec MySQL, pas seulement MariaDB)
-- ════════════════════════════════════════════════════════════

CREATE TABLE `factures` (
  `id` int(10) UNSIGNED NOT NULL,
  `reference` varchar(30) NOT NULL COMMENT 'Ex: FAC-2025-0001',
  `reservation_id` int(10) UNSIGNED NOT NULL,
  `client_id` int(10) UNSIGNED NOT NULL,
  `date_facture` date NOT NULL DEFAULT (curdate()),
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

CREATE TABLE `paiements` (
  `id` int(10) UNSIGNED NOT NULL,
  `facture_id` int(10) UNSIGNED NOT NULL,
  `montant` decimal(12,2) NOT NULL,
  `mode` enum('especes','virement','cheque','cmi','wave','whatsapp_pay','autre') NOT NULL DEFAULT 'especes',
  `reference_pmt` varchar(100) DEFAULT NULL COMMENT 'Numéro de virement, chèque, etc.',
  `date_paiement` date NOT NULL DEFAULT (curdate()),
  `recu_par` int(10) UNSIGNED DEFAULT NULL COMMENT 'FK user admin',
  `note` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Paiements reçus';

ALTER TABLE `factures`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_factures_ref` (`reference`),
  ADD KEY `idx_fac_reservation` (`reservation_id`),
  ADD KEY `idx_fac_client` (`client_id`);

ALTER TABLE `paiements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_pmt_facture` (`facture_id`);

ALTER TABLE `factures`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `paiements`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `factures`
  ADD CONSTRAINT `fk_fac_client` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_fac_reservation` FOREIGN KEY (`reservation_id`) REFERENCES `reservations` (`id`) ON UPDATE CASCADE;

ALTER TABLE `paiements`
  ADD CONSTRAINT `fk_pmt_facture` FOREIGN KEY (`facture_id`) REFERENCES `factures` (`id`) ON DELETE CASCADE;
