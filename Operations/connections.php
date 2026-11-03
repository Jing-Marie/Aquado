<?php

/*
 * Same database as the customer website (config.php)
 * so orders and statuses stay in sync between both apps.
 */
require_once __DIR__ . "/../db.php";


try {

    $conn = aquadoConnect();

} catch (mysqli_sql_exception $e) {

    // Don't show database details to visitors.
    error_log("Database connection failed: " . $e->getMessage());

    die("Database connection failed. Please check the database settings in config.php.");

}


// PHP and MySQL both use Philippine time (XAMPP's PHP defaults to Europe/Berlin).
date_default_timezone_set("Asia/Manila");

$conn->query("SET time_zone = '+08:00'");
