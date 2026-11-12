<?php
include "../connections.php";
include "../includes/auth.php";
include "../includes/workflow.php";

requireRole("driver", "../login.php");
$user = loadCurrentUser($conn);
$driverId = currentUserId();

// Delivery just started: show the confirmation modal for that order.
$activeDelivery = null;
if (isset($_GET["started"])) {
    $startedId = (int)$_GET["started"];
    $latest = $conn->prepare("SELECT * FROM orders WHERE id = ? AND driver_id = ? AND status = 'Out for Delivery'");
    $latest->bind_param("ii", $startedId, $driverId);
    $latest->execute();
    $activeDelivery = $latest->get_result()->fetch_assoc();
    $latest->close();
}
$showModal = (bool)$activeDelivery;

// Every order assigned to this driver that is not finished yet; a driver can work several at once.
$assignments = $conn->prepare("SELECT * FROM orders WHERE driver_id = ? AND status IN ('" . implode("','", ACTIVE_DRIVER_STATUSES) . "') ORDER BY created_at ASC");
$assignments->bind_param("i", $driverId);
$assignments->execute();
$result = $assignments->get_result();
$assignments->close();

$itemsByOrder = [];
$items = $conn->prepare("
    SELECT oi.order_id, oi.product_name, oi.quantity
    FROM order_items oi
    JOIN orders o ON o.id = oi.order_id
    WHERE o.driver_id = ? AND o.status IN ('" . implode("','", ACTIVE_DRIVER_STATUSES) . "')
");
$items->bind_param("i", $driverId);
$items->execute();
foreach ($items->get_result()->fetch_all(MYSQLI_ASSOC) as $item) {
    $itemsByOrder[$item["order_id"]][] = (int)$item["quantity"] . " × " . $item["product_name"];
}
$items->close();

// GCash photos the cashier rejected; the driver needs to send a new one.
$rejected = $conn->prepare("SELECT id, order_number, customer_name FROM orders WHERE driver_id = ? AND payment_status = 'Rejected'");
$rejected->bind_param("i", $driverId);
$rejected->execute();
$rejectedPayments = $rejected->get_result()->fetch_all(MYSQLI_ASSOC);
$rejected->close();

$isOnline = isDriverOnline($conn, $driverId);

$pageTitle = "Driver Dashboard";
$cssPath = "../style.css";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Driver Dashboard - Aquado</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="../style.css?v=<?php echo ASSET_VERSION; ?>">
    <?php include __DIR__ . "/../includes/pwa_head.php"; ?>
</head>
<body>
<div class="driver-app">
    <div class="driver-header">
        <p class="driver-hierarchy">Field Operations Portal</p>
        <div class="logo-area">
            <img src="../assets/logo.svg" class="logo-img" alt="Aquado">
            <div class="logo-text">
                <h1>AQUADO</h1>
                <span>Purified Drinking Water, Santa Rosa, Laguna</span>
            </div>
        </div>
        <p class="driver-greeting">Hello, <?php echo e(explode(" ", $user["fullname"])[0]); ?>!</p>
        <h2>Delivery Queue / Rider Dashboard</h2>
    </div>

    <div class="driver-content">
        <!-- ONLINE / OFFLINE: going Offline stops sharing the GPS location -->
        <form method="POST" action="set_status.php" class="online-switch <?php echo $isOnline ? "is-online" : "is-offline"; ?>" id="onlineForm">
            <input type="hidden" name="online" value="<?php echo $isOnline ? "0" : "1"; ?>">
            <input type="hidden" name="return" value="index">
            <div class="online-switch-text">
                <strong><i class="fa-solid fa-circle"></i> You are <?php echo $isOnline ? "Online" : "Offline"; ?></strong>
                <span>
                    <?php if ($isOnline) { ?>
                        Your location is shared with customers while you have an active delivery.
                    <?php } else { ?>
                        Location sharing is off. Go online to share your location again.
                    <?php } ?>
                </span>
            </div>
            <button type="submit" class="btn btn-sm <?php echo $isOnline ? "btn-secondary" : "btn-primary"; ?>"><?php echo $isOnline ? "Go Offline" : "Go Online"; ?></button>
        </form>

        <h3>Daily Assignments</h3>

        <?php if (isset($_GET["error"])) { ?>
            <div class="error-message">That order can't be updated right now. Its status may have changed.</div>
        <?php } ?>

        <?php foreach ($rejectedPayments as $rp) { ?>
            <div class="error-message">
                GCash photo for <?php echo e($rp["order_number"]); ?> (<?php echo e($rp["customer_name"]); ?>) was rejected.
                <a href="order.php?id=<?php echo (int)$rp["id"]; ?>"><strong>Send a new photo</strong></a>
            </div>
        <?php } ?>

        <?php if ($result->num_rows === 0) { ?>
            <div class="empty-state">
                <i class="fa-solid fa-check-circle"></i>
                <p>No pending deliveries today.</p>
            </div>
        <?php } else { ?>
            <?php while ($order = $result->fetch_assoc()) {
                $initial = strtoupper(substr($order["customer_name"], 0, 1));
                [$action, $label] = driverButton($order);
            ?>
                <div class="assignment-card">
                    <div class="assignment-avatar"><?php echo e($initial); ?></div>
                    <div class="assignment-info">
                        <h4><a href="order.php?id=<?php echo (int)$order["id"]; ?>"><?php echo e($order["customer_name"]); ?></a></h4>
                        <span style="font-size:13px;color:var(--gray-500);display:block;"><?php echo e($order["order_number"]); ?> · <?php echo e($order["customer_address"]); ?></span>
                        <span style="font-size:13px;display:block;"><?php echo e(implode(", ", $itemsByOrder[$order["id"]] ?? [])); ?></span>
                        <span class="status-pending">Status: <?php echo e($order["status"]); ?></span>
                    </div>
                </div>
                <?php if ($action === "done") { ?>
                    <!-- Completing a delivery goes through the payment confirmation screen. -->
                    <a href="order.php?id=<?php echo (int)$order["id"]; ?>" class="btn btn-dark btn-block btn-lg" style="margin-bottom:20px;"><?php echo e($label); ?></a>
                <?php } else { ?>
                    <form method="POST" action="update_status.php">
                        <input type="hidden" name="order_id" value="<?php echo (int)$order["id"]; ?>">
                        <input type="hidden" name="action" value="<?php echo e($action); ?>">
                        <button type="submit" class="btn btn-primary btn-block btn-lg" style="margin-bottom:20px;" <?php echo $action === null ? "disabled" : ""; ?>><?php echo e($label); ?></button>
                    </form>
                <?php } ?>
            <?php } ?>
        <?php } ?>
    </div>

    <?php if ($showModal && $activeDelivery) { ?>
        <div class="modal-overlay" id="deliveryModal">
            <div class="modal-card">
                <button class="modal-close" onclick="document.getElementById('deliveryModal').style.display='none'">&times;</button>
                <div class="modal-icon"><i class="fa-solid fa-droplet"></i></div>
                <h3>Delivery Started to <?php echo e($activeDelivery["customer_name"]); ?></h3>
                <div class="modal-actions">
                    <a href="order.php?id=<?php echo (int)$activeDelivery["id"]; ?>" class="btn btn-dark btn-block">Done / Delivered</a>
                    <form method="POST" action="update_status.php">
                        <input type="hidden" name="order_id" value="<?php echo (int)$activeDelivery["id"]; ?>">
                        <input type="hidden" name="action" value="undo_deliver">
                        <button type="submit" class="btn btn-secondary btn-block">Cancel (Wrong selection)</button>
                    </form>
                    <a href="index.php" class="btn btn-secondary btn-block">Close</a>
                </div>
            </div>
        </div>
    <?php } ?>

    <nav class="driver-bottom-nav">
        <a href="index.php" class="active"><i class="fa-solid fa-house"></i> Dashboard</a>
        <a href="routes.php"><i class="fa-solid fa-map"></i> Route Map</a>
        <a href="navigation.php"><i class="fa-solid fa-location-arrow"></i> Navigation</a>
        <a href="account.php"><i class="fa-solid fa-user"></i> Account</a>
    </nav>
</div>

<?php if ($result->num_rows > 0 && $isOnline) { ?>
    <script src="js/share_location.js?v=<?php echo ASSET_VERSION; ?>"></script>
    <script>startSharingLocation();</script>
<?php } ?>
<?php if ($isOnline && $result->num_rows > 0) { ?>
<script>
// Warn before going offline in the middle of a delivery.
document.getElementById("onlineForm").addEventListener("submit", function(event) {
    if (!confirm("You still have active deliveries. If you go offline, customers can no longer see your location. Go offline?")) {
        event.preventDefault();
    }
});
</script>
<?php } ?>
</body>
</html>
