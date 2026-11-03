<?php

include "../connections.php";
include "../includes/auth.php";

requireRole(["admin", "cashier"], "../login.php");

$adminPage = "driver_locations";


if (isset($_GET["ajax"]) && $_GET["ajax"] === "locations") {

    header("Content-Type: application/json");

    // Drivers who have accepted orders they are still working on.
    $sql = "
        SELECT
            o.id AS order_id,
            o.order_number,
            o.customer_name,
            o.customer_address,
            o.status,

            u.id AS driver_id,
            u.fullname AS driver_name,
            u.contact_number,

            dl.latitude,
            dl.longitude,
            dl.last_updated

        FROM orders o

        INNER JOIN users u
            ON o.driver_id = u.id

        LEFT JOIN driver_locations dl
            ON dl.driver_id = u.id

        WHERE o.status IN ('Accepted', 'Picked Up', 'Ready for Delivery', 'Out for Delivery')
          AND u.role = 'driver'

        ORDER BY u.fullname ASC, o.created_at ASC
    ";

    $result = $conn->query($sql);

    // One entry per driver, with all of their active orders.
    $drivers = [];

    if ($result) {

        while ($row = $result->fetch_assoc()) {

            $id = (int)$row["driver_id"];

            if (!isset($drivers[$id])) {

                $drivers[$id] = [
                    "driver_id" => $id,
                    "driver_name" => $row["driver_name"],
                    "contact_number" => $row["contact_number"],

                    "latitude" =>
                        $row["latitude"] !== null
                            ? (float)$row["latitude"]
                            : null,

                    "longitude" =>
                        $row["longitude"] !== null
                            ? (float)$row["longitude"]
                            : null,

                    "last_updated" => $row["last_updated"],

                    "orders" => []
                ];
            }

            $drivers[$id]["orders"][] = [
                "order_number" => $row["order_number"],
                "customer_name" => $row["customer_name"],
                "customer_address" => $row["customer_address"],
                "status" => $row["status"]
            ];
        }
    }

    echo json_encode([
        "success" => true,
        "drivers" => array_values($drivers)
    ]);

    exit;
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Driver Locations - Aquado</title>

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"
    >

    <link
        rel="stylesheet"
        href="../style.css?v=<?php echo time(); ?>"
    >

    <link
        rel="stylesheet"
        href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
    >

    <!-- DRIVER LOCATION CSS -->
    <link
        rel="stylesheet"
        href="css/driver_locations.css?v=<?php echo time(); ?>"
    >

    <?php include __DIR__ . "/../includes/pwa_head.php"; ?>
</head>

<body>

<div class="admin-app">

    <?php include "includes/sidebar.php"; ?>

    <main class="admin-main">

        <div class="driver-location-page">

            <div class="location-header">

                <h2>
                    <i class="fa-solid fa-map-location-dot"></i>
                    Driver Locations
                </h2>

                <p>
                    Monitor the live location of drivers currently delivering orders.
                </p>

            </div>


            <div class="location-layout">


                <!-- MAP -->

                <div class="location-map-card">

                    <div class="map-top-info">

                        <strong>
                            <i class="fa-solid fa-location-dot"></i>
                            Live Driver Map
                        </strong>

                        <span id="mapStatus">
                            Updating...
                        </span>

                    </div>

                    <div id="driverMap"></div>

                </div>


                <!-- DRIVER LIST -->

                <div>

                    <div
                        id="driverList"
                        class="driver-list"
                    >

                        <div class="empty-drivers">

                            <i class="fa-solid fa-spinner fa-spin"></i>

                            <p>
                                Loading drivers...
                            </p>

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </main>

</div>


<script
    src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js">
</script>

<!-- DRIVER LOCATION JS -->
<script
    src="js/driver_locations.js?v=<?php echo time(); ?>">
</script>

</body>

</html>