<?php

include "../connections.php";
include "../includes/auth.php";
include "../includes/workflow.php";

requireRole("driver", "../login.php");

header("Content-Type: application/json");

$driverId = currentUserId();

$latitude = isset($_POST["latitude"]) ? (float)$_POST["latitude"] : null;
$longitude = isset($_POST["longitude"]) ? (float)$_POST["longitude"] : null;


// Check required data
if (
    $latitude === null ||
    $longitude === null ||
    abs($latitude) > 90 ||
    abs($longitude) > 180
) {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Missing or invalid location."
    ]);

    exit;
}


// The location is only shared while the rider is Online and has an active delivery.
if (!isDriverOnline($conn, $driverId)) {

    stopSharingLocation($conn, $driverId);

    echo json_encode([
        "success" => false,
        "stop" => true,
        "message" => "You are offline. Location sharing is off."
    ]);

    exit;
}

if (driverActiveOrderCount($conn, $driverId) === 0) {

    stopSharingLocation($conn, $driverId);

    echo json_encode([
        "success" => false,
        "stop" => true,
        "message" => "No active delivery. Location sharing is off."
    ]);

    exit;
}


// Save / update this driver's latest location
$stmt = $conn->prepare("
    INSERT INTO driver_locations
        (driver_id, latitude, longitude, is_sharing)
    VALUES
        (?, ?, ?, 1)
    ON DUPLICATE KEY UPDATE
        latitude = VALUES(latitude),
        longitude = VALUES(longitude),
        is_sharing = 1,
        last_updated = CURRENT_TIMESTAMP
");

$stmt->bind_param("idd", $driverId, $latitude, $longitude);

if ($stmt->execute()) {

    echo json_encode([
        "success" => true,
        "message" => "Location saved."
    ]);

} else {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Could not save driver location."
    ]);
}

$stmt->close();
