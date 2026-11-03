# Putting Aquado Online (InfinityFree) and Installing the Driver App

Files in this folder:

| File | What it is |
|---|---|
| `aquado_website.zip` | Both apps (customer website + Operations/driver app), ready to upload |
| `aquado_database.sql` | Your current database (products, accounts, orders) to import on the host |

After setup your links will be (replace `yourname.infinityfreeapp.com` with your real address):

- Customer website: `https://yourname.infinityfreeapp.com/`
- Operations / Driver app: `https://yourname.infinityfreeapp.com/Operations/`

---

## ⚠️ Before you go live: change the demo passwords

All staff accounts use easy passwords (`admin`, `cashier`, `driver`). Once the site is public, anyone could log in with them. Ask Claude to set strong passwords and regenerate `aquado_database.sql` **before** you import it.

---

## 1. Create the hosting account

1. Sign up at **infinityfree.com** (free).
2. Click **Create Account** → choose a free subdomain, e.g. `aquado.infinityfreeapp.com` (or connect your own domain later).
3. Wait until the account shows **Active**, then open its **Control Panel**.

## 2. Create the database

1. In the Control Panel open **MySQL Databases**.
2. Create a database (e.g. name it `aquado`). The panel shows four values. Write them down:
   - **MySQL Hostname**, e.g. `sql123.infinityfree.com`
   - **MySQL Username**, e.g. `if0_12345678`
   - **MySQL Password**: your hosting account password (shown in the panel)
   - **Database Name**, e.g. `if0_12345678_aquado`
3. Click **phpMyAdmin** next to the new database → **Import** tab → choose `aquado_database.sql` → **Import**.
   You should see 10 tables (cart, container_inventory, delivery_tracking, driver_locations, order_items, orders, payment_proofs, products, sessions, users).
   If this database was set up before 2026-11-04, also run `revisions_2026-11-04_vercel.sql` in the **SQL** tab. It adds the `sessions` and `payment_proofs` tables the apps now need.

## 3. Upload the website

1. In the Control Panel open **Online File Manager** and go into the **htdocs** folder.
2. Delete the default `index2.html` file if it's there.
3. Upload `aquado_website.zip` into **htdocs** and **extract** it there.
   (If the file manager can't extract, unzip it on your computer and upload the contents with **FileZilla** using the FTP details in the Control Panel.)
4. After extracting, `htdocs` should contain: `Finals/`, `Operations/`, `uploads/`, `.htaccess`, `index.php`, `config.php`, `robots.php`, `sitemap.php`.

## 4. Connect the website to the database

Open `htdocs/config.php` in the File Manager (Edit) and put in the four values from step 2:

```php
define("DB_HOST", "sql123.infinityfree.com");
define("DB_USER", "if0_12345678");
define("DB_PASS", "your-hosting-password");
define("DB_NAME", "if0_12345678_aquado");
```

Save.

## 5. Turn on HTTPS (required for GPS tracking and installing the driver app)

1. In the Control Panel open **SSL Certificates** and install the free certificate for your address (skip if `https://` already works).
2. Open `https://yourname.infinityfreeapp.com/`. You should see the Aquado splash screen.

## 6. Check that everything works

| Open | Expect |
|---|---|
| `https://…/` | Splash screen → customer website |
| `https://…/Finals/home.php` | Home page |
| `https://…/Operations/` | Staff login |
| `https://…/robots.txt` | Text starting with `User-agent: *` |
| `https://…/sitemap.xml` | A list of 3 page addresses |
| `https://…/config.php` | **Forbidden** (this is correct) |

Then place a test order as a customer and take it through the cashier and driver steps.

---

## 7. Make the website discoverable on Google

The site already includes everything Google needs: page titles and descriptions, a business listing block (address, phone, opening hours), a share preview image, a sitemap, and robots rules that hide the staff app and payment photos.

1. Go to **search.google.com/search-console** → **Add property** → **URL prefix** → enter `https://yourname.infinityfreeapp.com/`.
2. Verify ownership with the **HTML file** method: download the file Google gives you and upload it into **htdocs**, then click **Verify**.
3. Open **Sitemaps** → enter `sitemap.xml` → **Submit**.
4. Open **URL Inspection** → paste `https://yourname.infinityfreeapp.com/Finals/home.php` → **Request indexing**.

Google usually lists a new site within a few days to a few weeks.

**Show up on Google Maps / "water refilling station near me":** create a free **Google Business Profile** at **business.google.com** for *Aquado Purified Water Refilling Station*, 143 Rizal Boulevard, Barangay Tagapo, Santa Rosa, Laguna, and add the website link. This is the biggest boost for local searches.

**Share the link** on your Facebook page and group chats. Links to your site help Google find it, and the post shows the Aquado preview image.

---

## 8. Install the Aquado Driver app on Android

Send the drivers this link: `https://yourname.infinityfreeapp.com/Operations/`

On the driver's Android phone:

1. Open the link in **Google Chrome**.
2. Tap **Install Aquado Driver App** on the login page.
   (If the button doesn't appear: tap Chrome's **⋮** menu → **Install app** or **Add to Home screen**.)
3. The **Aquado Driver** icon appears on the home screen. It opens full-screen like a normal app.
4. Log in with the driver account.
5. When asked, tap **Allow** for **location**. This is needed for live tracking on the customer's map.
   If location was blocked: Android **Settings → Apps → Chrome → Permissions → Location → Allow**.

Updates appear automatically. There's nothing to reinstall when the website changes.

---

## Troubleshooting

| Problem | Fix |
|---|---|
| "Database connection failed" | Check the four values in `config.php` (step 4). |
| "Too many redirects" | In `htdocs/.htaccess`, delete the 4 lines under *Always use the secure (https) address*. Install the SSL certificate first. |
| GPS map says location is blocked | Site must open with `https://`, and the driver must allow location for Chrome. |
| No "Install app" option | Must be opened in Chrome with `https://`. Try ⋮ → Add to Home screen. |
| GCash photo upload fails | Make sure `htdocs/uploads/payment_proofs/` exists. |
| Page not found for `/robots.txt` or `/sitemap.xml` | Make sure the `.htaccess` file was uploaded (it's a hidden file; enable "show hidden files" in FileZilla). |

---

## Deploying to Vercel (Docker container)

Vercel does not run PHP natively. This project includes a `Dockerfile`, a `Caddyfile` and a `.dockerignore`, which Vercel's Container Runtime (Beta) builds. The database is a separate MySQL service, because InfinityFree does not allow outside connections to its databases.

1. **Create the database.** In the Vercel dashboard, open **Marketplace** → **PlanetScale** and create a database named `aquado`. Copy its connection details: host, username, password and database name.
2. **Import the tables.** From a computer with the MySQL client installed, run this once (replace the placeholders, then enter the password when asked):

   ```bash
   mysql --ssl-mode=REQUIRED -h HOST -u USERNAME -p DATABASE_NAME < database/aquado_schema.sql
   ```

   Use `database/aquado_schema.sql` from this repository, not `aquado_database.sql`.
3. **Set the environment variables** in the Vercel project (**Settings** → **Environment Variables**):

   | Name | Value |
   |---|---|
   | `DB_HOST` | the PlanetScale host |
   | `DB_USER` | the username |
   | `DB_PASS` | the password |
   | `DB_NAME` | `aquado` |
   | `DB_SSL` | `true` |

4. **Deploy.** Import the GitHub repository in Vercel (**Add New** → **Project**). Vercel detects the root `Dockerfile` and builds it.
5. **Create staff accounts.** The schema file has no staff accounts, so add them once. Run this locally with your own password, then paste the hash into an `INSERT INTO users (fullname, email, password, role) VALUES (...)` statement (role `admin`, `cashier` or `driver`):

   ```bash
   php -r 'echo password_hash("CHOOSE-A-STRONG-PASSWORD", PASSWORD_DEFAULT), "\n";'
   ```

Notes:
- Vercel serves HTTPS, so the `.htaccess` HTTPS redirect is not used. The `Caddyfile` does the same job as the rest of `.htaccess` (`robots.txt`, `sitemap.xml`, blocked files).
- Login sessions and GCash payment photos are stored in the database, so they work on any of Vercel's servers.
- Keep the repository private, because this guide names the default staff passwords.
