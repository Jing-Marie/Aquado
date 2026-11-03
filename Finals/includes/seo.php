<?php

/*
|--------------------------------------------------------------------------
| SEARCH ENGINE HELPERS
|--------------------------------------------------------------------------
|
| URLs are built from the current request, so the same code works on
| localhost and on the live domain without any changes.
|
*/

function siteScheme() {

    $https = (!empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off")
        || ($_SERVER["HTTP_X_FORWARDED_PROTO"] ?? "") === "https"
        || ($_SERVER["SERVER_PORT"] ?? "") == 443;

    return $https ? "https" : "http";

}


/*
 * Absolute URL of a web path, e.g. absoluteUrl("/Finals/home.php").
 */
function absoluteUrl($path) {

    return siteScheme() . "://" . $_SERVER["HTTP_HOST"] . $path;

}


/*
 * Absolute URL of the current page without its query string.
 */
function currentPageUrl() {

    return absoluteUrl(strtok($_SERVER["REQUEST_URI"], "?"));

}


/*
 * Absolute URL of a file in the same folder as the current page.
 */
function siblingUrl($file) {

    $dir = rtrim(str_replace("\\", "/", dirname($_SERVER["SCRIPT_NAME"])), "/");

    return absoluteUrl($dir . "/" . $file);

}


/*
 * Google business listing details (shown in search results and Maps).
 */
function localBusinessJsonLd() {

    $data = [
        "@context" => "https://schema.org",
        "@type" => "LocalBusiness",
        "name" => "Aquado Purified Water Refilling Station",
        "description" => "Purified drinking water refilling station with home delivery, container swap, GCash and cash on delivery.",
        "url" => siblingUrl("home.php"),
        "image" => siblingUrl("assets/icons/og-image.jpg"),
        "logo" => siblingUrl("assets/icons/icon-192.png"),
        "email" => "TagapoAquado@gmail.com",
        "telephone" => "+63 922 811 2567",
        "priceRange" => "₱",
        "address" => [
            "@type" => "PostalAddress",
            "streetAddress" => "143 Rizal Boulevard, Barangay Tagapo",
            "addressLocality" => "Santa Rosa",
            "addressRegion" => "Laguna",
            "postalCode" => "4026",
            "addressCountry" => "PH",
        ],
        "areaServed" => "Santa Rosa, Laguna",
        "openingHoursSpecification" => [
            [
                "@type" => "OpeningHoursSpecification",
                "dayOfWeek" => ["Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"],
                "opens" => "07:00",
                "closes" => "20:00",
            ],
            [
                "@type" => "OpeningHoursSpecification",
                "dayOfWeek" => "Sunday",
                "opens" => "08:00",
                "closes" => "17:00",
            ],
        ],
    ];

    return json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG);

}
