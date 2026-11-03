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

## Deploying to Vercel (Docker container, free TiDB Cloud database)

Vercel does not run PHP natively. This project includes a `Dockerfile`, a `Caddyfile` and a `.dockerignore`, which Vercel's Container Runtime (Beta) builds. The database is a separate MySQL service, because InfinityFree does not allow outside connections to its databases.

This guide uses **TiDB Cloud Starter**, which has a free tier (5 GiB storage and 50 million request units a month per instance, no credit card needed).

### 1. Create the database (TiDB Cloud)

1. Sign up at **tidbcloud.com** and create a **Starter** instance on **AWS**. Choose the region closest to you. Singapore (`ap-southeast-1`) is closest to the Philippines.
2. Open the instance's networking settings. Make sure the **public endpoint** is on, and add the IP rule `0.0.0.0/0`. Vercel's outgoing IP addresses change, so the password and TLS are what protect the database.
3. Click **Connect** (top right), then **Connect With: MySQL CLI**, then **Generate Password**. The password is shown only once. Write down the **host**, the **port** (4000) and the **username**. The username looks like `PREFIX.root`; keep the prefix and the dot.
4. Create the database. Replace `HOST` and `PREFIX`, then enter the password when asked:

   ```bash
   mysql --ssl-mode=REQUIRED -h HOST -P 4000 -u 'PREFIX.root' -p -e "CREATE DATABASE IF NOT EXISTS aquado;"
   ```

5. Import the tables from this repository. Run it from the project folder:

   ```bash
   grep -vE '^/\*!|^/\*M!' database/aquado_schema.sql | mysql --ssl-mode=REQUIRED -h HOST -P 4000 -u 'PREFIX.root' -p aquado
   ```

   The `grep` removes MySQL-only setting lines. The table and data statements stay.

6. Check the tables. You should see 10, including `sessions` and `payment_proofs`.

   ```bash
   mysql --ssl-mode=REQUIRED -h HOST -P 4000 -u 'PREFIX.root' -p aquado -e "SHOW TABLES;"
   ```

### 2. Deploy on Vercel

1. Choose **Add New → Project**, then import the GitHub repository. Vercel detects the root `Dockerfile` and selects the **Container** preset.
2. Before deploying, add these **environment variables**. Set them for **Production** only, so preview deployments don't write to the live database.

   | Name | Value |
   |---|---|
   | `DB_HOST` | the host from step 1 |
   | `DB_PORT` | `4000` |
   | `DB_USER` | `PREFIX.root` (no quotes) |
   | `DB_PASS` | the generated password |
   | `DB_NAME` | `aquado` |
   | `DB_SSL` | `true` |

3. Click **Deploy**. The first build can take a few minutes.
4. Open the `*.vercel.app` address. It should redirect to `/Finals/`.

### 3. Create staff accounts

Change the name, email, role (`admin`, `cashier` or `driver`) and password. Then run:

```bash
php -r 'echo "INSERT INTO users (fullname, email, password, role) VALUES (\"Admin\", \"admin@yourstation.test\", \"" . password_hash("CHOOSE-A-STRONG-PASSWORD", PASSWORD_DEFAULT) . "\", \"admin\");", "\n";' | mysql --ssl-mode=REQUIRED -h HOST -P 4000 -u 'PREFIX.root' -p aquado
```

Staff log in at `/Operations/`. Customers register on the site.

### 4. Test

- Register, log in, add to cart and check out (cash on delivery).
- Log in to `/Operations/` as admin, confirm and dispatch the order. The driver accepts, picks up and delivers it.
- Refresh a logged-in page a few times. You should stay logged in.
- `/robots.txt` and `/sitemap.xml` return text. `/config.php` is blocked.

### Troubleshooting

| Symptom | Fix |
|---|---|
| "Database connection failed" | Check that `DB_PORT` is `4000`, `DB_USER` includes the prefix and `DB_SSL` is `true`. Then redeploy, because variable changes need a new deployment. |
| Connection refused or timeout | The public endpoint is off, or the `0.0.0.0/0` rule is missing. |
| Stops accepting connections partway through a month | The free monthly quota is used up. It resets each month, or you can raise the quota in the TiDB console. |
| Import shows an error | Copy the message and send it with the step number. |

### Notes

- Login sessions and GCash payment photos are stored in the database. Each shrunk photo is about 150–600 KB.
- `DB_SSL=true` encrypts the connection but does not check the server certificate.
- Vercel's Hobby plan is for personal, non-commercial use. Check Vercel's terms before using it for the business.
- Keep the repository private, because this guide names the default staff passwords.
- **PlanetScale** is a paid alternative (its smallest plan is about $39 a month, plus storage). Its import needs safe migrations turned off on the `main` branch first, and its port is 3306, so leave `DB_PORT` at 3306 or unset.
