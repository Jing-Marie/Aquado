<?php

include "connections.php";
include "includes/auth.php";

header("Content-Type: application/json");

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(["success" => false]);
    exit;
}

// Lets the open receipt page notice when the rider or cashier updates the order.
$orderNum = $_GET["order"] ?? "";
$userId = currentUserId();

$stmt = $conn->prepare("SELECT status, payment_status, payment_proof FROM orders WHERE order_number = ? AND user_id = ?");
$stmt->bind_param("si", $orderNum, $userId);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$order) {
    http_response_code(404);
    echo json_encode(["success" => false]);
    exit;
}

echo json_encode([
    "success" => true,
    "version" => md5($order["status"] . "|" . $order["payment_status"] . "|" . $order["payment_proof"])
]);
