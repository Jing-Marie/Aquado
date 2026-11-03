<?php

/*
 * Served as /sitemap.xml (see .htaccess). Lists the public pages of the
 * customer website for search engines.
 */

require_once __DIR__ . "/Finals/includes/seo.php";

header("Content-Type: application/xml; charset=UTF-8");

$root = rtrim(str_replace("\\", "/", dirname($_SERVER["SCRIPT_NAME"])), "/");

$pages = [
    "home.php" => "1.0",
    "register.php" => "0.6",
    "login.php" => "0.5",
];

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

foreach ($pages as $page => $priority) {
    echo "  <url>\n";
    echo "    <loc>" . htmlspecialchars(absoluteUrl("$root/Finals/$page")) . "</loc>\n";
    echo "    <lastmod>" . date("Y-m-d", filemtime(__DIR__ . "/Finals/$page")) . "</lastmod>\n";
    echo "    <priority>$priority</priority>\n";
    echo "  </url>\n";
}

echo "</urlset>\n";
