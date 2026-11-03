<?php

include "../connections.php";
include "../includes/auth.php";

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


// Save / update this driver's latest location
$stmt = $conn->prepare("
    INSERT INTO driver_locations
        (driver_id, latitude, longitude)
    VALUES
        (?, ?, ?)
    ON DUPLICATE KEY UPDATE
        latitude = VALUES(latitude),
        longitude = VALUES(longitude),
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
