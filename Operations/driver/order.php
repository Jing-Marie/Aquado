<?php
include "../connections.php";
include "../includes/auth.php";
include "../includes/workflow.php";

requireRole("driver", "../login.php");
$user = loadCurrentUser($conn);
$driverId = currentUserId();

$orderId = (int)($_GET["id"] ?? 0);

$stmt = $conn->prepare("SELECT * FROM orders WHERE id = ? AND driver_id = ?");
$stmt->bind_param("ii", $orderId, $driverId);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$order) {
    header("Location: index.php");
    exit();
}

[$action, $label] = driverButton($order);

$items = $conn->prepare("SELECT * FROM order_items WHERE order_id = ?");
$items->bind_param("i", $orderId);
$items->execute();
$orderItems = $items->get_result();
$items->close();

$pageTitle = "Order Detail";
$cssPath = "../style.css";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Detail - Aquado Driver</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="../style.css?v=<?php echo ASSET_VERSION; ?>">
    <?php include __DIR__ . "/../includes/pwa_head.php"; ?>
</head>
<body>
<div class="driver-app">
    <div class="driver-header">
        <p class="driver-hierarchy">Field Operations Portal</p>
        <h2>Order Detail & Proof of Delivery</h2>
    </div>

    <div class="driver-content">
        <div class="card">
            <h4 style="color:var(--green-dark);"><?php echo e($order["customer_name"]); ?></h4>
            <p style="font-size:14px;color:var(--gray-500);margin:8px 0;"><?php echo e($order["customer_address"]); ?></p>
            <p style="font-size:14px;">Phone: <?php echo e($order["customer_phone"] ?? "N/A"); ?></p>
            <p style="font-size:14px;">Payment: <strong><?php echo e($order["payment_method"]); ?></strong> · <?php echo e($order["payment_status"]); ?></p>
            <?php if ($order["payment_method"] === "GCash" && $order["status"] !== "Delivered") { ?>
                <p style="font-size:13px;color:var(--gray-500);">Customer will pay by scanning your GCash QR code.</p>
            <?php } ?>
            <p style="font-size:14px;margin-top:8px;"><span class="status-badge <?php echo statusBadgeClass($order["status"]); ?>"><?php echo e($order["status"]); ?></span></p>
            <?php if ($order["container_swap"]) { ?>
                <p style="font-size:14px;color:var(--orange-accent);font-weight:600;margin-top:8px;"><i class="fa-solid fa-arrows-rotate"></i> Container Swap Required</p>
            <?php } ?>
        </div>

        <div class="card">
            <h4 style="margin-bottom:12px;color:var(--green-dark);">Order Items</h4>
            <?php while ($item = $orderItems->fetch_assoc()) { ?>
                <div class="receipt-line">
                    <span><?php echo e($item["product_name"]); ?> × <?php echo (int)$item["quantity"]; ?></span>
                    <span><?php echo formatPeso($item["line_total"]); ?></span>
                </div>
            <?php } ?>
            <div class="receipt-line" style="font-weight:700;margin-top:8px;">
                <span>Total (Collect)</span>
                <span><?php echo formatPeso($order["total"]); ?></span>
            </div>
        </div>

        <?php if (!empty($_SESSION["order_error"])) { ?>
            <div class="error-message"><?php echo e($_SESSION["order_error"]); ?></div>
            <?php unset($_SESSION["order_error"]); ?>
        <?php } ?>

        <?php if (isset($_GET["uploaded"])) { ?>
            <div class="success-message">New payment photo sent to the cashier.</div>
        <?php } ?>

        <?php if ($action === "done") {
            $isCash = $order["payment_method"] !== "GCash";
        ?>
            <form method="POST" action="update_status.php" enctype="multipart/form-data">
                <input type="hidden" name="order_id" value="<?php echo (int)$order["id"]; ?>">
                <input type="hidden" name="action" value="done">
                <div class="card">
                    <h4 style="margin-bottom:12px;color:var(--green-dark);">Confirm Payment</h4>
                    <div class="receipt-line"><span>Payment Method</span><span><?php echo e($order["payment_method"]); ?></span></div>
                    <div class="receipt-line" style="font-weight:700;"><span>Amount</span><span><?php echo formatPeso($order["total"]); ?></span></div>

                    <?php if (!$isCash) { ?>
                        <p style="font-size:13px;color:var(--gray-500);margin-top:12px;">
                            Let the customer scan your GCash QR code, then take a photo of the successful payment screen.
                        </p>
                        <div class="form-group" style="margin-top:8px;">
                            <label>Photo of GCash Payment</label>
                            <input type="file" name="payment_proof" accept="image/*" required class="proof-input">
                        </div>
                        <img class="proof-preview" alt="Payment photo preview" style="display:none;width:100%;border-radius:8px;margin-bottom:8px;">
                    <?php } ?>

                    <label style="display:flex;gap:10px;align-items:flex-start;margin-top:12px;font-size:14px;cursor:pointer;">
                        <input type="checkbox" name="payment_received" value="1" required style="margin-top:3px;">
                        <span>
                            <?php if ($isCash) { ?>
                                I received <?php echo formatPeso($order["total"]); ?> cash from the customer.
                            <?php } else { ?>
                                The customer paid <?php echo formatPeso($order["total"]); ?> to my GCash.
                            <?php } ?>
                        </span>
                    </label>
                </div>
                <div class="card">
                    <h4 style="margin-bottom:12px;color:var(--green-dark);">Proof of Delivery</h4>
                    <div class="form-group">
                        <label>Delivery Photo <span class="req">*</span></label>
                        <input type="file" name="delivery_photo" accept="image/*" capture="environment" required class="proof-input" data-preview="deliveryPhotoPreview">
                    </div>
                    <img id="deliveryPhotoPreview" alt="Delivery photo preview" style="display:none;width:100%;border-radius:8px;margin-bottom:8px;">
                    <div class="form-group">
                        <label>Empty Containers Collected <span class="req">*</span></label>
                        <input type="number" name="empty_containers" min="0" max="999" step="1" inputmode="numeric" value="0" required>
                    </div>
                    <p style="font-size:13px;color:var(--gray-500);">
                        <i class="fa-solid fa-clock"></i> The delivery date and time are recorded automatically when you confirm
                        (now: <strong id="deliveryClock"><?php echo date("M d, Y h:i A"); ?></strong>).
                    </p>
                </div>
                <div class="form-group">
                    <label>Delivery Notes</label>
                    <textarea name="notes" rows="2" placeholder="Any notes about this delivery..."></textarea>
                </div>
                <button type="submit" class="btn btn-dark btn-block btn-lg">Confirm Delivery & Payment</button>
            </form>
        <?php } elseif ($order["status"] !== "Delivered") { ?>
            <form method="POST" action="update_status.php">
                <input type="hidden" name="order_id" value="<?php echo (int)$order["id"]; ?>">
                <input type="hidden" name="action" value="<?php echo e($action); ?>">
                <button type="submit" class="btn btn-primary btn-block btn-lg" <?php echo $action === null ? "disabled" : ""; ?>><?php echo e($label); ?></button>
            </form>
        <?php } else { ?>
            <!-- DELIVERED: PAYMENT STATUS -->
            <div class="card">
                <h4 style="margin-bottom:12px;color:var(--green-dark);">Payment</h4>
                <div class="receipt-line">
                    <span>Status</span>
                    <span class="status-badge <?php echo paymentBadgeClass($order["payment_status"]); ?>"><?php echo e($order["payment_status"]); ?></span>
                </div>
                <?php if ($order["payment_status"] === "For Verification") { ?>
                    <p style="font-size:13px;color:var(--gray-500);margin-top:8px;">Waiting for the cashier to confirm this payment.</p>
                <?php } ?>
                <?php if ($order["payment_proof"]) { ?>
                    <a href="../payment_proof.php?order_id=<?php echo (int)$order["id"]; ?>" target="_blank">
                        <img src="../payment_proof.php?order_id=<?php echo (int)$order["id"]; ?>" alt="GCash payment photo" style="width:100%;border-radius:8px;margin-top:12px;">
                    </a>
                <?php } ?>
            </div>

            <?php if ($order["delivered_at"] || $order["delivery_photo"]) { ?>
                <div class="card">
                    <h4 style="margin-bottom:12px;color:var(--green-dark);">Proof of Delivery</h4>
                    <?php if ($order["delivered_at"]) { ?>
                        <div class="receipt-line"><span>Delivered on</span><span><?php echo date("M d, Y h:i A", strtotime($order["delivered_at"])); ?></span></div>
                    <?php } ?>
                    <?php if ($order["empty_containers_collected"] !== null) { ?>
                        <div class="receipt-line"><span>Empty containers collected</span><span><?php echo (int)$order["empty_containers_collected"]; ?></span></div>
                    <?php } ?>
                    <?php if ($order["delivery_photo"]) { ?>
                        <a href="../delivery_proof.php?order_id=<?php echo (int)$order["id"]; ?>" target="_blank">
                            <img src="../delivery_proof.php?order_id=<?php echo (int)$order["id"]; ?>" alt="Delivery photo" style="width:100%;border-radius:8px;margin-top:12px;">
                        </a>
                    <?php } ?>
                </div>
            <?php } ?>

            <?php if ($order["payment_status"] === "Rejected") { ?>
                <form method="POST" action="update_status.php" enctype="multipart/form-data">
                    <input type="hidden" name="order_id" value="<?php echo (int)$order["id"]; ?>">
                    <input type="hidden" name="action" value="resubmit_proof">
                    <div class="card">
                        <h4 style="margin-bottom:8px;color:#C62828;">Payment Photo Rejected</h4>
                        <p style="font-size:14px;margin-bottom:12px;"><?php echo e($order["payment_note"]); ?></p>
                        <div class="form-group">
                            <label>New Photo of GCash Payment</label>
                            <input type="file" name="payment_proof" accept="image/*" required class="proof-input">
                        </div>
                        <img class="proof-preview" alt="Payment photo preview" style="display:none;width:100%;border-radius:8px;margin-bottom:8px;">
                        <button type="submit" class="btn btn-dark btn-block btn-lg">Send New Photo</button>
                    </div>
                </form>
            <?php } ?>
        <?php } ?>
    </div>

    <nav class="driver-bottom-nav">
        <a href="index.php"><i class="fa-solid fa-house"></i> Dashboard</a>
        <a href="routes.php"><i class="fa-solid fa-map"></i> Route Map</a>
        <a href="navigation.php"><i class="fa-solid fa-location-arrow"></i> Navigation</a>
        <a href="order.php?id=<?php echo (int)$orderId; ?>" class="active"><i class="fa-solid fa-clipboard-check"></i> Order</a>
    </nav>
</div>

<script src="js/proof_photo.js?v=<?php echo ASSET_VERSION; ?>"></script>
<script>
// Live clock next to "delivery date and time" (the server records the real time).
const deliveryClock = document.getElementById("deliveryClock");
if (deliveryClock) {
    setInterval(function() {
        deliveryClock.textContent = new Date().toLocaleString([], {
            month: "short", day: "numeric", year: "numeric", hour: "numeric", minute: "2-digit"
        });
    }, 30000);
}
</script>
</body>
</html>
