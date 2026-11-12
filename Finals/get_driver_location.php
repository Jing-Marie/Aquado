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

// After this many seconds without a new GPS fix the position is shown as the "last known" one.
const LOCATION_STALE_SECONDS = 120;

$orderNum = $_GET["order"] ?? "";
$userId = currentUserId();

$stmt = $conn->prepare("
    SELECT
        o.status,
        o.driver_id,
        u.is_online,
        dl.latitude,
        dl.longitude,
        dl.is_sharing,
        dl.last_updated,
        TIMESTAMPDIFF(SECOND, dl.last_updated, NOW()) AS seconds_ago
    FROM orders o
    LEFT JOIN users u
        ON u.id = o.driver_id
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

$tracking = in_array($order["status"], TRACKABLE_STATUSES, true);

if (!$tracking || $order["latitude"] === null) {
    echo json_encode([
        "success" => true,
        "status" => $order["status"],
        "tracking" => $tracking,
        "location" => null
    ]);
    exit;
}

// The rider went Offline (or finished their deliveries): the location is no longer shared.
$sharing = (int)$order["is_online"] === 1 && (int)$order["is_sharing"] === 1;

if (!$sharing) {
    echo json_encode([
        "success" => true,
        "status" => $order["status"],
        "tracking" => true,
        "sharing" => false,
        "driver_online" => (int)$order["is_online"] === 1,
        "location" => null
    ]);
    exit;
}

$secondsAgo = max(0, (int)$order["seconds_ago"]);

echo json_encode([
    "success" => true,
    "status" => $order["status"],
    "tracking" => true,
    "sharing" => true,
    "driver_online" => true,
    "location" => [
        "latitude" => (float)$order["latitude"],
        "longitude" => (float)$order["longitude"],
        "last_updated" => $order["last_updated"],
        "seconds_ago" => $secondsAgo,
        // GPS signal temporarily lost: this is the last position we received.
        "stale" => $secondsAgo > LOCATION_STALE_SECONDS
    ]
]);
