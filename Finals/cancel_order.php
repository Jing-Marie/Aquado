<?php

include "connections.php";
include "includes/auth.php";
require_once __DIR__ . "/../order_actions.php";

requireLogin();

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: order_history.php");
    exit();
}

$userId = currentUserId();
$orderNum = trim($_POST["order"] ?? "");
$back = ($_POST["return"] ?? "") === "tracking" ? "tracking" : "orders";

$stmt = $conn->prepare("SELECT id FROM orders WHERE order_number = ? AND user_id = ?");
$stmt->bind_param("si", $orderNum, $userId);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Only Pending orders can be cancelled; anything further along is refused.
if ($order && cancelPendingOrder($conn, (int)$order["id"], $userId, "customer", $_POST["reason"] ?? "")) {
    $_SESSION["order_notice"] = ["success", "Order " . $orderNum . " has been cancelled."];
} else {
    $_SESSION["order_notice"] = ["error", "This order can no longer be cancelled. Only orders that are still Pending can be cancelled. Please contact us at 09228112567."];
}

header("Location: order_history.php?tab=" . $back . "&order=" . urlencode($orderNum));
exit();
