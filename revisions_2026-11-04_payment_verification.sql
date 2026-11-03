-- --------------------------------------------------------
-- Aquado: GCash payment proof + cashier payment confirmation (2026-11-04)
-- Run once against the `aquado` database, after revisions_2026-11-03_gps_payment.sql.
--
-- Payment flow:
--   Unpaid → For Verification (rider collected cash / uploaded GCash photo)
--          → Paid (cashier confirmed)  or  Rejected (cashier rejected the GCash proof)
-- --------------------------------------------------------

ALTER TABLE `orders`
  MODIFY `payment_status` enum('Unpaid','For Verification','Paid','Rejected') NOT NULL DEFAULT 'Unpaid',
  ADD COLUMN `payment_proof` varchar(255) DEFAULT NULL AFTER `payment_status`,
  ADD COLUMN `payment_collected_at` datetime DEFAULT NULL AFTER `payment_proof`,
  ADD COLUMN `payment_confirmed_by` int(11) DEFAULT NULL AFTER `paid_at`,
  ADD COLUMN `payment_note` varchar(255) DEFAULT NULL AFTER `payment_confirmed_by`;

-- The cashier account (password "cashier") is created by a PHP script,
-- since the password must be stored as a hash.
