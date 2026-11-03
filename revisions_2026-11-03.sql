-- --------------------------------------------------------
-- Aquado requested revisions (2026-11-03)
-- Run once against the `aquado` database, after aquado_db.sql.
-- --------------------------------------------------------

-- --------------------------------------------------------
-- 1. Order statuses for the refill pickup / wash / deliver workflow
--    Old 'Dispatched' (driver assigned) becomes 'Assigned'.
-- --------------------------------------------------------

ALTER TABLE `orders`
  MODIFY `status` enum('Pending','Confirmed','Dispatched','Assigned','Accepted','Picked Up','Ready for Delivery','Out for Delivery','Delivered','Cancelled') NOT NULL DEFAULT 'Pending';

UPDATE `orders` SET `status` = 'Assigned' WHERE `status` = 'Dispatched';
UPDATE `delivery_tracking` SET `status` = 'Assigned' WHERE `status` = 'Dispatched';

ALTER TABLE `orders`
  MODIFY `status` enum('Pending','Confirmed','Assigned','Accepted','Picked Up','Ready for Delivery','Out for Delivery','Delivered','Cancelled') NOT NULL DEFAULT 'Pending';

-- --------------------------------------------------------
-- 2. Product types: refills are services (no stock),
--    containers and PET bottles are physical products.
-- --------------------------------------------------------

ALTER TABLE `products`
  ADD COLUMN `product_type` enum('refill','container','bottle') NOT NULL DEFAULT 'refill' AFTER `unit`,
  MODIFY `stock` int(11) DEFAULT NULL;

UPDATE `products` SET `product_type` = 'refill'    WHERE `id` IN (1, 3);
UPDATE `products` SET `product_type` = 'container' WHERE `id` IN (4, 5);
UPDATE `products` SET `product_type` = 'bottle'    WHERE `id` = 2;

UPDATE `products` SET `stock` = NULL WHERE `product_type` = 'refill';

-- NOTE: placeholder price, update to the real 500 ml price.
INSERT INTO `products` (`name`, `description`, `price`, `unit`, `product_type`, `image`, `stock`, `is_active`)
VALUES ('Purified Water 500ml', 'Convenient 500 ml purified water in a PET bottle.', 8.00, '500-ml', 'bottle', 'assets/1liter.svg', 0, 1);

-- --------------------------------------------------------
-- 3. Orders that contain a refill need the empty containers picked up
--    and washed/refilled before delivery.
-- --------------------------------------------------------

ALTER TABLE `orders`
  ADD COLUMN `requires_pickup` tinyint(1) NOT NULL DEFAULT 0 AFTER `container_swap`;

UPDATE `orders` o
SET o.`requires_pickup` = 1
WHERE EXISTS (
    SELECT 1
    FROM `order_items` oi
    JOIN `products` p ON p.`id` = oi.`product_id`
    WHERE oi.`order_id` = o.`id`
      AND p.`product_type` = 'refill'
);

-- --------------------------------------------------------
-- 4. Inventory only tracks physical products
-- --------------------------------------------------------

UPDATE `container_inventory` SET `container_type` = 'Slim Gallon Container'  WHERE `container_type` = '5-Gallon Slim';
UPDATE `container_inventory` SET `container_type` = 'Round Gallon Container' WHERE `container_type` = '5-Gallon Round';
DELETE FROM `container_inventory` WHERE `container_type` = '1-Gallon Bottle';

INSERT INTO `container_inventory` (`container_type`, `total_count`, `filled_count`, `empty_count`) VALUES
('1 Liter PET Bottle', 0, 0, 0),
('500 ml PET Bottle', 0, 0, 0);
