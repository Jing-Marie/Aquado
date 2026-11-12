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

## Deploying to Vercel with a free TiDB Cloud database

This is the setup for the live site (**https://aquado.vercel.app**). Vercel runs the PHP app as a container, and TiDB Cloud stores the data. Both run in Singapore, so each database query stays close to the app.

| Part | Where | What it is |
|---|---|---|
| App | Vercel, region `sin1` (Singapore) | Built from the root `Dockerfile` with the **Container** preset. The region is set in `vercel.json`. |
| Database | TiDB Cloud Starter, region `ap-southeast-1` (Singapore) | MySQL-compatible, with a free tier (5 GiB storage and 50 million request units a month per instance). No credit card needed. |

InfinityFree can't be used for the database, because it doesn't allow outside connections.

### 1. Create the database in TiDB Cloud

1. Sign up at **tidbcloud.com**. Create a **Starter** instance on **AWS** in region **ap-southeast-1 (Singapore)**.
2. Open the instance's networking settings. Turn on the **public endpoint** and add the IP rule `0.0.0.0/0`. Vercel's outgoing IP addresses change, so the password and TLS protect the database instead.
3. Click **Connect** (top right), choose **Connect With: MySQL CLI**, then click **Generate Password**. The password is shown only once, so save it somewhere safe.
4. Note three values from the Connect dialog:
   - **Host**, for example `gateway01.ap-southeast-1.prod.aws.tidbcloud.com`. Use the one shown for your instance.
   - **Port**: `4000`.
   - **Username**: `PREFIX.root`. The part before the dot is your prefix. Use the username from the dialog, not your TiDB login name or email.

> On a Mac, the `mysql` command in Terminal can fail with *"Authentication plugin 'mysql_native_password' cannot be loaded"*. Use the **SQL Editor** method below instead. It needs no local tools.

### 2. Load the data

Use the **SQL Editor** of your TiDB instance. Each step copies a file to the clipboard with Terminal, then you paste it into the editor.

In the editor, click inside it, press **Cmd+A**, then **Delete**, then **Cmd+V**, then **Run**. Afterwards, refresh the schema list and expand **aquado**.

Choose one option:

**Option A: empty tables and the product list (from this repository)**

From the project folder, run:

```bash
(echo 'CREATE DATABASE IF NOT EXISTS aquado;'; echo 'USE aquado;'; grep -vE '^/\*!|^/\*M!' database/aquado_schema.sql) | pbcopy
```

Paste and run. You should see 10 tables and 6 products. There are no accounts yet, so create staff accounts in step 3.

**Option B: all of the owner's data (private)**

This file is not in the repository. It holds customers' names, phone numbers and addresses, and the accounts' login details. The owner creates it as `aquado_tidb_import.sql` in the project folder. Keep it private.

```bash
pbcopy < aquado_tidb_import.sql
```

Paste and run. You should see 10 tables, 5 accounts, 7 orders and 6 products. Then **change every account's password straight away** (step 3). These accounts still use the old demo passwords, and anyone who knows them can sign in to the Operations app.

To check the tables, run `SHOW TABLES;` in the SQL Editor. You should see 10 tables.

### 3. Staff accounts

Option A has no accounts. Option B has accounts with old passwords, which must be changed.

For each person, run one of these in Terminal. Paste the output into the SQL Editor, then run it. Replace the values in capitals and the email.

To create an account (Option A):

```bash
php -r 'echo "USE aquado;\nINSERT INTO users (fullname, email, password, role) VALUES (\"Admin\", \"admin@yourstation.test\", \"" . password_hash("CHOOSE-A-STRONG-PASSWORD", PASSWORD_DEFAULT) . "\", \"admin\");\n";' | pbcopy
```

To change an existing account's password (Option B):

```bash
php -r 'echo "USE aquado;\nUPDATE users SET password = \"" . password_hash("NEW-STRONG-PASSWORD", PASSWORD_DEFAULT) . "\" WHERE email = \"admin@yourstation.test\";\n";' | pbcopy
```

Roles are `admin`, `cashier`, `driver` and `customer`. Staff sign in at `/Operations/`. Customers register on the site.

### 4. Deploy on Vercel

1. **Import the repository.** In Vercel, choose **Add New → Project**, then import `Jing-Marie/Aquado`. Name the project `aquado`, so the site address is `aquado.vercel.app`.
2. **Set the Container preset.** The project must use the **Container** framework preset. If the site shows PHP code instead of pages, the project is still on the static preset. The dashboard may not show this option, so use the command line:

   ```bash
   npm install -g vercel
   ```

   ```bash
   vercel login
   ```

   ```bash
   vercel project update aquado --framework container --yes --scope YOUR-TEAM-SLUG
   ```

   `YOUR-TEAM-SLUG` is the name in your Vercel address (`vercel.com/YOUR-TEAM-SLUG/...`). If the project is on your personal account, use your account name.

   To confirm, run:

   ```bash
   vercel project inspect aquado --scope YOUR-TEAM-SLUG
   ```

   The output should show **Framework Preset: Container**.

3. **Region.** The repository's `vercel.json` sets `"regions": ["sin1"]`, which runs the app in Singapore next to the database. Don't remove it.
4. **Environment variables.** In **Settings → Environment Variables**, add these for **Production** only. Preview deployments shouldn't write to the live database.

   | Name | Value |
   |---|---|
   | `DB_HOST` | the host from step 1 |
   | `DB_PORT` | `4000` |
   | `DB_USER` | `PREFIX.root` (no quotes) |
   | `DB_PASS` | the generated password |
   | `DB_NAME` | `aquado` |
   | `DB_SSL` | `true` |

   Don't use Marketplace integrations for these. They create variables with other names.

5. **Deploy.** Each push to `main` deploys automatically. After changing the preset or the environment variables, redeploy from **Deployments**: open the latest deployment, choose **⋮**, then **Redeploy**, with the build cache turned off. A build takes about a minute. The newest deployment should show **Ready**, with region `sin1`.

### 5. Check the site

| Open | Expect |
|---|---|
| `https://aquado.vercel.app/` | Redirects to `/Finals/` and shows the page |
| `https://aquado.vercel.app/Finals/home.php` | The web page, not PHP code |
| `https://aquado.vercel.app/robots.txt` | Text starting with `User-agent: *` |
| `https://aquado.vercel.app/sitemap.xml` | A list of pages |
| `https://aquado.vercel.app/config.php` | Blocked (403) |
| `https://aquado.vercel.app/Operations/` | The staff sign-in page. A staff account signs in. |

To confirm the database is reached, sign in at `/Operations/` with a wrong password. The message should be "Invalid email or password", not "Database connection failed".

### Updating a site that is already live (database update of 2026-11-12)

The cancel order, notifications, proof of delivery and rider online/offline features need new database columns and one new table. **Run the update on the TiDB database first, then merge to `main` (Vercel redeploys on its own).** If the new code goes live first, pages show database errors until the update is run. The update only adds columns and a table; existing orders and accounts are untouched.

```bash
mysql --ssl-mode=REQUIRED -h HOST -P 4000 -u 'PREFIX.root' -p aquado < revisions_2026-11-12_cancel_proof_gps.sql
```

No password at hand? Open the instance's **SQL Editor** in the TiDB Cloud console, choose the `aquado` database, paste the contents of `revisions_2026-11-12_cancel_proof_gps.sql` and run it.

(Run it once. Running it twice fails on "duplicate column", which is harmless.) Delivery photos are stored in the database like the GCash photos, so nothing else is needed on Vercel.

### Troubleshooting

| Symptom | Fix |
|---|---|
| Errors about `notifications`, `cancelled_at`, `delivery_photo` or `is_online` | The 2026-11-12 database update above has not been run yet. |
| The site shows PHP code | The preset is not Container. Run the `vercel project update` command in step 4. |
| "Database connection failed" | Check that `DB_PORT` is `4000`, `DB_USER` is `PREFIX.root` and `DB_SSL` is `true`. Redeploy after changing variables. |
| Connection refused or times out | The TiDB public endpoint is off, or the `0.0.0.0/0` rule is missing. |
| Terminal says `mysql_native_password` cannot be loaded | Use the SQL Editor method in step 2. |
| SQL Editor: "Unknown database 'aquado'" | The first line creates the database. Make sure you copied the whole file with `pbcopy`. |
| SQL Editor: "Parsing sql failed" | You pasted a Terminal command. Paste only the SQL that `pbcopy` copied. |
| Pages take several seconds | Check that `vercel.json` has `"regions": ["sin1"]` and that the newest deployment shows `sin1`. |
| Staff are signed out at random | The `sessions` table is missing. Check that `SHOW TABLES` lists it. |
| Photo upload fails | Photos over 4.5 MB can't go through Vercel. The driver page shrinks photos first, so keep JavaScript turned on. |
| Connections are refused partway through a month | The free monthly quota is used up. It resets each month, or you can raise the quota in the TiDB console. |

### Notes

- **This repository is public.** Never commit database exports, login details or passwords. `aquado.sql` and `aquado_tidb_import.sql` are git-ignored; keep them that way.
- `DB_SSL=true` encrypts the connection, but it doesn't check the server's certificate.
- Login sessions and GCash payment photos are stored in the database. Each shrunk photo is about 150–600 KB.
- Vercel's Hobby plan is for personal, non-commercial use. Check Vercel's terms before using it for the business.
- PlanetScale is a paid alternative. Its smallest plan is about $39 a month, plus storage.
