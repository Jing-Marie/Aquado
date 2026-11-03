<?php

include "connections.php";
include "includes/auth.php";

header("Content-Type: application/json");

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "Please log in."]);
    exit;
}

/*
 * A customer can only see the driver's location for their own order,
 * and only while the driver is on the way to them.
 */
const TRACKABLE_STATUSES = ["Accepted", "Out for Delivery"];

$orderNum = $_GET["order"] ?? "";
$userId = currentUserId();

$stmt = $conn->prepare("
    SELECT
        o.status,
        o.driver_id,
        dl.latitude,
        dl.longitude,
        dl.last_updated
    FROM orders o
    LEFT JOIN driver_locations dl
        ON dl.driver_id = o.driver_id
    WHERE o.order_number = ?
      AND o.user_id = ?
    LIMIT 1
");

$stmt->bind_param("si", $orderNum, $userId);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$order) {
    http_response_code(404);
    echo json_encode(["success" => false, "message" => "Order not found."]);
    exit;
}

if (!in_array($order["status"], TRACKABLE_STATUSES, true) || $order["latitude"] === null) {
    echo json_encode([
        "success" => true,
        "status" => $order["status"],
        "tracking" => in_array($order["status"], TRACKABLE_STATUSES, true),
        "location" => null
    ]);
    exit;
}

echo json_encode([
    "success" => true,
    "status" => $order["status"],
    "tracking" => true,
    "location" => [
        "latitude" => (float)$order["latitude"],
        "longitude" => (float)$order["longitude"],
        "last_updated" => $order["last_updated"]
    ]
]);
