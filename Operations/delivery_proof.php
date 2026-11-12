<?php

include "connections.php";
include "includes/auth.php";
include "includes/payment_proof.php";

requireLogin();

// Admin and cashier can review any delivery photo; drivers only their own orders.
$orderId = (int)($_GET["order_id"] ?? 0);

$stmt = $conn->prepare("SELECT delivery_photo, driver_id FROM orders WHERE id = ?");
$stmt->bind_param("i", $orderId);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$order || (currentRole() === "driver" && (int)$order["driver_id"] !== currentUserId())) {
    http_response_code(404);
    exit;
}

// Delivery photos are stored in the database, like the GCash payment photos.
outputPaymentProof($conn, $order["delivery_photo"]);
