<?php

/*
|--------------------------------------------------------------------------
| ORDER WORKFLOW
|--------------------------------------------------------------------------
|
| Refill orders (requires_pickup = 1):
|   Pending → Confirmed → Assigned → Accepted → Picked Up
|   → Ready for Delivery → Out for Delivery → Delivered
|
| Orders without refills (containers / PET bottles) skip pickup & washing:
|   Pending → Confirmed → Assigned → Accepted → Out for Delivery → Delivered
|
| Every update only succeeds if the order is still in the expected status,
| so no step can be skipped, and is logged to delivery_tracking so the
| customer's tracking page shows it.
|
*/

require_once __DIR__ . "/../../order_actions.php";

const ACTIVE_DRIVER_STATUSES = [
    "Assigned",
    "Accepted",
    "Picked Up",
    "Ready for Delivery",
    "Out for Delivery",
];


/*
|--------------------------------------------------------------------------
| DRIVER BUTTON FOR AN ORDER: [action, label] (null action = disabled)
|--------------------------------------------------------------------------
*/

function driverButton($order) {

    switch ($order["status"]) {

        case "Assigned":
            return ["accept", "Accept Order"];

        case "Accepted":
            return $order["requires_pickup"]
                ? ["pickup", "Pick Up"]
                : ["deliver", "Deliver"];

        case "Picked Up":
            return [null, "Waiting for Washing and Refilling"];

        case "Ready for Delivery":
            return ["deliver", "Deliver"];

        case "Out for Delivery":
            return ["done", "Done / Delivered"];

    }

    return [null, $order["status"]];

}


/*
|--------------------------------------------------------------------------
| LOG THE ORDER'S CURRENT STATUS FOR CUSTOMER TRACKING
|--------------------------------------------------------------------------
*/

function logOrderStatus($conn, $orderId, $notes, $updatedBy) {

    $stmt = $conn->prepare("
        INSERT INTO delivery_tracking (order_id, status, notes, updated_by)
        SELECT id, status, ?, ?
        FROM orders
        WHERE id = ?
    ");

    $stmt->bind_param("sii", $notes, $updatedBy, $orderId);
    $stmt->execute();
    $stmt->close();

}


/*
|--------------------------------------------------------------------------
| DRIVER ACTIONS (only on orders assigned to this driver)
|--------------------------------------------------------------------------
*/

function applyDriverAction($conn, $orderId, $driverId, $action) {

    // action => [required current status, new status (SQL), tracking note]
    $rules = [
        "accept" => [
            "status = 'Assigned'",
            "'Accepted'",
            "Driver accepted the order",
        ],
        "pickup" => [
            "status = 'Accepted' AND requires_pickup = 1",
            "'Picked Up'",
            "Empty containers picked up by driver",
        ],
        "deliver" => [
            "(status = 'Ready for Delivery' OR (status = 'Accepted' AND requires_pickup = 0))",
            "'Out for Delivery'",
            "Driver is on the way with your order",
        ],
        "undo_deliver" => [
            "status = 'Out for Delivery'",
            "IF(requires_pickup = 1, 'Ready for Delivery', 'Accepted')",
            "Delivery start cancelled by driver",
        ],
    ];

    if (!isset($rules[$action])) {
        return false;
    }

    [$where, $newStatus, $notes] = $rules[$action];

    $stmt = $conn->prepare("
        UPDATE orders
        SET status = $newStatus
        WHERE id = ? AND driver_id = ? AND $where
    ");

    $stmt->bind_param("ii", $orderId, $driverId);
    $stmt->execute();
    $changed = $stmt->affected_rows === 1;
    $stmt->close();

    if ($changed) {
        logOrderStatus($conn, $orderId, $notes, $driverId);
    }

    return $changed;

}


/*
|--------------------------------------------------------------------------
| PAYMENT FLOW
|--------------------------------------------------------------------------
|
| Unpaid → For Verification (rider collected the cash, or uploaded a photo
| of the customer's GCash payment) → Paid (cashier confirmed)
| or Rejected (cashier rejected the GCash photo; the rider can upload a new one).
|
*/


/*
|--------------------------------------------------------------------------
| DRIVER COMPLETES THE DELIVERY AND COLLECTS THE CUSTOMER'S PAYMENT
|--------------------------------------------------------------------------
*/

function completeDelivery($conn, $orderId, $driverId, $notes, $proofFile, $deliveryPhoto, $emptyContainers) {

    // Proof of delivery: photo, empty containers collected, and the exact date and time.
    $stmt = $conn->prepare("
        UPDATE orders
        SET status = 'Delivered',
            payment_status = 'For Verification',
            payment_proof = ?,
            payment_collected_at = NOW(),
            delivery_notes = ?,
            delivery_photo = ?,
            empty_containers_collected = ?,
            delivered_at = NOW()
        WHERE id = ? AND driver_id = ? AND status = 'Out for Delivery'
    ");

    $stmt->bind_param("sssiii", $proofFile, $notes, $deliveryPhoto, $emptyContainers, $orderId, $driverId);
    $stmt->execute();
    $changed = $stmt->affected_rows === 1;
    $stmt->close();

    if ($changed) {

        $order = $conn->query("SELECT payment_method, total, delivered_at FROM orders WHERE id = " . (int)$orderId)->fetch_assoc();

        // The delivery evidence is saved in the order history (delivery_tracking).
        $evidence = " Delivered on " . date("M d, Y h:i A", strtotime($order["delivered_at"]))
            . ". Delivery photo uploaded. "
            . $emptyContainers . " empty container(s) collected.";

        logOrderStatus(
            $conn,
            $orderId,
            ($order["payment_method"] === "GCash"
                ? "Delivery completed. GCash payment of " . formatPeso($order["total"]) . " received by the rider (photo uploaded), awaiting cashier confirmation."
                : "Delivery completed. Cash payment of " . formatPeso($order["total"]) . " collected by the rider, awaiting cashier confirmation.")
                . $evidence,
            $driverId
        );

        // Last delivery done: the rider's location is no longer shared.
        stopSharingIfIdle($conn, $driverId);

    }

    return $changed;

}


/*
|--------------------------------------------------------------------------
| RIDER LOCATION SHARING
|--------------------------------------------------------------------------
|
| The rider's GPS position is only shared while the rider is Online and has
| an active delivery. Going Offline, or finishing the last delivery, stops it.
|
*/

function driverActiveOrderCount($conn, $driverId) {

    $stmt = $conn->prepare("
        SELECT COUNT(*) AS cnt
        FROM orders
        WHERE driver_id = ? AND status IN ('" . implode("','", ACTIVE_DRIVER_STATUSES) . "')
    ");

    $stmt->bind_param("i", $driverId);
    $stmt->execute();
    $count = (int)$stmt->get_result()->fetch_assoc()["cnt"];
    $stmt->close();

    return $count;

}


function stopSharingLocation($conn, $driverId) {

    $stmt = $conn->prepare("UPDATE driver_locations SET is_sharing = 0 WHERE driver_id = ?");
    $stmt->bind_param("i", $driverId);
    $stmt->execute();
    $stmt->close();

}


function stopSharingIfIdle($conn, $driverId) {

    if (driverActiveOrderCount($conn, $driverId) === 0) {
        stopSharingLocation($conn, $driverId);
    }

}


function isDriverOnline($conn, $driverId) {

    $stmt = $conn->prepare("SELECT is_online FROM users WHERE id = ?");
    $stmt->bind_param("i", $driverId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return $row && (int)$row["is_online"] === 1;

}


function setDriverOnline($conn, $driverId, $online) {

    $online = $online ? 1 : 0;

    $stmt = $conn->prepare("UPDATE users SET is_online = ? WHERE id = ? AND role = 'driver'");
    $stmt->bind_param("ii", $online, $driverId);
    $stmt->execute();
    $stmt->close();

    // Going Offline stops the location sharing right away.
    if (!$online) {
        stopSharingLocation($conn, $driverId);
    }

}


/*
|--------------------------------------------------------------------------
| DRIVER UPLOADS A NEW GCASH PHOTO AFTER THE CASHIER REJECTED IT
|--------------------------------------------------------------------------
*/

function resubmitPaymentProof($conn, $orderId, $driverId, $proofFile) {

    $stmt = $conn->prepare("
        UPDATE orders
        SET payment_status = 'For Verification',
            payment_proof = ?,
            payment_note = NULL
        WHERE id = ? AND driver_id = ? AND payment_status = 'Rejected'
    ");

    $stmt->bind_param("sii", $proofFile, $orderId, $driverId);
    $stmt->execute();
    $changed = $stmt->affected_rows === 1;
    $stmt->close();

    if ($changed) {
        logOrderStatus($conn, $orderId, "New GCash payment photo uploaded by the rider, awaiting cashier confirmation", $driverId);
    }

    return $changed;

}


/*
|--------------------------------------------------------------------------
| CASHIER CONFIRMS OR REJECTS A PAYMENT
|--------------------------------------------------------------------------
*/

function confirmPayment($conn, $orderId, $staffId) {

    $stmt = $conn->prepare("
        UPDATE orders
        SET payment_status = 'Paid',
            paid_at = NOW(),
            payment_confirmed_by = ?,
            payment_note = NULL
        WHERE id = ? AND payment_status = 'For Verification'
    ");

    $stmt->bind_param("ii", $staffId, $orderId);
    $stmt->execute();
    $changed = $stmt->affected_rows === 1;
    $stmt->close();

    if ($changed) {
        logOrderStatus($conn, $orderId, "Payment confirmed by the cashier. Thank you!", $staffId);
    }

    return $changed;

}


function rejectPayment($conn, $orderId, $staffId, $reason) {

    $reason = $reason !== "" ? $reason : "The GCash payment could not be verified.";

    $stmt = $conn->prepare("
        UPDATE orders
        SET payment_status = 'Rejected',
            payment_confirmed_by = ?,
            payment_note = ?
        WHERE id = ? AND payment_status = 'For Verification' AND payment_method = 'GCash'
    ");

    $stmt->bind_param("isi", $staffId, $reason, $orderId);
    $stmt->execute();
    $changed = $stmt->affected_rows === 1;
    $stmt->close();

    if ($changed) {
        logOrderStatus($conn, $orderId, "GCash payment not verified: " . $reason, $staffId);
    }

    return $changed;

}


/*
|--------------------------------------------------------------------------
| ADMIN / CASHIER ACTIONS
|--------------------------------------------------------------------------
*/

function confirmOrder($conn, $orderId, $staffId) {

    $stmt = $conn->prepare("UPDATE orders SET status = 'Confirmed' WHERE id = ? AND status = 'Pending'");
    $stmt->bind_param("i", $orderId);
    $stmt->execute();
    $changed = $stmt->affected_rows === 1;
    $stmt->close();

    if ($changed) {
        logOrderStatus($conn, $orderId, "Order accepted by staff", $staffId);
    }

    return $changed;

}


// Cancelling is allowed only while the order is Pending (see order_actions.php).
function cancelOrder($conn, $orderId, $staffId, $reason) {

    return cancelPendingOrder($conn, $orderId, $staffId, currentRole(), $reason);

}


function assignDriver($conn, $orderId, $driverId, $staffId) {

    $check = $conn->prepare("SELECT id FROM users WHERE id = ? AND role = 'driver'");
    $check->bind_param("i", $driverId);
    $check->execute();
    $isDriver = $check->get_result()->num_rows === 1;
    $check->close();

    if (!$isDriver) {
        return false;
    }

    // Reassigning is allowed until the driver accepts the order.
    $stmt = $conn->prepare("
        UPDATE orders
        SET status = 'Assigned', driver_id = ?
        WHERE id = ? AND status IN ('Confirmed', 'Assigned')
    ");
    $stmt->bind_param("ii", $driverId, $orderId);
    $stmt->execute();
    $changed = $stmt->affected_rows === 1;
    $stmt->close();

    if ($changed) {
        logOrderStatus($conn, $orderId, "Order assigned to driver", $staffId);
    }

    return $changed;

}


function markWashedAndRefilled($conn, $orderId, $staffId) {

    $stmt = $conn->prepare("UPDATE orders SET status = 'Ready for Delivery' WHERE id = ? AND status = 'Picked Up'");
    $stmt->bind_param("i", $orderId);
    $stmt->execute();
    $changed = $stmt->affected_rows === 1;
    $stmt->close();

    if ($changed) {
        logOrderStatus($conn, $orderId, "Containers washed and refilled at the station", $staffId);
    }

    return $changed;

}
