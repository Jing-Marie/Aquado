<?php
include "connections.php";
include "includes/auth.php";

requireLogin();
$user = loadCurrentUser($conn);
$userId = currentUserId();
$message = "";

$cart = $conn->prepare("SELECT * FROM cart WHERE user_id = ?");
$cart->bind_param("i", $userId);
$cart->execute();
$items = $cart->get_result();
$cart->close();

$cartRows = [];
$subtotal = 0;
$hasSwap = false;
while ($row = $items->fetch_assoc()) {
    $line = $row["product_price"] * $row["quantity"];
    $subtotal += $line;
    if ($row["container_swap"]) $hasSwap = true;
    $cartRows[] = $row;
}

if (empty($cartRows)) {
    header("Location: cart.php");
    exit();
}

$deliveryFee = 0;
$total = $subtotal + $deliveryFee;

// Refill orders need the empty containers picked up and washed before delivery.
$refills = $conn->prepare("SELECT COUNT(*) AS cnt FROM cart c JOIN products p ON p.id = c.product_id WHERE c.user_id = ? AND p.product_type = 'refill'");
$refills->bind_param("i", $userId);
$refills->execute();
$requiresPickup = $refills->get_result()->fetch_assoc()["cnt"] > 0 ? 1 : 0;
$refills->close();

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $address = trim($_POST["address"] ?? $user["address"] ?? "");
    $phone = trim($_POST["phone"] ?? $user["contact_number"] ?? "");
    $payment = $_POST["payment_method"] ?? "COD";

    if ($address === "") {
        $message = "Please enter a delivery address.";
    } else {
        $orderNum = generateOrderNumber();
        $stmt = $conn->prepare("INSERT INTO orders (order_number, user_id, customer_name, customer_email, customer_phone, customer_address, subtotal, delivery_fee, total, payment_method, container_swap, requires_pickup, status, delivery_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending', CURDATE())");
        $stmt->bind_param("sissssdddsii", $orderNum, $userId, $user["fullname"], $user["email"], $phone, $address, $subtotal, $deliveryFee, $total, $payment, $hasSwap, $requiresPickup);
        $stmt->execute();
        $orderId = $conn->insert_id;
        $stmt->close();

        foreach ($cartRows as $item) {
            $lineTotal = $item["product_price"] * $item["quantity"];
            $ins = $conn->prepare("INSERT INTO order_items (order_id, product_id, product_name, quantity, unit_price, line_total) VALUES (?, ?, ?, ?, ?, ?)");
            $ins->bind_param("iisidd", $orderId, $item["product_id"], $item["product_name"], $item["quantity"], $item["product_price"], $lineTotal);
            $ins->execute();
            $ins->close();
        }

        $track = $conn->prepare("INSERT INTO delivery_tracking (order_id, status, notes) VALUES (?, 'Pending', 'Order placed online')");
        $track->bind_param("i", $orderId);
        $track->execute();
        $track->close();

        $conn->query("DELETE FROM cart WHERE user_id = $userId");

        header("Location: receipt.php?order=" . urlencode($orderNum));
        exit();
    }
}

$pageTitle = "Checkout";
$breadcrumb = "Payment & Order Summary";
$portal = "customer";
include "includes/header.php";
?>

<section class="content-page">
    <h2>Payment & Order Summary</h2>

    <?php if ($message !== "") { ?>
        <div class="error-message"><?php echo e($message); ?></div>
    <?php } ?>

    <form method="POST">
        <div class="card">
            <h3 style="margin-bottom:16px;color:var(--green-dark);">Order Items</h3>
            <?php foreach ($cartRows as $item) { ?>
                <div class="cart-item">
                    <div class="cart-item-info">
                        <h4><?php echo e($item["product_name"]); ?> × <?php echo (int)$item["quantity"]; ?></h4>
                    </div>
                    <div class="cart-item-price"><?php echo formatPeso($item["product_price"] * $item["quantity"]); ?></div>
                </div>
            <?php } ?>

            <div class="order-summary">
                <div class="summary-row"><span>Subtotal</span><span><?php echo formatPeso($subtotal); ?></span></div>
                <?php if ($hasSwap) { ?>
                    <div class="summary-row"><span>Container Swap</span><span>Yes</span></div>
                <?php } ?>
                <div class="summary-row total"><span>Total</span><span><?php echo formatPeso($total); ?></span></div>
            </div>
        </div>

        <div class="card">
            <h3 style="margin-bottom:16px;color:var(--green-dark);">Delivery Details</h3>
            <div class="form-group">
                <label>Delivery Address</label>
                <textarea name="address" rows="2" required><?php echo e($_POST["address"] ?? $user["address"] ?? ""); ?></textarea>
            </div>
            <div class="form-group">
                <label>Contact Number</label>
                <input type="text" name="phone" value="<?php echo e($_POST["phone"] ?? $user["contact_number"] ?? ""); ?>" required>
            </div>
        </div>

        <div class="card">
            <h3 style="margin-bottom:16px;color:var(--green-dark);">Payment Method</h3>
            <div class="payment-options">
                <label class="payment-option selected">
                    <input type="radio" name="payment_method" value="COD" checked>
                    <i class="fa-solid fa-money-bill-wave"></i>
                    <div>Cash on Delivery</div>
                </label>
                <label class="payment-option">
                    <input type="radio" name="payment_method" value="GCash">
                    <i class="fa-solid fa-mobile-screen"></i>
                    <div>GCash</div>
                </label>
            </div>
        </div>

        <button type="submit" class="btn btn-primary btn-lg btn-block">Place Order</button>
    </form>
</section>

<script>
document.querySelectorAll(".payment-option").forEach(function(opt) {
    opt.addEventListener("click", function() {
        document.querySelectorAll(".payment-option").forEach(function(o) { o.classList.remove("selected"); });
        opt.classList.add("selected");
        opt.querySelector("input").checked = true;
    });
});
</script>

<?php include "includes/footer.php"; ?>