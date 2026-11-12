<?php
include "../connections.php";
include "../includes/auth.php";
include "../includes/workflow.php";

requireRole(["admin", "cashier"], "../login.php");
$user = loadCurrentUser($conn);
$staffId = currentUserId();
$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (isset($_POST["confirm_order"])) {
        $id = (int)$_POST["order_id"];
        $message = confirmOrder($conn, $id, $staffId)
            ? "Order accepted."
            : "Order could not be accepted (it is no longer pending).";
    }
    if (isset($_POST["cancel_order"])) {
        $id = (int)$_POST["order_id"];
        $message = cancelOrder($conn, $id, $staffId, trim($_POST["cancel_reason"] ?? ""))
            ? "Order cancelled. The customer and the staff were notified."
            : "Order could not be cancelled. Only orders that are still pending can be cancelled.";
    }
    if (isset($_POST["dispatch_order"])) {
        $id = (int)$_POST["order_id"];
        $driverId = (int)$_POST["driver_id"];
        $message = assignDriver($conn, $id, $driverId, $staffId)
            ? "Driver assigned."
            : "Driver could not be assigned (the order must be accepted first and not yet accepted by a driver).";
    }
    if (isset($_POST["mark_refilled"])) {
        $id = (int)$_POST["order_id"];
        $message = markWashedAndRefilled($conn, $id, $staffId)
            ? "Containers marked as washed and refilled. The driver can now deliver."
            : "Order could not be updated (the empty containers must be picked up first).";
    }
    if (isset($_POST["manual_order"])) {
        $name = trim($_POST["customer_name"] ?? "");
        $phone = trim($_POST["customer_phone"] ?? "");
        $address = trim($_POST["customer_address"] ?? "");
        $productId = (int)$_POST["product_id"];
        $qty = max(1, (int)$_POST["quantity"]);
        $payment = $_POST["payment_method"] ?? "COD";

        if ($name !== "" && $address !== "") {
            $prod = $conn->query("SELECT * FROM products WHERE id = $productId")->fetch_assoc();
            if ($prod) {
                $subtotal = $prod["price"] * $qty;
                $deliveryFee = 0;
                $total = $subtotal + $deliveryFee;
                $requiresPickup = $prod["product_type"] === "refill" ? 1 : 0;
                $orderNum = generateOrderNumber();
                $stmt = $conn->prepare("INSERT INTO orders (order_number, customer_name, customer_phone, customer_address, subtotal, delivery_fee, total, payment_method, requires_pickup, status, delivery_date) VALUES (?, ?, ?, ?, ?, 0, ?, ?, ?, 'Confirmed', CURDATE())");
                $stmt->bind_param("ssssddsi", $orderNum, $name, $phone, $address, $subtotal, $total, $payment, $requiresPickup);
                $stmt->execute();
                $orderId = $conn->insert_id;
                $stmt->close();

                $ins = $conn->prepare("INSERT INTO order_items (order_id, product_id, product_name, quantity, unit_price, line_total) VALUES (?, ?, ?, ?, ?, ?)");
                $lineTotal = $prod["price"] * $qty;
                $ins->bind_param("iisidd", $orderId, $productId, $prod["name"], $qty, $prod["price"], $lineTotal);
                $ins->execute();
                $ins->close();

                $message = "Manual order created: $orderNum";
            }
        }
    }
}

$orders = $conn->query("SELECT o.*, u.fullname as driver_name FROM orders o LEFT JOIN users u ON o.driver_id = u.id ORDER BY o.created_at DESC");
$drivers = $conn->query("
    SELECT u.id, u.fullname, u.is_online, COUNT(o.id) AS active_orders
    FROM users u
    LEFT JOIN orders o
        ON o.driver_id = u.id
       AND o.status IN ('" . implode("','", ACTIVE_DRIVER_STATUSES) . "')
    WHERE u.role = 'driver'
    GROUP BY u.id, u.fullname, u.is_online
    ORDER BY u.fullname
");
$products = $conn->query("SELECT * FROM products WHERE is_active = 1");

$adminPage = "orders";
$cssPath = "../style.css";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Management - Aquado</title>
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
                <h2>Order Management & Dispatching</h2>
            </div>
            <button class="btn btn-primary btn-sm" onclick="document.getElementById('manualModal').style.display='flex'">+ Manual Order Entry</button>
        </div>

        <?php if ($message !== "") { ?>
            <div class="success-message"><?php echo e($message); ?></div>
        <?php } ?>

        <table class="data-table">
            <thead>
                <tr>
                    <th>Order #</th>
                    <th>Customer</th>
                    <th>Address</th>
                    <th>Total</th>
                    <th>Payment</th>
                    <th>Status</th>
                    <th>Driver</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($order = $orders->fetch_assoc()) { ?>
                    <tr>
                        <td><?php echo e($order["order_number"]); ?></td>
                        <td><?php echo e($order["customer_name"]); ?></td>
                        <td style="max-width:200px;font-size:13px;"><?php echo e($order["customer_address"]); ?></td>
                        <td><?php echo formatPeso($order["total"]); ?></td>
                        <td><?php echo e($order["payment_method"]); ?><br><small style="color:var(--gray-500);"><?php echo e($order["payment_status"]); ?></small></td>
                        <td>
                            <span class="status-badge <?php echo statusBadgeClass($order["status"]); ?>"><?php echo e($order["status"]); ?></span>
                            <?php if ($order["status"] === "Cancelled") { ?>
                                <div style="font-size:12px;color:var(--gray-500);margin-top:4px;">
                                    <?php echo e($order["cancel_reason"] ?? ""); ?>
                                    <?php if ($order["cancelled_at"]) { ?><br><?php echo date("M d, h:i A", strtotime($order["cancelled_at"])); ?><?php } ?>
                                </div>
                            <?php } ?>
                        </td>
                        <td><?php echo e($order["driver_name"] ?? "—"); ?></td>
                        <td>
                            <div class="table-actions">
                                <?php if ($order["status"] === "Pending") { ?>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="order_id" value="<?php echo (int)$order["id"]; ?>">
                                        <button type="submit" name="confirm_order" class="btn btn-sm btn-primary">Accept Order</button>
                                    </form>
                                    <form method="POST" class="cancel-order-form" style="display:inline-flex;gap:4px;align-items:center;">
                                        <input type="hidden" name="order_id" value="<?php echo (int)$order["id"]; ?>">
                                        <input type="text" name="cancel_reason" placeholder="Reason (optional)" maxlength="200" style="padding:4px 8px;border:1px solid var(--gray-200);border-radius:6px;font-size:12px;width:130px;">
                                        <button type="submit" name="cancel_order" class="btn btn-sm btn-cancel"><i class="fa-solid fa-ban"></i> Cancel Order</button>
                                    </form>
                                <?php } ?>
                                <?php if ($order["status"] === "Delivered") { ?>
                                    <button type="button" class="btn btn-sm btn-secondary view-proof"
                                        data-order="<?php echo e($order["order_number"]); ?>"
                                        data-customer="<?php echo e($order["customer_name"]); ?>"
                                        data-driver="<?php echo e($order["driver_name"] ?? "—"); ?>"
                                        data-delivered="<?php echo $order["delivered_at"] ? e(date("M d, Y h:i A", strtotime($order["delivered_at"]))) : ""; ?>"
                                        data-empties="<?php echo $order["empty_containers_collected"] === null ? "" : (int)$order["empty_containers_collected"]; ?>"
                                        data-notes="<?php echo e($order["delivery_notes"] ?? ""); ?>"
                                        data-photo="<?php echo $order["delivery_photo"] ? "../delivery_proof.php?order_id=" . (int)$order["id"] : ""; ?>"
                                        data-payment="<?php echo $order["payment_proof"] ? "../payment_proof.php?order_id=" . (int)$order["id"] : ""; ?>">
                                        <i class="fa-solid fa-camera"></i> View Proof
                                    </button>
                                <?php } ?>
                                <?php if (in_array($order["status"], ["Confirmed", "Assigned"])) { ?>
                                    <form method="POST" style="display:inline-flex;gap:4px;align-items:center;">
                                        <input type="hidden" name="order_id" value="<?php echo (int)$order["id"]; ?>">
                                        <select name="driver_id" required style="padding:4px 8px;border-radius:6px;font-size:12px;">
                                            <?php
                                            $drivers->data_seek(0);
                                            while ($d = $drivers->fetch_assoc()) {
                                            ?>
                                                <option value="<?php echo (int)$d["id"]; ?>" <?php echo (int)$d["id"] === (int)$order["driver_id"] ? "selected" : ""; ?>><?php echo e($d["fullname"]); ?> (<?php echo (int)$d["active_orders"]; ?> active<?php echo (int)$d["is_online"] === 1 ? "" : ", offline"; ?>)</option>
                                            <?php } ?>
                                        </select>
                                        <button type="submit" name="dispatch_order" class="btn btn-sm btn-dark"><?php echo $order["status"] === "Assigned" ? "Reassign" : "Assign Driver"; ?></button>
                                    </form>
                                <?php } ?>
                                <?php if ($order["status"] === "Picked Up") { ?>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="order_id" value="<?php echo (int)$order["id"]; ?>">
                                        <button type="submit" name="mark_refilled" class="btn btn-sm btn-primary">Mark Washed & Refilled</button>
                                    </form>
                                <?php } ?>
                            </div>
                        </td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </main>
</div>

<div class="modal-overlay" id="manualModal" style="display:none;">
    <div class="modal-card" style="max-width:480px;text-align:left;">
        <button class="modal-close" onclick="document.getElementById('manualModal').style.display='none'">&times;</button>
        <h3 style="margin-bottom:20px;color:var(--green-dark);">Manual Order Entry</h3>
        <form method="POST">
            <div class="form-group">
                <label>Customer Name</label>
                <input type="text" name="customer_name" required>
            </div>
            <div class="form-group">
                <label>Phone</label>
                <input type="text" name="customer_phone">
            </div>
            <div class="form-group">
                <label>Address</label>
                <textarea name="customer_address" rows="2" required></textarea>
            </div>
            <div class="form-group">
                <label>Product</label>
                <select name="product_id" required>
                    <?php while ($p = $products->fetch_assoc()) { ?>
                        <option value="<?php echo (int)$p["id"]; ?>"><?php echo e($p["name"]); ?> — <?php echo formatPeso($p["price"]); ?></option>
                    <?php } ?>
                </select>
            </div>
            <div class="form-group">
                <label>Quantity</label>
                <input type="number" name="quantity" value="1" min="1" required>
            </div>
            <div class="form-group">
                <label>Payment</label>
                <select name="payment_method">
                    <option value="COD">Cash on Delivery</option>
                    <option value="GCash">GCash</option>
                    <option value="Cash">Cash</option>
                </select>
            </div>
            <button type="submit" name="manual_order" class="btn btn-primary btn-block">Create Order</button>
        </form>
    </div>
</div>

<!-- PROOF OF DELIVERY (admin / cashier review) -->
<div class="modal-overlay" id="proofModal" style="display:none;">
    <div class="modal-card" style="max-width:480px;text-align:left;">
        <button class="modal-close" onclick="document.getElementById('proofModal').style.display='none'">&times;</button>
        <h3 style="margin-bottom:16px;color:var(--green-dark);">Proof of Delivery <span id="proofOrder" style="font-size:14px;color:var(--gray-500);"></span></h3>
        <div class="receipt-line"><span>Customer</span><span id="proofCustomer"></span></div>
        <div class="receipt-line"><span>Rider</span><span id="proofDriver"></span></div>
        <div class="receipt-line"><span>Delivered on</span><span id="proofDelivered"></span></div>
        <div class="receipt-line"><span>Empty containers collected</span><span id="proofEmpties"></span></div>
        <div class="receipt-line"><span>Rider's notes</span><span id="proofNotes"></span></div>
        <div id="proofPhotoBox" style="margin-top:14px;">
            <p style="font-size:13px;font-weight:600;color:var(--green-dark);margin-bottom:6px;">Delivery Photo</p>
            <a id="proofPhotoLink" href="#" target="_blank"><img id="proofPhoto" alt="Delivery photo" style="width:100%;border-radius:8px;"></a>
        </div>
        <p id="proofNoPhoto" style="display:none;font-size:13px;color:var(--gray-500);margin-top:14px;">No delivery photo was submitted for this order (delivered before photos were required).</p>
        <div id="proofPaymentBox" style="margin-top:14px;display:none;">
            <p style="font-size:13px;font-weight:600;color:var(--green-dark);margin-bottom:6px;">GCash Payment Photo</p>
            <a id="proofPaymentLink" href="#" target="_blank"><img id="proofPayment" alt="GCash payment photo" style="width:100%;border-radius:8px;"></a>
        </div>
    </div>
</div>

<script>
// Show the delivery evidence the rider submitted for an order.
document.querySelectorAll(".view-proof").forEach(function(button) {
    button.addEventListener("click", function() {
        const d = button.dataset;
        document.getElementById("proofOrder").textContent = d.order;
        document.getElementById("proofCustomer").textContent = d.customer;
        document.getElementById("proofDriver").textContent = d.driver;
        document.getElementById("proofDelivered").textContent = d.delivered || "Not recorded";
        document.getElementById("proofEmpties").textContent = d.empties !== "" ? d.empties : "Not recorded";
        document.getElementById("proofNotes").textContent = d.notes || "—";

        const hasPhoto = d.photo !== "";
        document.getElementById("proofPhotoBox").style.display = hasPhoto ? "block" : "none";
        document.getElementById("proofNoPhoto").style.display = hasPhoto ? "none" : "block";
        if (hasPhoto) {
            document.getElementById("proofPhoto").src = d.photo;
            document.getElementById("proofPhotoLink").href = d.photo;
        }

        const hasPayment = d.payment !== "";
        document.getElementById("proofPaymentBox").style.display = hasPayment ? "block" : "none";
        if (hasPayment) {
            document.getElementById("proofPayment").src = d.payment;
            document.getElementById("proofPaymentLink").href = d.payment;
        }

        document.getElementById("proofModal").style.display = "flex";
    });
});

// Ask before cancelling an order.
document.querySelectorAll(".cancel-order-form").forEach(function(form) {
    form.addEventListener("submit", function(event) {
        if (!confirm("Cancel this order? The customer will be notified. This cannot be undone.")) {
            event.preventDefault();
        }
    });
});
</script>
</body>
</html>