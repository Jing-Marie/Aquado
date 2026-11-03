<?php
include "../connections.php";
include "../includes/auth.php";

requireRole(["admin", "cashier"], "../login.php");
$user = loadCurrentUser($conn);

// Revenue = payments confirmed by the cashier
$paid = $conn->query("SELECT COALESCE(SUM(total), 0) as total, COUNT(*) as cnt FROM orders WHERE payment_status = 'Paid'")->fetch_assoc();
$totalSales = $paid["total"];
$totalOrders = $conn->query("SELECT COUNT(*) as cnt FROM orders WHERE status != 'Cancelled'")->fetch_assoc()["cnt"];
$avgOrder = $paid["cnt"] > 0 ? $totalSales / $paid["cnt"] : 0;

// Confirmed sales for each of the last 6 months
$salesByMonth = [];
for ($i = 5; $i >= 0; $i--) {
    $salesByMonth[date("Y-m", strtotime("first day of -$i months"))] = 0;
}
$trend = $conn->query("
    SELECT DATE_FORMAT(paid_at, '%Y-%m') AS month, SUM(total) AS total
    FROM orders
    WHERE payment_status = 'Paid'
    GROUP BY DATE_FORMAT(paid_at, '%Y-%m')
");
while ($row = $trend->fetch_assoc()) {
    if (isset($salesByMonth[$row["month"]])) {
        $salesByMonth[$row["month"]] = (float)$row["total"];
    }
}
$maxMonthSales = max(1, max($salesByMonth));

$monthlySales = $conn->query("
    SELECT DATE_FORMAT(created_at, '%Y-%m') as month, SUM(total) as sales, COUNT(*) as orders
    FROM orders WHERE status != 'Cancelled'
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ORDER BY month DESC LIMIT 6
");

$paymentBreakdown = $conn->query("
    SELECT payment_method, COUNT(*) as cnt, SUM(total) as total
    FROM orders WHERE status != 'Cancelled'
    GROUP BY payment_method
");

$adminPage = "reports";
$cssPath = "../style.css";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales Reports - Aquado</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="../style.css?v=<?php echo ASSET_VERSION; ?>">
    <?php include __DIR__ . "/../includes/pwa_head.php"; ?>
</head>
<body>
<div class="admin-layout">
    <?php include "includes/sidebar.php"; ?>

    <main class="admin-main">
        <div class="admin-header-bar">
            <h2>Sales & Reports</h2>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <h4>Total Revenue</h4>
                <div class="stat-value blue"><?php echo formatPeso($totalSales); ?></div>
            </div>
            <div class="stat-card">
                <h4>Total Orders</h4>
                <div class="stat-value"><?php echo (int)$totalOrders; ?></div>
            </div>
            <div class="stat-card">
                <h4>Average Order Value</h4>
                <div class="stat-value"><?php echo formatPeso($avgOrder); ?></div>
            </div>
        </div>

        <h3 style="color:var(--green-dark);margin:24px 0 16px;">Monthly Sales</h3>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Month</th>
                    <th>Orders</th>
                    <th>Revenue</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $monthlySales->fetch_assoc()) { ?>
                    <tr>
                        <td><?php echo e($row["month"]); ?></td>
                        <td><?php echo (int)$row["orders"]; ?></td>
                        <td><?php echo formatPeso($row["sales"]); ?></td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>

        <h3 style="color:var(--green-dark);margin:24px 0 16px;">Payment Method Breakdown</h3>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Method</th>
                    <th>Orders</th>
                    <th>Revenue</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $paymentBreakdown->fetch_assoc()) { ?>
                    <tr>
                        <td><?php echo e($row["payment_method"]); ?></td>
                        <td><?php echo (int)$row["cnt"]; ?></td>
                        <td><?php echo formatPeso($row["total"]); ?></td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>

        <div class="chart-placeholder">
            <h4 style="color:var(--green-dark);margin-bottom:16px;">Sales Trend (Last 6 Months)</h4>
            <div class="chart-bars" style="padding-bottom:24px;">
                <?php foreach ($salesByMonth as $month => $amount) { ?>
                    <div class="chart-bar" style="height:<?php echo max(4, round($amount / $maxMonthSales * 100)); ?>%;" title="<?php echo e(date("F Y", strtotime($month . "-01")) . ": " . formatPeso($amount)); ?>">
                        <em style="position:absolute;top:-20px;left:50%;transform:translateX(-50%);font-size:11px;font-style:normal;color:var(--green-dark);white-space:nowrap;"><?php echo $amount > 0 ? formatPeso($amount) : ""; ?></em>
                        <span><?php echo date("M", strtotime($month . "-01")); ?></span>
                    </div>
                <?php } ?>
            </div>
        </div>
    </main>

    <div class="admin-decor">
        <div class="shape-teal"></div>
        <div class="shape-orange"></div>
    </div>
</div>
</body>
</html>
