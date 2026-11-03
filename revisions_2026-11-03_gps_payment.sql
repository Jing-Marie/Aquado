-- --------------------------------------------------------
-- Aquado: live driver tracking + payment confirmation (2026-11-03)
-- Run once against the `aquado` database, after revisions_2026-11-03.sql.
-- --------------------------------------------------------

-- Latest GPS position of each driver (one row per driver, since a
-- driver can handle several orders at the same time).
CREATE TABLE IF NOT EXISTS `driver_locations` (
  `driver_id` int(11) NOT NULL,
  `latitude` decimal(10,7) NOT NULL,
  `longitude` decimal(10,7) NOT NULL,
  `last_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`driver_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- The rider confirms the customer's payment when completing the delivery.
ALTER TABLE `orders`
  ADD COLUMN `payment_status` enum('Unpaid','Paid') NOT NULL DEFAULT 'Unpaid' AFTER `payment_method`,
  ADD COLUMN `paid_at` datetime DEFAULT NULL AFTER `payment_status`;
