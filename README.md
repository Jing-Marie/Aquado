# Aquado Purified Water Refilling Station

Two PHP + MySQL apps that share one database:

| Folder | App | Who uses it |
|---|---|---|
| `Finals/` | Customer website: catalog, cart, checkout, order tracking with live driver map, e-receipts | Customers |
| `Operations/` | Operations app (installable "Aquado Driver" PWA): order dispatch, payments, driver locations, inventory, reports, driver delivery workflow | Admin, cashier, drivers |

Shared: `config.php` (database settings), `uploads/payment_proofs/` (GCash photos, not in git), `robots.php` / `sitemap.php` (served as `/robots.txt` and `/sitemap.xml` via `.htaccess`).

## Run locally (XAMPP)

1. Put this folder at `C:\xampp\htdocs\Aquado` and start Apache + MySQL.
2. Create a database named `aquado` and import `database/aquado_schema.sql`.
3. Create accounts: register customers at `/Finals/register.php`; staff accounts (role `admin`, `cashier` or `driver`) are added in the `users` table with a `password_hash()` password.
4. Open:
   - Customer website: http://localhost/Aquado/Finals/
   - Operations app: http://localhost/Aquado/Operations/

## Order workflow

Refill orders: Pending → Confirmed (staff accepts) → Assigned (driver chosen) → Accepted (driver) → Picked Up (empty containers) → Ready for Delivery (staff washed & refilled) → Out for Delivery → Delivered.
Containers / PET bottles skip Picked Up and Ready for Delivery.

Payments: Unpaid → For Verification (rider collected cash, or uploaded a GCash payment photo) → Paid (cashier confirmed) or Rejected (rider uploads a new photo).

Cancelling: a customer or staff (admin / cashier) can cancel an order **only while it is Pending**. The order becomes Cancelled, the customer, admin and cashier are notified (bell icon / Notifications page), and anything past Pending is refused.

Proof of delivery: to finish a delivery the rider uploads a delivery photo, enters the empty containers collected, and the date/time is recorded automatically. It is saved on the order and in its history, shown to the customer on the tracking page, and admin / cashier review it with **View Proof** in Order Management.

Rider location: shared only while the rider is **Online** and has an active delivery. Going Offline, logging out or finishing the last delivery stops it. If the GPS signal drops, customers and admin keep seeing the last known position (faded and labelled) until a new one arrives.

## Database changes

`database/aquado_schema.sql` already includes everything. The `revisions_*.sql` files are the step-by-step changes for upgrading an older copy of the database. After pulling this version run `revisions_2026-11-12_cancel_proof_gps.sql` once on your existing database (cancel / proof of delivery / notifications / rider online status) **before** deploying.

## Deploying

See `deploy/DEPLOY_GUIDE.md` (InfinityFree hosting, Google Search Console, installing the driver app on Android, and deploying to Vercel as a Docker container with a free TiDB Cloud MySQL database).

Private data (customer database exports, backups, payment photos) is excluded by `.gitignore`. Never commit real database credentials in `config.php`.
