<?php
include "../connections.php";
include "../includes/auth.php";

requireRole(["admin", "cashier"], "../login.php");
$user = loadCurrentUser($conn);

$todayOrders = $conn->query("SELECT COUNT(*) as cnt FROM orders WHERE DATE(created_at) = CURDATE()")->fetch_assoc()["cnt"];
$activeRiders = $conn->query("SELECT COUNT(DISTINCT driver_id) as cnt FROM orders WHERE status IN ('Accepted','Picked Up','Ready for Delivery','Out for Delivery') AND driver_id IS NOT NULL")->fetch_assoc()["cnt"];
$pendingDispatches = $conn->query("SELECT COUNT(*) as cnt FROM orders WHERE status IN ('Pending','Confirmed')")->fetch_assoc()["cnt"];
$inventory = $conn->query("SELECT * FROM container_inventory ORDER BY id ASC");

// Sales statistics (revenue = payments confirmed by the cashier)
$todaySales = $conn->query("SELECT COALESCE(SUM(total), 0) AS total FROM orders WHERE payment_status = 'Paid' AND DATE(paid_at) = CURDATE()")->fetch_assoc()["total"];
$totalRevenue = $conn->query("SELECT COALESCE(SUM(total), 0) AS total FROM orders WHERE payment_status = 'Paid'")->fetch_assoc()["total"];
$deliveredToday = $conn->query("SELECT COUNT(DISTINCT order_id) AS cnt FROM delivery_tracking WHERE status = 'Delivered' AND DATE(created_at) = CURDATE()")->fetch_assoc()["cnt"];
$toConfirm = $conn->query("SELECT COUNT(*) AS cnt, COALESCE(SUM(total), 0) AS total FROM orders WHERE payment_status = 'For Verification'")->fetch_assoc();

// Confirmed sales for each of the last 7 days
$salesByDay = [];
for ($i = 6; $i >= 0; $i--) {
    $salesByDay[date("Y-m-d", strtotime("-$i days"))] = 0;
}
$dailySales = $conn->query("
    SELECT DATE(paid_at) AS day, SUM(total) AS total
    FROM orders
    WHERE payment_status = 'Paid' AND paid_at >= CURDATE() - INTERVAL 6 DAY
    GROUP BY DATE(paid_at)
");
while ($row = $dailySales->fetch_assoc()) {
    $salesByDay[$row["day"]] = (float)$row["total"];
}
$maxDaySales = max(1, max($salesByDay));

$adminPage = "dashboard";
$cssPath = "../style.css";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Master Dashboard - Aquado</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="../style.css?v=<?php echo time(); ?>">
    <?php include __DIR__ . "/../includes/pwa_head.php"; ?>
</head>
<body>
<div class="admin-layout">
    <?php include "includes/sidebar.php"; ?>

    <main class="admin-main">
        <div class="admin-header-bar">
            <div>
                <p class="page-subtitle" style="text-transform:uppercase;letter-spacing:1px;font-size:12px;">Station Management Portal</p>
                <h2>Master Dashboard</h2>
            </div>
            <span style="color:var(--gray-500);"><?php echo e($user["fullname"]); ?></span>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <h4>Total of Today's Orders</h4>
                <div class="stat-value"><?php echo (int)$todayOrders; ?></div>
            </div>
            <div class="stat-card">
                <h4>Active Riders</h4>
                <div class="stat-value blue"><?php echo (int)$activeRiders; ?></div>
            </div>
            <div class="stat-card">
                <h4>Pending Dispatches</h4>
                <div class="stat-value" style="color:var(--orange-accent);"><?php echo (int)$pendingDispatches; ?></div>
            </div>
            <div class="stat-card">
                <h4>Delivered Today</h4>
                <div class="stat-value"><?php echo (int)$deliveredToday; ?></div>
            </div>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <h4>Today's Sales</h4>
                <div class="stat-value"><?php echo formatPeso($todaySales); ?></div>
            </div>
            <div class="stat-card">
                <h4>Total Revenue</h4>
                <div class="stat-value blue"><?php echo formatPeso($totalRevenue); ?></div>
            </div>
            <a href="payments.php" class="stat-card" style="display:block;">
                <h4>Payments to Confirm</h4>
                <div class="stat-value" style="color:var(--orange-accent);"><?php echo (int)$toConfirm["cnt"]; ?></div>
                <p style="font-size:13px;color:var(--gray-500);margin-top:8px;"><?php echo formatPeso($toConfirm["total"]); ?> from riders →</p>
            </a>
        </div>

        <div class="chart-placeholder" style="margin:0 0 32px;">
            <h4 style="color:var(--green-dark);margin-bottom:16px;">Sales – Last 7 Days</h4>
            <div class="chart-bars" style="padding-bottom:24px;">
                <?php foreach ($salesByDay as $day => $amount) { ?>
                    <div class="chart-bar" style="height:<?php echo max(4, round($amount / $maxDaySales * 100)); ?>%;" title="<?php echo e(date("M d", strtotime($day)) . ": " . formatPeso($amount)); ?>">
                        <em style="position:absolute;top:-20px;left:50%;transform:translateX(-50%);font-size:11px;font-style:normal;color:var(--green-dark);white-space:nowrap;"><?php echo $amount > 0 ? formatPeso($amount) : ""; ?></em>
                        <span><?php echo date("D", strtotime($day)); ?></span>
                    </div>
                <?php } ?>
            </div>
        </div>

        <div class="card" style="margin-top:8px;">
            <h3 style="color:var(--green-dark);margin-bottom:4px;">Container Inventory</h3>
            <p class="page-subtitle">Current stock levels at the station</p>
            <div class="inventory-mini-grid">
                <?php while ($inv = $inventory->fetch_assoc()) { ?>
                    <div class="inventory-mini-card">
                        <h5><?php echo e($inv["container_type"]); ?></h5>
                        <div class="inv-val"><?php echo (int)$inv["total_count"]; ?></div>
                        <div class="inv-detail">Filled: <?php echo (int)$inv["filled_count"]; ?> · Empty: <?php echo (int)$inv["empty_count"]; ?></div>
                    </div>
                <?php } ?>
            </div>
            <a href="inventory.php" class="btn btn-sm btn-primary" style="margin-top:16px;">Manage Inventory</a>
        </div>

        <div style="margin-top:24px;">
            <a href="orders.php" class="btn btn-dark btn-lg">Go to Order Management & Dispatching →</a>
        </div>
    </main>

    <div class="admin-decor">
        <div class="shape-teal"></div>
        <div class="shape-orange"></div>
    </div>
</div>
</body>
</html>
