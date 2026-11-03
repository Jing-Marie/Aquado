<?php

/*
|--------------------------------------------------------------------------
| DATABASE SETTINGS (used by both the customer website and the Operations app)
|--------------------------------------------------------------------------
|
| Each value is read from an environment variable when it is set (Vercel
| project settings, `docker run -e`), so no passwords live in this file.
| Without them, the defaults below are used (local MySQL on this computer).
|
| On a host where you edit this file directly (e.g. InfinityFree: "MySQL
| Databases" page), replace the defaults with the MySQL details from the
| control panel.
|
| DB_PORT is 3306 unless the host says otherwise (TiDB Cloud uses 4000).
| DB_SSL=true encrypts the connection. Hosted MySQL services such as
| TiDB Cloud and PlanetScale require it.
|
*/

define("DB_HOST", getenv("DB_HOST") ?: "localhost");
define("DB_PORT", (int)(getenv("DB_PORT") ?: 3306));
define("DB_USER", getenv("DB_USER") ?: "root");
define("DB_PASS", getenv("DB_PASS") ?: "");
define("DB_NAME", getenv("DB_NAME") ?: "aquado");
define("DB_SSL", getenv("DB_SSL") === "true");
