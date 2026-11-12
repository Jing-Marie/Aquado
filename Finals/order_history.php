<?php
include "connections.php";
include "includes/auth.php";

requireLogin();
$user = loadCurrentUser($conn);
$userId = currentUserId();

$tab = $_GET["tab"] ?? "orders";
$orderNum = $_GET["order"] ?? "";

$orders = $conn->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC");
$orders->bind_param("i", $userId);
$orders->execute();
$result = $orders->get_result();
$orders->close();

$trackOrder = null;
$tracking = [];
if ($orderNum !== "") {
    $stmt = $conn->prepare("SELECT * FROM orders WHERE order_number = ? AND user_id = ?");
    $stmt->bind_param("si", $orderNum, $userId);
    $stmt->execute();
    $trackOrder = $stmt->get_result()->fetch_assoc();
    $stmt->close();
} elseif ($tab === "tracking") {
    $stmt = $conn->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC LIMIT 1");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $trackOrder = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

if ($trackOrder) {
    $t = $conn->prepare("SELECT * FROM delivery_tracking WHERE order_id = ? ORDER BY created_at ASC");
    $t->bind_param("i", $trackOrder["id"]);
    $t->execute();
    $tracking = $t->get_result()->fetch_all(MYSQLI_ASSOC);
    $t->close();
}

// Live map while the driver is on the way to the customer.
$showLiveMap = $tab === "tracking"
    && $trackOrder
    && in_array($trackOrder["status"], ["Accepted", "Out for Delivery"], true);

$steps = orderSteps($trackOrder["requires_pickup"] ?? 0);
$currentIdx = 0;
if ($trackOrder) {
    $currentIdx = array_search($trackOrder["status"], $steps);
    if ($currentIdx === false) $currentIdx = 0;
}

// Result of a cancel request (set by cancel_order.php).
$notice = $_SESSION["order_notice"] ?? null;
unset($_SESSION["order_notice"]);

$pageTitle = "Orders & Receipts";
$activeNav = "orders";
$breadcrumb = "Order History & E-Receipts";
$portal = "customer";
include "includes/header.php";
?>

<section class="content-page">
    <h2>Order History & E-Receipts</h2>

    <?php if ($notice) { ?>
        <div class="<?php echo $notice[0] === "success" ? "success-message" : "error-message"; ?>"><?php echo e($notice[1]); ?></div>
    <?php } ?>

    <div class="tab-nav">
        <a href="order_history.php?tab=orders" class="tab-btn <?php echo $tab === "orders" ? "active" : ""; ?>">
            <i class="fa-solid fa-receipt"></i> Past Order Details / Receipts
        </a>
        <a href="order_history.php?tab=tracking" class="tab-btn <?php echo $tab === "tracking" ? "active" : ""; ?>">
            <i class="fa-solid fa-location-dot"></i> Tracking Status
        </a>
    </div>

    <?php if ($tab === "tracking") { ?>
        <?php if (!$trackOrder) { ?>
            <div class="empty-state">
                <i class="fa-solid fa-location-dot"></i>
                <p>No orders to track yet.</p>
                <a href="catalog.php" class="btn btn-primary" style="margin-top:16px;">Order Water</a>
            </div>
        <?php } else { ?>
            <div class="card">
                <div class="order-card-header">
                    <span class="order-number"><?php echo e($trackOrder["order_number"]); ?></span>
                    <span class="status-badge <?php echo statusBadgeClass($trackOrder["status"]); ?>"><?php echo e($trackOrder["status"]); ?></span>
                </div>
                <p style="font-size:14px;color:var(--gray-500);">Deliver to: <?php echo e($trackOrder["customer_address"]); ?></p>
                <?php if ($trackOrder["status"] === "Pending") { ?>
                    <form method="POST" action="cancel_order.php" class="cancel-form" style="margin-top:14px;">
                        <input type="hidden" name="order" value="<?php echo e($trackOrder["order_number"]); ?>">
                        <input type="hidden" name="return" value="tracking">
                        <button type="submit" class="btn btn-sm btn-cancel"><i class="fa-solid fa-ban"></i> Cancel Order</button>
                    </form>
                <?php } ?>
            </div>

            <?php if ($trackOrder["status"] === "Cancelled") { ?>
                <div class="card cancelled-card">
                    <h3><i class="fa-solid fa-ban"></i> Order Cancelled</h3>
                    <p>
                        <?php if ($trackOrder["cancelled_at"]) { ?>
                            Cancelled on <?php echo date("M d, Y h:i A", strtotime($trackOrder["cancelled_at"])); ?>.
                        <?php } ?>
                        <?php if ($trackOrder["cancel_reason"]) { ?>
                            Reason: <?php echo e($trackOrder["cancel_reason"]); ?>
                        <?php } ?>
                    </p>
                </div>
            <?php } else { ?>
            <div class="card">
                <h3 style="color:var(--green-dark);margin-bottom:16px;">Tracking Status</h3>
                <div class="tracking-timeline">
                    <?php foreach ($steps as $i => $step) {
                        $cls = $i < $currentIdx ? "done" : ($i === $currentIdx ? "active" : "");
                    ?>
                        <div class="tracking-step <?php echo $cls; ?>">
                            <div class="step-dot"><?php if ($i <= $currentIdx) { ?><i class="fa-solid fa-check"></i><?php } ?></div>
                            <span><?php echo e($step); ?></span>
                        </div>
                    <?php } ?>
                </div>
            </div>
            <?php } ?>

            <?php if ($trackOrder["status"] === "Delivered" && ($trackOrder["delivered_at"] || $trackOrder["delivery_photo"])) { ?>
                <!-- PROOF OF DELIVERY -->
                <div class="card">
                    <h3 style="color:var(--green-dark);margin-bottom:12px;"><i class="fa-solid fa-camera"></i> Proof of Delivery</h3>
                    <?php if ($trackOrder["delivered_at"]) { ?>
                        <div class="receipt-line"><span>Delivered on</span><span><?php echo date("M d, Y h:i A", strtotime($trackOrder["delivered_at"])); ?></span></div>
                    <?php } ?>
                    <?php if ($trackOrder["empty_containers_collected"] !== null) { ?>
                        <div class="receipt-line"><span>Empty containers collected</span><span><?php echo (int)$trackOrder["empty_containers_collected"]; ?></span></div>
                    <?php } ?>
                    <?php if ($trackOrder["delivery_photo"]) { ?>
                        <a href="delivery_proof.php?order=<?php echo urlencode($trackOrder["order_number"]); ?>" target="_blank">
                            <img src="delivery_proof.php?order=<?php echo urlencode($trackOrder["order_number"]); ?>" alt="Delivery photo" style="width:100%;max-width:360px;border-radius:8px;display:block;margin-top:12px;">
                        </a>
                    <?php } ?>
                </div>
            <?php } ?>

            <?php if ($showLiveMap) { ?>
                <!-- LIVE DRIVER MAP -->
                <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
                <div class="card">
                    <h3 style="color:var(--green-dark);margin-bottom:16px;"><i class="fa-solid fa-truck"></i> Live Driver Location</h3>
                    <div id="customerTrackingMap" style="width:100%;height:450px;border-radius:12px;overflow:hidden;"></div>
                    <p id="customerMapStatus" style="font-size:13px;color:var(--gray-500);margin-top:10px;">Loading driver location...</p>
                </div>
            <?php } ?>

            <?php if (!empty($tracking)) { ?>
                <div class="card">
                    <h3 style="color:var(--green-dark);margin-bottom:12px;">Activity Log</h3>
                    <?php foreach ($tracking as $tr) { ?>
                        <div style="padding:8px 0;border-bottom:1px solid var(--gray-100);font-size:14px;">
                            <strong><?php echo e($tr["status"]); ?></strong> — <?php echo e($tr["notes"]); ?>
                            <span style="color:var(--gray-500);font-size:12px;display:block;"><?php echo date("M d, Y h:i A", strtotime($tr["created_at"])); ?></span>
                        </div>
                    <?php } ?>
                </div>
            <?php } ?>

            <?php if ($result->num_rows > 1) { ?>
                <div class="card">
                    <h4 style="margin-bottom:12px;color:var(--green-dark);">Select Order to Track</h4>
                    <?php
                    $result->data_seek(0);
                    while ($o = $result->fetch_assoc()) {
                    ?>
                        <a href="order_history.php?tab=tracking&order=<?php echo urlencode($o["order_number"]); ?>" style="display:block;padding:10px 0;border-bottom:1px solid var(--gray-100);font-size:14px;">
                            <?php echo e($o["order_number"]); ?> — <span class="status-badge <?php echo statusBadgeClass($o["status"]); ?>"><?php echo e($o["status"]); ?></span>
                        </a>
                    <?php } ?>
                </div>
            <?php } ?>
        <?php } ?>

    <?php } else { ?>
        <?php if ($result->num_rows === 0) { ?>
            <div class="empty-state">
                <i class="fa-solid fa-receipt"></i>
                <p>No orders yet. Start by ordering purified water!</p>
                <a href="catalog.php" class="btn btn-primary" style="margin-top:16px;">Product Catalog</a>
            </div>
        <?php } else { ?>
            <?php while ($order = $result->fetch_assoc()) { ?>
                <div class="order-card">
                    <div class="order-card-header">
                        <span class="order-number"><?php echo e($order["order_number"]); ?></span>
                        <span class="status-badge <?php echo statusBadgeClass($order["status"]); ?>"><?php echo e($order["status"]); ?></span>
                    </div>
                    <p style="font-size:14px;color:var(--gray-500);margin-bottom:8px;">
                        <?php echo date("M d, Y", strtotime($order["created_at"])); ?> · <?php echo e($order["payment_method"]); ?> · <?php echo e(paymentStatusLabel($order["payment_status"])); ?>
                    </p>
                    <p style="font-size:16px;font-weight:700;color:var(--green-dark);"><?php echo formatPeso($order["total"]); ?></p>
                    <div style="margin-top:12px;display:flex;gap:8px;flex-wrap:wrap;">
                        <a href="receipt.php?order=<?php echo urlencode($order["order_number"]); ?>" class="btn btn-sm btn-secondary">View E-Receipt</a>
                        <a href="order_history.php?tab=tracking&order=<?php echo urlencode($order["order_number"]); ?>" class="btn btn-sm btn-primary">Track Status</a>
                        <?php if ($order["status"] === "Pending") { ?>
                            <form method="POST" action="cancel_order.php" class="cancel-form" style="display:inline;">
                                <input type="hidden" name="order" value="<?php echo e($order["order_number"]); ?>">
                                <input type="hidden" name="return" value="orders">
                                <button type="submit" class="btn btn-sm btn-cancel"><i class="fa-solid fa-ban"></i> Cancel Order</button>
                            </form>
                        <?php } ?>
                    </div>
                </div>
            <?php } ?>
        <?php } ?>
    <?php } ?>
</section>

<script>
// Ask before cancelling an order.
document.querySelectorAll(".cancel-form").forEach(function(form) {
    form.addEventListener("submit", function(event) {
        if (!confirm("Cancel this order? This cannot be undone.")) {
            event.preventDefault();
        }
    });
});
</script>

<?php if ($showLiveMap) { ?>
<script>
const TRACK_ORDER_NUMBER = <?php echo json_encode($trackOrder["order_number"]); ?>;
const CUSTOMER_ADDRESS = <?php echo json_encode($trackOrder["customer_address"]); ?>;
</script>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="js/order_history.js?v=<?php echo ASSET_VERSION; ?>"></script>
<?php } ?>

<?php include "includes/footer.php"; ?>
