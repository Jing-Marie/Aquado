<?php

include "connections.php";
include "includes/auth.php";
require_once __DIR__ . "/../payment_proofs.php";

requireLogin();

// Customers can only view the delivery photo of their own order.
$orderNum = $_GET["order"] ?? "";
$userId = currentUserId();

$stmt = $conn->prepare("SELECT delivery_photo FROM orders WHERE order_number = ? AND user_id = ?");
$stmt->bind_param("si", $orderNum, $userId);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Delivery photos are stored in the database, like the GCash payment photos.
outputPaymentProof($conn, $order["delivery_photo"] ?? null);
