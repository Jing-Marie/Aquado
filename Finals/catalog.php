<?php
include "connections.php";
include "includes/auth.php";

requireLogin();

$user = loadCurrentUser($conn);
$userId = currentUserId();
$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["add_to_cart"])) {
    $productId = (int)$_POST["product_id"];
    $qty = max(1, (int)$_POST["quantity"]);
    $swap = isset($_POST["container_swap"]) ? 1 : 0;

    $stmt = $conn->prepare("SELECT id, name, price FROM products WHERE id = ? AND is_active = 1");
    $stmt->bind_param("i", $productId);
    $stmt->execute();
    $product = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($product) {
        $check = $conn->prepare("SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ?");
        $check->bind_param("ii", $userId, $productId);
        $check->execute();
        $existing = $check->get_result()->fetch_assoc();
        $check->close();

        if ($existing) {
            $newQty = $existing["quantity"] + $qty;
            $upd = $conn->prepare("UPDATE cart SET quantity = ?, container_swap = ? WHERE id = ?");
            $upd->bind_param("iii", $newQty, $swap, $existing["id"]);
            $upd->execute();
            $upd->close();
        } else {
            $ins = $conn->prepare("INSERT INTO cart (user_id, product_id, product_name, product_price, quantity, container_swap) VALUES (?, ?, ?, ?, ?, ?)");
            $ins->bind_param("iisdii", $userId, $productId, $product["name"], $product["price"], $qty, $swap);
            $ins->execute();
            $ins->close();
        }
        $message = "Added to cart!";
    }
}

$products = $conn->query("SELECT * FROM products WHERE is_active = 1 ORDER BY id ASC");

$pageTitle = "Product Catalog";
$activeNav = "catalog";
$breadcrumb = "Product Catalog › Product Selection";
$portal = "customer";
include "includes/header.php";
?>

<section class="catalog-page">
    <div class="catalog-header">
        <div>
            <h2>Product Catalog</h2>
            <p class="page-subtitle">Product Selection</p>
        </div>
        <div class="swap-toggle">
            <label>Container Swap</label>
            <label class="toggle-switch">
                <input type="checkbox" id="globalSwap" form="none">
                <span class="toggle-slider"></span>
            </label>
        </div>
    </div>

    <?php if ($message !== "") { ?>
        <div class="success-message"><?php echo e($message); ?></div>
    <?php } ?>

    <div class="product-grid">
        <?php while ($p = $products->fetch_assoc()) { ?>
            <div class="product-card">
                <div class="product-image">
                    <img src="<?php echo e($p["image"] ?: "assets/water-5gal.svg"); ?>" alt="<?php echo e($p["name"]); ?>">
                </div>
                <div class="product-info">
                    <h3><?php echo e($p["name"]); ?></h3>
                    <p><?php echo e($p["description"]); ?></p>
                    <div class="product-price">
                        <?php echo formatPeso($p["price"]); ?>
                        <span>/ <?php echo e($p["unit"]); ?></span>
                    </div>
                    <form method="POST">
                        <input type="hidden" name="product_id" value="<?php echo (int)$p["id"]; ?>">
                        <input type="hidden" name="container_swap" class="swap-input" value="0">
                        <div class="qty-control">
                            <button type="button" onclick="changeQty(this,-1)">−</button>
                            <span class="qty-val">1</span>
                            <input type="hidden" name="quantity" value="1" class="qty-input">
                            <button type="button" onclick="changeQty(this,1)">+</button>
                        </div>
                        <button type="submit" name="add_to_cart" class="btn btn-primary btn-block">Add to Cart</button>
                    </form>
                </div>
            </div>
        <?php } ?>
    </div>
</section>

<script>
function changeQty(btn, delta) {
    const form = btn.closest("form");
    const span = form.querySelector(".qty-val");
    const input = form.querySelector(".qty-input");
    let val = parseInt(span.textContent) + delta;
    if (val < 1) val = 1;
    span.textContent = val;
    input.value = val;
}

const globalSwap = document.getElementById("globalSwap");
if (globalSwap) {
    globalSwap.addEventListener("change", function() {
        document.querySelectorAll(".swap-input").forEach(function(el) {
            el.value = globalSwap.checked ? "1" : "0";
        });
    });
}
</script>

<?php include "includes/footer.php"; ?>
