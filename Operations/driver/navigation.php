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
    <title>Route & Navigation - Aquado Driver</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="../style.css?v=<?php echo ASSET_VERSION; ?>">
    <?php include __DIR__ . "/../includes/pwa_head.php"; ?>
</head>
<body>
<div class="driver-app">
    <div class="driver-header">
        <p class="driver-hierarchy">Field Operations Portal</p>
        <h2>Route & Navigation View</h2>
    </div>

    <div class="driver-content">
        <?php if ($current) { ?>
            <div class="card">
                <h4 style="color:var(--green-dark);"><?php echo count($stops); ?> stop<?php echo count($stops) === 1 ? "" : "s"; ?> on this trip</h4>
                <p style="font-size:14px;color:var(--gray-500);margin:8px 0;">Pick up empty containers and deliver filled ones in any order.</p>
            </div>

            <div class="nav-steps">
                <?php foreach ($stops as $i => $stop) {
                    $isPickup = $stop["status"] === "Accepted" && $stop["requires_pickup"];
                ?>
                    <div class="nav-step">
                        <div class="nav-step-num"><?php echo $i + 1; ?></div>
                        <div>
                            <strong><?php echo $isPickup ? "Pick up empty containers" : "Deliver"; ?> — <?php echo e($stop["customer_name"]); ?></strong>
                            <p style="font-size:13px;color:var(--gray-500);margin-top:4px;"><?php echo e($stop["customer_address"]); ?></p>
                            <?php if (!$isPickup) { ?>
                                <p style="font-size:13px;color:var(--gray-500);margin-top:4px;">Collect: <?php echo e($stop["payment_method"]); ?> — <?php echo formatPeso($stop["total"]); ?></p>
                            <?php } ?>
                            <p style="font-size:13px;margin-top:6px;">
                                <a href="https://maps.google.com/?q=<?php echo urlencode($stop["customer_address"]); ?>" target="_blank"><i class="fa-solid fa-location-arrow"></i> Google Maps</a>
                                &nbsp;·&nbsp;
                                <a href="order.php?id=<?php echo (int)$stop["id"]; ?>">Order Detail</a>
                            </p>
                        </div>
                    </div>
                <?php } ?>
            </div>
        <?php } else { ?>
            <div class="empty-state">
                <i class="fa-solid fa-location-arrow"></i>
                <p>No active navigation. Start a delivery first.</p>
                <a href="index.php" class="btn btn-primary" style="margin-top:16px;">Delivery Queue</a>
            </div>
        <?php } ?>
    </div>

    <nav class="driver-bottom-nav">
        <a href="index.php"><i class="fa-solid fa-house"></i> Dashboard</a>
        <a href="routes.php"><i class="fa-solid fa-map"></i> Route Map</a>
        <a href="navigation.php" class="active"><i class="fa-solid fa-location-arrow"></i> Navigation</a>
        <a href="order.php?id=<?php echo $current ? (int)$current["id"] : 0; ?>"><i class="fa-solid fa-clipboard-check"></i> Order</a>
    </nav>
</div>
</body>
</html>
