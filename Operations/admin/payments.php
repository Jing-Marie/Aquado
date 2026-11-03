<?php
include "../connections.php";
include "../includes/auth.php";
include "../includes/workflow.php";

requireRole(["admin", "cashier"], "../login.php");
$user = loadCurrentUser($conn);
$staffId = currentUserId();
$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $id = (int)($_POST["order_id"] ?? 0);
    if (isset($_POST["confirm_payment"])) {
        $message = confirmPayment($conn, $id, $staffId)
            ? "Payment confirmed. The customer's receipt now shows Paid."
            : "Payment could not be confirmed (it may have already been handled).";
    }
    if (isset($_POST["reject_payment"])) {
        $message = rejectPayment($conn, $id, $staffId, trim($_POST["reason"] ?? ""))
            ? "GCash photo rejected. The rider was asked to send a new one."
            : "Payment could not be rejected (it may have already been handled).";
    }
}

// Payments the riders collected, waiting for the cashier.
$pending = $conn->query("
    SELECT o.*, u.fullname AS driver_name
    FROM orders o
    LEFT JOIN users u ON u.id = o.driver_id
    WHERE o.payment_status = 'For Verification'
    ORDER BY u.fullname, o.payment_collected_at
");

// What each rider should hand over / has sent for checking.
$byDriver = $conn->query("
    SELECT
        u.fullname AS driver_name,
        COUNT(*) AS payments,
        COALESCE(SUM(CASE WHEN o.payment_method <> 'GCash' THEN o.total END), 0) AS cash_total,
        COALESCE(SUM(CASE WHEN o.payment_method = 'GCash' THEN o.total END), 0) AS gcash_total
    FROM orders o
    LEFT JOIN users u ON u.id = o.driver_id
    WHERE o.payment_status = 'For Verification'
    GROUP BY u.id, u.fullname
    ORDER BY u.fullname
");

$recent = $conn->query("
    SELECT o.*, d.fullname AS driver_name, c.fullname AS confirmed_by
    FROM orders o
    LEFT JOIN users d ON d.id = o.driver_id
    LEFT JOIN users c ON c.id = o.payment_confirmed_by
    WHERE o.payment_status IN ('Paid', 'Rejected')
    ORDER BY COALESCE(o.paid_at, o.payment_collected_at) DESC
    LIMIT 20
");

$adminPage = "payments";
$cssPath = "../style.css";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payments - Aquado</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="../style.css?v=<?php echo time(); ?>">
    <?php include __DIR__ . "/../includes/pwa_head.php"; ?>
</head>
<body>
<div class="admin-layout">
    <?php include "includes/sidebar.php"; ?>

    <main class="admin-main">
        <div class="admin-header-bar">
            <div>
                <p class="page-subtitle" style="text-transform:uppercase;letter-spacing:1px;font-size:12px;">Station Management Portal</p>
                <h2>Payment Confirmation</h2>
            </div>
        </div>

        <?php if ($message !== "") { ?>
            <div class="success-message"><?php echo e($message); ?></div>
        <?php } ?>

        <?php if ($byDriver->num_rows > 0) { ?>
            <div class="stats-grid">
                <?php while ($d = $byDriver->fetch_assoc()) { ?>
                    <div class="stat-card">
                        <h4><?php echo e($d["driver_name"] ?? "No rider"); ?> · <?php echo (int)$d["payments"]; ?> payment(s)</h4>
                        <div class="stat-value"><?php echo formatPeso($d["cash_total"]); ?></div>
                        <p style="font-size:13px;color:var(--gray-500);margin-top:8px;">
                            Cash to hand over · GCash to check: <?php echo formatPeso($d["gcash_total"]); ?>
                        </p>
                    </div>
                <?php } ?>
            </div>
        <?php } ?>

        <h3 style="color:var(--green-dark);margin-bottom:16px;">Waiting for Confirmation</h3>

        <?php if ($pending->num_rows === 0) { ?>
            <div class="card">
                <p style="color:var(--gray-500);">No payments to confirm right now.</p>
            </div>
        <?php } else { ?>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Order #</th>
                        <th>Customer</th>
                        <th>Rider</th>
                        <th>Amount</th>
                        <th>Payment</th>
                        <th>Collected</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($o = $pending->fetch_assoc()) { ?>
                        <tr>
                            <td><?php echo e($o["order_number"]); ?></td>
                            <td><?php echo e($o["customer_name"]); ?></td>
                            <td><?php echo e($o["driver_name"] ?? "—"); ?></td>
                            <td><strong><?php echo formatPeso($o["total"]); ?></strong></td>
                            <td>
                                <?php echo e($o["payment_method"]); ?>
                                <?php if ($o["payment_proof"]) { ?>
                                    <br>
                                    <a href="../payment_proof.php?order_id=<?php echo (int)$o["id"]; ?>" target="_blank" title="Open full photo">
                                        <img src="../payment_proof.php?order_id=<?php echo (int)$o["id"]; ?>" alt="GCash payment photo" style="width:90px;max-height:120px;object-fit:cover;border-radius:6px;margin-top:6px;">
                                    </a>
                                <?php } ?>
                            </td>
                            <td style="font-size:13px;"><?php echo $o["payment_collected_at"] ? date("M d, h:i A", strtotime($o["payment_collected_at"])) : "—"; ?></td>
                            <td>
                                <form method="POST" style="margin-bottom:6px;">
                                    <input type="hidden" name="order_id" value="<?php echo (int)$o["id"]; ?>">
                                    <button type="submit" name="confirm_payment" class="btn btn-sm btn-primary">
                                        <?php echo $o["payment_method"] === "GCash" ? "Confirm GCash Payment" : "Confirm Cash Received"; ?>
                                    </button>
                                </form>
                                <?php if ($o["payment_method"] === "GCash") { ?>
                                    <form method="POST" style="display:flex;gap:4px;">
                                        <input type="hidden" name="order_id" value="<?php echo (int)$o["id"]; ?>">
                                        <input type="text" name="reason" placeholder="Reason (optional)" style="padding:4px 8px;border:1px solid var(--gray-200);border-radius:6px;font-size:12px;width:130px;">
                                        <button type="submit" name="reject_payment" class="btn btn-sm btn-secondary" onclick="return confirm('Reject this GCash photo?');">Reject</button>
                                    </form>
                                <?php } ?>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        <?php } ?>

        <h3 style="color:var(--green-dark);margin:32px 0 16px;">Recently Handled</h3>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Order #</th>
                    <th>Customer</th>
                    <th>Rider</th>
                    <th>Amount</th>
                    <th>Payment</th>
                    <th>Status</th>
                    <th>Handled By</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($recent->num_rows === 0) { ?>
                    <tr><td colspan="7" style="color:var(--gray-500);">Nothing yet.</td></tr>
                <?php } ?>
                <?php while ($o = $recent->fetch_assoc()) { ?>
                    <tr>
                        <td><?php echo e($o["order_number"]); ?></td>
                        <td><?php echo e($o["customer_name"]); ?></td>
                        <td><?php echo e($o["driver_name"] ?? "—"); ?></td>
                        <td><?php echo formatPeso($o["total"]); ?></td>
                        <td>
                            <?php echo e($o["payment_method"]); ?>
                            <?php if ($o["payment_proof"]) { ?>
                                · <a href="../payment_proof.php?order_id=<?php echo (int)$o["id"]; ?>" target="_blank">View photo</a>
                            <?php } ?>
                        </td>
                        <td>
                            <span class="status-badge <?php echo paymentBadgeClass($o["payment_status"]); ?>"><?php echo e($o["payment_status"]); ?></span>
                            <?php if ($o["payment_status"] === "Rejected" && $o["payment_note"]) { ?>
                                <div style="font-size:12px;color:var(--gray-500);margin-top:4px;"><?php echo e($o["payment_note"]); ?></div>
                            <?php } ?>
                        </td>
                        <td style="font-size:13px;">
                            <?php echo e($o["confirmed_by"] ?? "—"); ?>
                            <?php if ($o["paid_at"]) { ?><br><?php echo date("M d, h:i A", strtotime($o["paid_at"])); ?><?php } ?>
                        </td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </main>
</div>
</body>
</html>
