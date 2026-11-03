<?php
include "../connections.php";
include "../includes/auth.php";
include "../includes/workflow.php";
include "../includes/payment_proof.php";

requireRole("driver", "../login.php");
$driverId = currentUserId();

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: index.php");
    exit();
}

$orderId = (int)($_POST["order_id"] ?? 0);
$action = $_POST["action"] ?? "";

function backToOrder($orderId, $error) {
    $_SESSION["order_error"] = $error;
    header("Location: order.php?id=" . $orderId);
    exit();
}

if ($action === "done" || $action === "resubmit_proof") {

    $stmt = $conn->prepare("SELECT payment_method, payment_proof FROM orders WHERE id = ? AND driver_id = ?");
    $stmt->bind_param("ii", $orderId, $driverId);
    $stmt->execute();
    $order = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$order) {
        header("Location: index.php?error=1");
        exit();
    }

    // The rider must confirm they received the customer's payment.
    if ($action === "done" && empty($_POST["payment_received"])) {
        backToOrder($orderId, "Please confirm the customer's payment to complete the delivery.");
    }

    // GCash payments need a photo of the customer's payment.
    $proofFile = null;
    if ($order["payment_method"] === "GCash") {
        $error = "";
        $proofFile = savePaymentProof($conn, $_FILES["payment_proof"] ?? null, $error);
        if ($proofFile === null) {
            backToOrder($orderId, $error);
        }
    }

    if ($action === "resubmit_proof") {
        if ($proofFile !== null && resubmitPaymentProof($conn, $orderId, $driverId, $proofFile)) {
            // The rejected photo is no longer needed.
            deletePaymentProof($conn, $order["payment_proof"]);
            header("Location: order.php?id=" . $orderId . "&uploaded=1");
        } else {
            if ($proofFile !== null) {
                deletePaymentProof($conn, $proofFile);
            }
            backToOrder($orderId, "The photo could not be submitted for this order.");
        }
        exit();
    }

    if (completeDelivery($conn, $orderId, $driverId, trim($_POST["notes"] ?? ""), $proofFile)) {
        header("Location: history.php?completed=1");
    } else {
        if ($proofFile !== null) {
            deletePaymentProof($conn, $proofFile);
        }
        header("Location: index.php?error=1");
    }
    exit();

}

$ok = applyDriverAction($conn, $orderId, $driverId, $action);

if (!$ok) {
    header("Location: index.php?error=1");
} elseif ($action === "deliver") {
    header("Location: index.php?started=" . $orderId);
} else {
    header("Location: index.php");
}
exit();
