<?php

include "connections.php";
include "includes/auth.php";
require_once __DIR__ . "/../payment_proofs.php";

requireLogin();

// Customers can only view the photo for their own order.
$orderNum = $_GET["order"] ?? "";
$userId = currentUserId();

$stmt = $conn->prepare("SELECT payment_proof FROM orders WHERE order_number = ? AND user_id = ?");
$stmt->bind_param("si", $orderNum, $userId);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
$stmt->close();

outputPaymentProof($conn, $order["payment_proof"] ?? null);
