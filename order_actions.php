<?php

/*
|--------------------------------------------------------------------------
| SHARED ORDER ACTIONS (used by the customer website and the Operations app)
|--------------------------------------------------------------------------
|
| Cancelling an order and notifying people about it. Both apps include this
| file so the rules live in one place:
|
|   - An order can only be cancelled while it is still "Pending".
|   - The status change is a single UPDATE that only succeeds while the order
|     is still Pending, so a cancellation can never overwrite an order that
|     staff accepted a moment earlier.
|
*/

const CANCELLABLE_STATUS = "Pending";


/*
|--------------------------------------------------------------------------
| NOTIFICATIONS
|--------------------------------------------------------------------------
*/

function notifyUser($conn, $userId, $orderId, $type, $message) {

    if (!$userId) {
        return;
    }

    $stmt = $conn->prepare("INSERT INTO notifications (user_id, order_id, type, message) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("iiss", $userId, $orderId, $type, $message);
    $stmt->execute();
    $stmt->close();

}


// Every admin and cashier account gets the notification.
function notifyStaff($conn, $orderId, $type, $message) {

    $staff = $conn->query("SELECT id FROM users WHERE role IN ('admin', 'cashier')");

    while ($row = $staff->fetch_assoc()) {
        notifyUser($conn, (int)$row["id"], $orderId, $type, $message);
    }

}


function unreadNotificationCount($conn, $userId) {

    $stmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $count = (int)$stmt->get_result()->fetch_assoc()["cnt"];
    $stmt->close();

    return $count;

}


/*
|--------------------------------------------------------------------------
| CANCEL A PENDING ORDER
|--------------------------------------------------------------------------
|
| $actorRole is "customer" (the order's owner) or "admin" / "cashier".
| Returns true when the order was cancelled, false when it could not be
| (not found, not the customer's own order, or no longer Pending).
|
*/

function cancelPendingOrder($conn, $orderId, $actorId, $actorRole, $reason = "") {

    $isCustomer = $actorRole === "customer";

    $reason = trim((string)$reason);

    if ($reason === "") {
        $reason = $isCustomer ? "Cancelled by the customer" : "Cancelled by Aquado staff";
    }

    $reason = function_exists("mb_substr") ? mb_substr($reason, 0, 200) : substr($reason, 0, 200);

    $sql = "
        UPDATE orders
        SET status = 'Cancelled',
            cancelled_at = NOW(),
            cancelled_by = ?,
            cancel_reason = ?
        WHERE id = ? AND status = '" . CANCELLABLE_STATUS . "'
    ";

    if ($isCustomer) {
        $sql .= " AND user_id = ?";
    }

    $stmt = $conn->prepare($sql);

    if ($isCustomer) {
        $stmt->bind_param("isii", $actorId, $reason, $orderId, $actorId);
    } else {
        $stmt->bind_param("isi", $actorId, $reason, $orderId);
    }

    $stmt->execute();
    $changed = $stmt->affected_rows === 1;
    $stmt->close();

    if (!$changed) {
        return false;
    }


    $stmt = $conn->prepare("SELECT order_number, user_id, customer_name FROM orders WHERE id = ?");
    $stmt->bind_param("i", $orderId);
    $stmt->execute();
    $order = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $stmt = $conn->prepare("SELECT fullname FROM users WHERE id = ?");
    $stmt->bind_param("i", $actorId);
    $stmt->execute();
    $actor = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $actorName = $actor["fullname"] ?? "Staff";
    $number = $order["order_number"];


    // Shows up in the customer's tracking activity log.
    $note = $isCustomer
        ? "Order cancelled by the customer. Reason: " . $reason
        : "Order cancelled by Aquado staff (" . $actorName . "). Reason: " . $reason;

    $stmt = $conn->prepare("INSERT INTO delivery_tracking (order_id, status, notes, updated_by) VALUES (?, 'Cancelled', ?, ?)");
    $stmt->bind_param("isi", $orderId, $note, $actorId);
    $stmt->execute();
    $stmt->close();


    if ($isCustomer) {

        notifyUser($conn, $order["user_id"], $orderId, "order_cancelled", "You cancelled order $number.");

        notifyStaff($conn, $orderId, "order_cancelled", "Customer " . $order["customer_name"] . " cancelled order $number (it was still pending).");

    } else {

        notifyUser($conn, $order["user_id"], $orderId, "order_cancelled", "Your order $number was cancelled by Aquado. Reason: $reason");

        notifyStaff($conn, $orderId, "order_cancelled", "Order $number ($order[customer_name]) was cancelled by $actorName. Reason: $reason");

    }

    return true;

}
