<?php
include "connections.php";
include "includes/auth.php";
require_once __DIR__ . "/../order_actions.php";

requireLogin();
$user = loadCurrentUser($conn);
$userId = currentUserId();

// Opening the page shows the unread ones as new, then marks everything read.
$stmt = $conn->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC, id DESC LIMIT 50");
$stmt->bind_param("i", $userId);
$stmt->execute();
$notifications = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$orderNumbers = [];
$ids = array_filter(array_unique(array_column($notifications, "order_id")));
if ($ids) {
    $res = $conn->query("SELECT id, order_number FROM orders WHERE user_id = " . (int)$userId . " AND id IN (" . implode(",", array_map("intval", $ids)) . ")");
    while ($row = $res->fetch_assoc()) {
        $orderNumbers[$row["id"]] = $row["order_number"];
    }
}

$mark = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0");
$mark->bind_param("i", $userId);
$mark->execute();
$mark->close();

$pageTitle = "Notifications";
$breadcrumb = "Notifications";
$portal = "customer";
include "includes/header.php";
?>

<section class="content-page">
    <h2>Notifications</h2>

    <?php if (empty($notifications)) { ?>
        <div class="empty-state">
            <i class="fa-solid fa-bell"></i>
            <p>You have no notifications yet.</p>
        </div>
    <?php } else { ?>
        <?php foreach ($notifications as $n) { ?>
            <div class="card notification-item <?php echo $n["is_read"] ? "" : "unread"; ?>">
                <div class="notification-icon <?php echo e($n["type"]); ?>"><i class="fa-solid <?php echo $n["type"] === "order_cancelled" ? "fa-ban" : "fa-bell"; ?>"></i></div>
                <div class="notification-body">
                    <p><?php echo e($n["message"]); ?></p>
                    <span class="notification-time"><?php echo date("M d, Y h:i A", strtotime($n["created_at"])); ?></span>
                    <?php if (isset($orderNumbers[$n["order_id"]])) { ?>
                        <a href="order_history.php?tab=tracking&order=<?php echo urlencode($orderNumbers[$n["order_id"]]); ?>">View order</a>
                    <?php } ?>
                </div>
            </div>
        <?php } ?>
    <?php } ?>
</section>

<?php include "includes/footer.php"; ?>
