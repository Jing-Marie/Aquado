<?php

/*
 * Served as /robots.txt (see .htaccess). Keeps the staff app and payment
 * photos out of search engines and points them to the sitemap.
 */

require_once __DIR__ . "/Finals/includes/seo.php";

header("Content-Type: text/plain; charset=UTF-8");

$root = rtrim(str_replace("\\", "/", dirname($_SERVER["SCRIPT_NAME"])), "/");

echo "User-agent: *\n";
echo "Disallow: $root/Operations/\n";
echo "Disallow: $root/uploads/\n";
echo "Allow: $root/\n";
echo "\n";
echo "Sitemap: " . absoluteUrl("$root/sitemap.xml") . "\n";
