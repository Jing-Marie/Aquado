<?php
include "../connections.php";
include "../includes/auth.php";

requireRole("driver", "../login.php");
$driverId = currentUserId();

$history = $conn->prepare("SELECT * FROM orders WHERE driver_id = ? AND status = 'Delivered' ORDER BY created_at DESC LIMIT 20");
$history->bind_param("i", $driverId);
$history->execute();
$result = $history->get_result();
$history->close();

$pageTitle = "History";
$cssPath = "../style.css";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delivery History - Aquado Driver</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="../style.css?v=<?php echo ASSET_VERSION; ?>">
    <?php include __DIR__ . "/../includes/pwa_head.php"; ?>
</head>
<body>
<div class="driver-app">
    <div class="driver-header">
        <h2>DELIVERY HISTORY</h2>
    </div>

    <div class="driver-content">
        <?php if (isset($_GET["completed"])) { ?>
            <div class="success-message">Delivery completed successfully!</div>
        <?php } ?>

        <?php if ($result->num_rows === 0) { ?>
            <div class="empty-state">
                <i class="fa-solid fa-clock-rotate-left"></i>
                <p>No completed deliveries yet.</p>
            </div>
        <?php } else { ?>
            <?php while ($order = $result->fetch_assoc()) { ?>
                <div class="assignment-card">
                    <div class="assignment-avatar" style="background:var(--green-accent);color:white;">
                        <i class="fa-solid fa-check"></i>
                    </div>
                    <div class="assignment-info">
                        <h4><a href="order.php?id=<?php echo (int)$order["id"]; ?>"><?php echo e($order["customer_name"]); ?></a></h4>
                        <span style="font-size:13px;color:var(--gray-500);"><?php echo e($order["order_number"]); ?></span>
                        <span style="font-size:12px;color:var(--green-accent);font-weight:600;">Delivered · <?php echo formatPeso($order["total"]); ?> · <?php echo e($order["payment_method"]); ?></span>
                        <span class="status-badge <?php echo paymentBadgeClass($order["payment_status"]); ?>" style="display:inline-block;margin-top:4px;"><?php echo e($order["payment_status"]); ?></span>
                    </div>
                </div>
            <?php } ?>
        <?php } ?>
    </div>

    <nav class="driver-bottom-nav">
        <a href="index.php"><i class="fa-solid fa-house"></i> Dashboard</a>
        <a href="routes.php"><i class="fa-solid fa-map"></i> Routes</a>
        <a href="history.php" class="active"><i class="fa-solid fa-clock-rotate-left"></i> History</a>
        <a href="account.php"><i class="fa-solid fa-user"></i> Account</a>
    </nav>
</div>
</body>
</html>
