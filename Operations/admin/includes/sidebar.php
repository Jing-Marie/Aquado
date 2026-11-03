<?php
$adminPage = $adminPage ?? "dashboard";

$paymentsToConfirm = (int)$conn->query("SELECT COUNT(*) AS cnt FROM orders WHERE payment_status = 'For Verification'")->fetch_assoc()["cnt"];
?>
<div class="admin-sidebar">
    <div class="logo-area">
        <img src="../assets/logo.svg" class="logo-img" alt="Aquado">
        <div class="logo-text">
            <h1>AQUADO</h1>
            <span>Station Management · <?php echo e(ucfirst(currentRole())); ?></span>
        </div>
    </div>
    <nav class="admin-nav">
        <a href="index.php" class="<?php echo $adminPage === "dashboard" ? "active" : ""; ?>"><i class="fa-solid fa-gauge"></i> Master Dashboard</a>
        <a href="orders.php" class="<?php echo $adminPage === "orders" ? "active" : ""; ?>"><i class="fa-solid fa-list-check"></i> Order Management & Dispatch</a>
        <a href="payments.php" class="<?php echo $adminPage === "payments" ? "active" : ""; ?>">
            <i class="fa-solid fa-money-check-dollar"></i> Payments
            <?php if ($paymentsToConfirm > 0) { ?>
                <span style="margin-left:auto;background:var(--orange-accent);color:#fff;border-radius:999px;padding:1px 8px;font-size:12px;font-weight:700;"><?php echo $paymentsToConfirm; ?></span>
            <?php } ?>
        </a>
        <a href="driver_locations.php" class="<?php echo $adminPage === "driver_locations" ? "active" : ""; ?>"><i class="fa-solid fa-map-location-dot"></i> Driver Locations</a>
        <a href="inventory.php" class="<?php echo $adminPage === "inventory" ? "active" : ""; ?>"><i class="fa-solid fa-boxes-stacked"></i> Inventory</a>
        <a href="reports.php" class="<?php echo $adminPage === "reports" ? "active" : ""; ?>"><i class="fa-solid fa-chart-column"></i> Sales & Reports</a>
        <a href="../../Finals/home.php"><i class="fa-solid fa-store"></i> Customer App</a>
        <a href="../logout.php"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
    </nav>
</div>
