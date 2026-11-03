<?php
include "connections.php";
include "includes/auth.php";

requireLogin();
$user = loadCurrentUser($conn);
$userId = currentUserId();

$orderNum = $_GET["order"] ?? "";
$order = null;

if ($orderNum !== "") {
    $stmt = $conn->prepare("SELECT * FROM orders WHERE order_number = ? AND user_id = ?");
    $stmt->bind_param("si", $orderNum, $userId);
    $stmt->execute();
    $order = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

if (!$order) {
    header("Location: order_history.php");
    exit();
}

$items = $conn->prepare("SELECT * FROM order_items WHERE order_id = ?");
$items->bind_param("i", $order["id"]);
$items->execute();
$orderItems = $items->get_result();
$items->close();

$pageTitle = "E-Receipt";
$portal = "customer";
include "includes/header.php";
?>

<section class="content-page">
    <div class="receipt-card">
        <div class="receipt-header">
            <img src="assets/logo.svg" alt="Aquado" style="width:48px;margin-bottom:8px;">
            <h3>AQUADO E-RECEIPT</h3>
            <p style="font-size:13px;color:var(--gray-500);"><?php echo e($order["order_number"]); ?></p>
            <p style="font-size:13px;color:var(--gray-500);"><?php echo date("M d, Y h:i A", strtotime($order["created_at"])); ?></p>
        </div>

        <div class="receipt-line"><span>Customer</span><span><?php echo e($order["customer_name"]); ?></span></div>
        <div class="receipt-line"><span>Address</span><span><?php echo e($order["customer_address"]); ?></span></div>
        <div class="receipt-line"><span>Payment</span><span><?php echo e($order["payment_method"]); ?></span></div>
        <div class="receipt-line"><span>Payment Status</span><span class="status-badge <?php echo paymentBadgeClass($order["payment_status"]); ?>"><?php echo e(paymentStatusLabel($order["payment_status"])); ?></span></div>
        <?php if ($order["payment_status"] === "Paid" && $order["paid_at"]) { ?>
            <div class="receipt-line"><span>Paid On</span><span><?php echo date("M d, Y h:i A", strtotime($order["paid_at"])); ?></span></div>
        <?php } elseif ($order["payment_status"] === "For Verification") { ?>
            <p style="font-size:12px;color:var(--gray-500);text-align:right;">The rider received your payment. Our cashier will confirm it shortly.</p>
        <?php } elseif ($order["payment_status"] === "Rejected") { ?>
            <p style="font-size:12px;color:#C62828;text-align:right;">We couldn't verify your GCash payment yet. Please contact us at 09228112567.</p>
        <?php } ?>
        <div class="receipt-line"><span>Status</span><span class="status-badge <?php echo statusBadgeClass($order["status"]); ?>"><?php echo e($order["status"]); ?></span></div>

        <hr style="margin:16px 0;border:none;border-top:1px dashed var(--gray-200);">

        <?php while ($item = $orderItems->fetch_assoc()) { ?>
            <div class="receipt-line">
                <span><?php echo e($item["product_name"]); ?> × <?php echo (int)$item["quantity"]; ?></span>
                <span><?php echo formatPeso($item["line_total"]); ?></span>
            </div>
        <?php } ?>

        <hr style="margin:16px 0;border:none;border-top:1px dashed var(--gray-200);">

        <div class="receipt-line"><span>Subtotal</span><span><?php echo formatPeso($order["subtotal"]); ?></span></div>
        <div class="receipt-line"><span>Delivery</span><span><?php echo formatPeso($order["delivery_fee"]); ?></span></div>
        <div class="receipt-line" style="font-weight:700;font-size:18px;color:var(--green-dark);">
            <span>Total</span><span><?php echo formatPeso($order["total"]); ?></span>
        </div>

        <?php if ($order["payment_proof"]) { ?>
            <hr style="margin:16px 0;border:none;border-top:1px dashed var(--gray-200);">
            <p style="font-size:13px;font-weight:600;color:var(--green-dark);margin-bottom:8px;">GCash Payment Proof</p>
            <a href="payment_proof.php?order=<?php echo urlencode($order["order_number"]); ?>" target="_blank">
                <img src="payment_proof.php?order=<?php echo urlencode($order["order_number"]); ?>" alt="GCash payment photo" style="width:100%;max-width:320px;border-radius:8px;display:block;margin:0 auto;">
            </a>
        <?php } ?>

        <div style="margin-top:24px;display:flex;gap:12px;">
            <a href="track_order.php?order=<?php echo urlencode($order["order_number"]); ?>" class="btn btn-primary" style="flex:1;">Track Order</a>
            <a href="order_history.php" class="btn btn-secondary" style="flex:1;">All Orders</a>
        </div>
    </div>
</section>

<?php if ($order["payment_status"] !== "Paid") { ?>
<script>
// Refresh the receipt automatically when the rider or cashier updates this order.
(function() {
    const url = "receipt_status.php?order=" + encodeURIComponent(<?php echo json_encode($order["order_number"]); ?>);
    let version = null;

    function check() {
        fetch(url + "&t=" + Date.now())
            .then(response => response.json())
            .then(data => {
                if (!data.success) return;
                if (version !== null && data.version !== version) {
                    location.reload();
                }
                version = data.version;
            })
            .catch(() => {});
    }

    check();
    setInterval(check, 10000);
})();
</script>
<?php } ?>

<?php include "includes/footer.php"; ?>
