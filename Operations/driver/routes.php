<?php
include "../connections.php";
include "../includes/auth.php";

requireRole("driver", "../login.php");
$user = loadCurrentUser($conn);
$driverId = currentUserId();

// Stops the driver still has to visit: pick up empties, or deliver.
$activeOrder = $conn->prepare("SELECT * FROM orders WHERE driver_id = ? AND status IN ('Accepted', 'Out for Delivery') ORDER BY created_at ASC");
$activeOrder->bind_param("i", $driverId);
$activeOrder->execute();
$stops = $activeOrder->get_result()->fetch_all(MYSQLI_ASSOC);
$activeOrder->close();
$current = $stops[0] ?? null;

$cssPath = "../style.css";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Route Map View - Aquado Driver</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="../style.css?v=<?php echo ASSET_VERSION; ?>">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <?php include __DIR__ . "/../includes/pwa_head.php"; ?>
</head>
<body>
<div class="driver-app">
    <div class="driver-header">
        <p class="driver-hierarchy">Field Operations Portal</p>
        <h2>Route Map View</h2>
    </div>

    <div class="driver-content">
        <?php if ($current) { ?>
            <!-- LIVE MAP -->
            <div class="map-frame">
                <div id="map"></div>
                <div id="map-status">Getting your location...</div>
            </div>

            <?php foreach ($stops as $i => $stop) {
                $isPickup = $stop["status"] === "Accepted" && $stop["requires_pickup"];
            ?>
                <div class="card">
                    <p style="font-weight:600;color:var(--green-dark);">Stop <?php echo $i + 1; ?> · <?php echo e($stop["customer_name"]); ?></p>
                    <p style="font-size:13px;color:var(--gray-500);margin:4px 0 8px;"><?php echo e($stop["customer_address"]); ?></p>
                    <p style="font-size:14px;"><strong><?php echo $isPickup ? "Pick up empty containers" : "Deliver"; ?></strong></p>
                    <p style="font-size:14px;"><strong>Payment:</strong> <?php echo e($stop["payment_method"]); ?> · <?php echo formatPeso($stop["total"]); ?></p>
                    <a href="order.php?id=<?php echo (int)$stop["id"]; ?>" class="btn btn-dark btn-block" style="margin-top:10px;">Order Detail & Proof of Delivery</a>
                </div>
            <?php } ?>
            <a href="navigation.php" class="btn btn-primary btn-block btn-lg">Route & Navigation View →</a>
        <?php } else { ?>
            <div class="empty-state">
                <i class="fa-solid fa-map"></i>
                <p>No active route. Start a delivery from the dashboard.</p>
                <a href="index.php" class="btn btn-primary" style="margin-top:16px;">Delivery Queue</a>
            </div>
        <?php } ?>
    </div>

    <nav class="driver-bottom-nav">
        <a href="index.php"><i class="fa-solid fa-house"></i> Dashboard</a>
        <a href="routes.php" class="active"><i class="fa-solid fa-map"></i> Route Map</a>
        <a href="navigation.php"><i class="fa-solid fa-location-arrow"></i> Navigation</a>
        <a href="order.php?id=<?php echo $current ? (int)$current["id"] : 0; ?>"><i class="fa-solid fa-clipboard-check"></i> Order</a>
    </nav>
</div>

<?php if ($current) { ?>
<script>
const ROUTE_STOPS = <?php echo json_encode(array_map(function ($stop) {
    return [
        "name" => $stop["customer_name"],
        "address" => $stop["customer_address"],
        "task" => ($stop["status"] === "Accepted" && $stop["requires_pickup"]) ? "Pick up empty containers" : "Deliver",
    ];
}, $stops)); ?>;
</script>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="js/share_location.js?v=<?php echo ASSET_VERSION; ?>"></script>
<script src="js/routes.js?v=<?php echo ASSET_VERSION; ?>"></script>
<?php } ?>
</body>
</html>
