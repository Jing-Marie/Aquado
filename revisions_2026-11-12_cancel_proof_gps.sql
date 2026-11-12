-- --------------------------------------------------------
-- Aquado: order cancellation, notifications, proof of delivery and
-- rider online/offline + GPS sharing state (2026-11-12)
-- Run once against the `aquado` database (local MySQL, TiDB Cloud, ...), after
-- revisions_2026-11-04_vercel.sql. Already included in database/aquado_schema.sql.
-- Each column is added by its own statement so it also runs on TiDB.
--
-- 1. Cancel Order (Pending orders only): who/when/why is saved on the order.
-- 2. In-app notifications for the customer and the staff (admin + cashier).
-- 3. Proof of delivery: photo, empty containers collected, delivery date/time.
--    The delivery photo itself is stored in the existing `payment_proofs`
--    table (like the GCash photos); orders.delivery_photo keeps its name.
-- 4. Rider Online/Offline switch and a flag that says whether the rider's
--    GPS location is currently being shared.
-- --------------------------------------------------------

ALTER TABLE `orders` ADD COLUMN `cancelled_at` datetime DEFAULT NULL AFTER `delivery_date`;
ALTER TABLE `orders` ADD COLUMN `cancelled_by` int(11) DEFAULT NULL AFTER `cancelled_at`;
ALTER TABLE `orders` ADD COLUMN `cancel_reason` varchar(255) DEFAULT NULL AFTER `cancelled_by`;
ALTER TABLE `orders` ADD COLUMN `delivery_photo` varchar(255) DEFAULT NULL AFTER `cancel_reason`;
ALTER TABLE `orders` ADD COLUMN `empty_containers_collected` int(11) DEFAULT NULL AFTER `delivery_photo`;
ALTER TABLE `orders` ADD COLUMN `delivered_at` datetime DEFAULT NULL AFTER `empty_containers_collected`;

ALTER TABLE `users` ADD COLUMN `is_online` tinyint(1) NOT NULL DEFAULT 1 AFTER `address`;

ALTER TABLE `driver_locations` ADD COLUMN `is_sharing` tinyint(1) NOT NULL DEFAULT 1 AFTER `longitude`;

CREATE TABLE IF NOT EXISTS `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `type` varchar(30) NOT NULL DEFAULT 'info',
  `message` varchar(255) NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_unread` (`user_id`, `is_read`),
  KEY `order_id` (`order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
