<?php
include "../connections.php";
include "../includes/auth.php";
include "../includes/workflow.php";

requireRole(["admin", "cashier"], "../login.php");
$user = loadCurrentUser($conn);
$staffId = currentUserId();

// "Mark all as read" button.
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["mark_read"])) {
    $mark = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0");
    $mark->bind_param("i", $staffId);
    $mark->execute();
    $mark->close();
    header("Location: notifications.php");
    exit();
}

$stmt = $conn->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC, id DESC LIMIT 100");
$stmt->bind_param("i", $staffId);
$stmt->execute();
$notifications = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$unread = count(array_filter($notifications, function ($n) { return !$n["is_read"]; }));

$adminPage = "notifications";
$cssPath = "../style.css";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications - Aquado</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="../style.css?v=<?php echo ASSET_VERSION; ?>">
    <?php include __DIR__ . "/../includes/pwa_head.php"; ?>
</head>
<body>
<div class="admin-layout">
    <?php include "includes/sidebar.php"; ?>

    <main class="admin-main">
        <div class="admin-header-bar">
            <div>
                <p class="page-subtitle" style="text-transform:uppercase;letter-spacing:1px;font-size:12px;">Station Management Portal</p>
                <h2>Notifications</h2>
            </div>
            <?php if ($unread > 0) { ?>
                <form method="POST">
                    <button type="submit" name="mark_read" class="btn btn-secondary btn-sm">Mark all as read</button>
                </form>
            <?php } ?>
        </div>

        <?php if (empty($notifications)) { ?>
            <div class="card">
                <p style="color:var(--gray-500);">No notifications yet. Order cancellations will show up here.</p>
            </div>
        <?php } else { ?>
            <?php foreach ($notifications as $n) { ?>
                <div class="card notification-item <?php echo $n["is_read"] ? "" : "unread"; ?>">
                    <div class="notification-icon <?php echo e($n["type"]); ?>"><i class="fa-solid <?php echo $n["type"] === "order_cancelled" ? "fa-ban" : "fa-bell"; ?>"></i></div>
                    <div class="notification-body">
                        <p><?php echo e($n["message"]); ?></p>
                        <span class="notification-time"><?php echo date("M d, Y h:i A", strtotime($n["created_at"])); ?></span>
                        <?php if ($n["order_id"]) { ?>
                            <a href="orders.php">Open Order Management</a>
                        <?php } ?>
                    </div>
                </div>
            <?php } ?>
        <?php } ?>
    </main>
</div>
</body>
</html>
