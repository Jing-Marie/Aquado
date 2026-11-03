-- --------------------------------------------------------
-- Aquado: database sessions + payment photos kept in the database (2026-11-04)
-- Run once against the `aquado` database. Needed for hosting where files and
-- sessions on disk do not persist (Vercel). Already included in
-- database/aquado_schema.sql.
--
-- Payment photos uploaded before this change stay in uploads/payment_proofs/
-- and are not moved automatically.
-- --------------------------------------------------------

-- Login sessions (both apps).
CREATE TABLE IF NOT EXISTS `sessions` (
  `id` varchar(128) NOT NULL,
  `data` mediumblob NOT NULL,
  `last_access` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `last_access` (`last_access`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- GCash payment photos. orders.payment_proof keeps the photo's name.
CREATE TABLE IF NOT EXISTS `payment_proofs` (
  `name` varchar(64) NOT NULL,
  `mime_type` varchar(30) NOT NULL,
  `data` mediumblob NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
