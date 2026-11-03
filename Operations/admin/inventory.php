<?php
include "../connections.php";
include "../includes/auth.php";

requireRole(["admin", "cashier"], "../login.php");
$user = loadCurrentUser($conn);
$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["update_inventory"])) {
    $id = (int)$_POST["inventory_id"];
    $filled = (int)$_POST["filled_count"];
    $empty = (int)$_POST["empty_count"];
    $total = $filled + $empty;

    $stmt = $conn->prepare("UPDATE container_inventory SET total_count = ?, filled_count = ?, empty_count = ? WHERE id = ?");
    $stmt->bind_param("iiii", $total, $filled, $empty, $id);
    $stmt->execute();
    $stmt->close();
    $message = "Inventory updated.";
}

$inventory = $conn->query("SELECT * FROM container_inventory ORDER BY id ASC");

$adminPage = "inventory";
$cssPath = "../style.css";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Container Inventory - Aquado</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="../style.css?v=<?php echo time(); ?>">
    <?php include __DIR__ . "/../includes/pwa_head.php"; ?>
</head>
<body>
<div class="admin-layout">
    <?php include "includes/sidebar.php"; ?>

    <main class="admin-main">
        <div class="admin-header-bar">
            <h2>Container Inventory</h2>
        </div>

        <?php if ($message !== "") { ?>
            <div class="success-message"><?php echo e($message); ?></div>
        <?php } ?>

        <div class="stats-grid">
            <?php
            $inventory->data_seek(0);
            while ($inv = $inventory->fetch_assoc()) {
            ?>
                <div class="stat-card">
                    <h4><?php echo e($inv["container_type"]); ?></h4>
                    <div class="stat-value"><?php echo (int)$inv["total_count"]; ?></div>
                    <p style="font-size:13px;color:var(--gray-500);margin-top:8px;">
                        Filled: <?php echo (int)$inv["filled_count"]; ?> · Empty: <?php echo (int)$inv["empty_count"]; ?>
                    </p>
                </div>
            <?php } ?>
        </div>

        <h3 style="color:var(--green-dark);margin:24px 0 16px;">Update Stock Levels</h3>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Container Type</th>
                    <th>Total</th>
                    <th>Filled</th>
                    <th>Empty</th>
                    <th>Last Updated</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $inventory->data_seek(0);
                while ($inv = $inventory->fetch_assoc()) {
                ?>
                    <tr>
                        <form method="POST">
                            <input type="hidden" name="inventory_id" value="<?php echo (int)$inv["id"]; ?>">
                            <td><?php echo e($inv["container_type"]); ?></td>
                            <td><?php echo (int)$inv["total_count"]; ?></td>
                            <td><input type="number" name="filled_count" value="<?php echo (int)$inv["filled_count"]; ?>" min="0" style="width:80px;padding:6px;border:1px solid var(--gray-200);border-radius:6px;"></td>
                            <td><input type="number" name="empty_count" value="<?php echo (int)$inv["empty_count"]; ?>" min="0" style="width:80px;padding:6px;border:1px solid var(--gray-200);border-radius:6px;"></td>
                            <td style="font-size:13px;"><?php echo date("M d, Y h:i A", strtotime($inv["last_updated"])); ?></td>
                            <td><button type="submit" name="update_inventory" class="btn btn-sm btn-primary">Update</button></td>
                        </form>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </main>
</div>
</body>
</html>
