<?php

include "connections.php";
include "includes/auth.php";
include "includes/payment_proof.php";

requireLogin();

// Admin and cashier can view any payment photo; drivers only their own orders.
$orderId = (int)($_GET["order_id"] ?? 0);

$stmt = $conn->prepare("SELECT payment_proof, driver_id FROM orders WHERE id = ?");
$stmt->bind_param("i", $orderId);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$order || (currentRole() === "driver" && (int)$order["driver_id"] !== currentUserId())) {
    http_response_code(404);
    exit;
}

outputPaymentProof($conn, $order["payment_proof"]);
