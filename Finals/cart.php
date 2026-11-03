<?php
include "connections.php";
include "includes/auth.php";

requireLogin();
$user = loadCurrentUser($conn);
$userId = currentUserId();

if (isset($_GET["remove"])) {
    $id = (int)$_GET["remove"];
    $del = $conn->prepare("DELETE FROM cart WHERE id = ? AND user_id = ?");
    $del->bind_param("ii", $id, $userId);
    $del->execute();
    $del->close();
    header("Location: cart.php");
    exit();
}

$cart = $conn->prepare("SELECT * FROM cart WHERE user_id = ?");
$cart->bind_param("i", $userId);
$cart->execute();
$items = $cart->get_result();
$cart->close();

$subtotal = 0;
$cartRows = [];
while ($row = $items->fetch_assoc()) {
    $line = $row["product_price"] * $row["quantity"];
    $subtotal += $line;
    $cartRows[] = $row;
}

$pageTitle = "Cart";
$portal = "customer";
include "includes/header.php";
?>

<section class="content-page">
    <h2>Shopping Cart</h2>

    <?php if (empty($cartRows)) { ?>
        <div class="empty-state">
            <i class="fa-solid fa-cart-shopping"></i>
            <p>Your cart is empty.</p>
            <a href="catalog.php" class="btn btn-primary" style="margin-top:16px;">Browse Products</a>
        </div>
    <?php } else { ?>
        <div class="card">
            <?php foreach ($cartRows as $item) { ?>
                <div class="cart-item">
                    <img src="assets/water-5gal.svg" alt="">
                    <div class="cart-item-info">
                        <h4><?php echo e($item["product_name"]); ?></h4>
                        <p>Qty: <?php echo (int)$item["quantity"]; ?>
                            <?php if ($item["container_swap"]) { ?> · Container Swap<?php } ?>
                        </p>
                    </div>
                    <div class="cart-item-price"><?php echo formatPeso($item["product_price"] * $item["quantity"]); ?></div>
                    <a href="cart.php?remove=<?php echo (int)$item["id"]; ?>" style="color:#C62828;"><i class="fa-solid fa-trash"></i></a>
                </div>
            <?php } ?>

            <div class="order-summary">
                <div class="summary-row"><span>Subtotal</span><span><?php echo formatPeso($subtotal); ?></span></div>
                <div class="summary-row"><span>Delivery Fee</span><span><?php echo formatPeso(30); ?></span></div>
                <div class="summary-row total"><span>Total</span><span><?php echo formatPeso($subtotal + 30); ?></span></div>
            </div>
        </div>
        <a href="checkout.php" class="btn btn-primary btn-lg btn-block">Proceed to Checkout</a>
    <?php } ?>
</section>

<?php include "includes/footer.php"; ?>
