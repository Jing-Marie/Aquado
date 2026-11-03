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

## Database changes

`database/aquado_schema.sql` already includes everything. The `revisions_*.sql` files are the step-by-step changes for upgrading an older copy of the database.

## Deploying

See `deploy/DEPLOY_GUIDE.md` (InfinityFree hosting, Google Search Console, installing the driver app on Android, and deploying to Vercel as a Docker container with a PlanetScale MySQL database).

Private data (customer database exports, backups, payment photos) is excluded by `.gitignore`. Never commit real database credentials in `config.php`.
