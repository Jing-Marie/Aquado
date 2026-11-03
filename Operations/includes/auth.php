<?php

/*
 * Own session cookie so the Operations app and the customer website
 * never share a login.
 */

require_once __DIR__ . "/../../db.php";

if (session_status() === PHP_SESSION_NONE) {
    session_set_save_handler(new AquadoSessionHandler(), true);
    session_name("AQUADO_OPS");
    session_start();
}

// The staff app never appears in search engines.
header("X-Robots-Tag: noindex, nofollow");


/*
|--------------------------------------------------------------------------
| ROLES ALLOWED IN THE OPERATIONS APP
|--------------------------------------------------------------------------
*/

const STAFF_ROLES = ["admin", "cashier", "driver"];


/*
|--------------------------------------------------------------------------
| ESCAPE HTML
|--------------------------------------------------------------------------
*/

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}


/*
|--------------------------------------------------------------------------
| CHECK IF STAFF IS LOGGED IN
|--------------------------------------------------------------------------
*/

function isLoggedIn() {
    return isset($_SESSION["user_id"])
        && !empty($_SESSION["user_id"])
        && in_array(currentRole(), STAFF_ROLES, true);
}


/*
|--------------------------------------------------------------------------
| GET CURRENT USER ID
|--------------------------------------------------------------------------
*/

function currentUserId() {
    return (int)($_SESSION["user_id"] ?? 0);
}


/*
|--------------------------------------------------------------------------
| GET CURRENT USER ROLE
|--------------------------------------------------------------------------
*/

function currentRole() {
    return strtolower((string)($_SESSION["role"] ?? ""));
}


/*
|--------------------------------------------------------------------------
| REQUIRE LOGIN
|--------------------------------------------------------------------------
*/

function requireLogin($redirect = "login.php") {

    if (!isLoggedIn()) {
        header("Location: $redirect");
        exit();
    }

}


/*
|--------------------------------------------------------------------------
| REQUIRE SPECIFIC ROLE
|--------------------------------------------------------------------------
*/

function requireRole($roles, $redirect = "../login.php") {

    requireLogin($redirect);

    $roles = (array)$roles;

    if (!in_array(currentRole(), $roles, true)) {
        header("Location: $redirect");
        exit();
    }

}


/*
|--------------------------------------------------------------------------
| REDIRECT STAFF BASED ON ROLE (paths relative to the Operations root)
|--------------------------------------------------------------------------
*/

function redirectByRole() {

    if (currentRole() === "driver") {

        header("Location: driver/index.php");
        exit();

    }

    header("Location: admin/index.php");
    exit();

}


/*
|--------------------------------------------------------------------------
| LOAD CURRENT USER
|--------------------------------------------------------------------------
*/

function loadCurrentUser($conn) {

    if (!isLoggedIn()) {
        return null;
    }

    $userId = currentUserId();

    $stmt = $conn->prepare("
        SELECT
            id,
            fullname,
            email,
            role,
            contact_number,
            address
        FROM users
        WHERE id = ?
        LIMIT 1
    ");

    if (!$stmt) {
        return null;
    }

    $stmt->bind_param("i", $userId);

    $stmt->execute();

    $user = $stmt->get_result()->fetch_assoc();

    $stmt->close();

    return $user;

}


/*
|--------------------------------------------------------------------------
| LOGOUT USER
|--------------------------------------------------------------------------
*/

function logoutUser() {

    $_SESSION = [];

    if (ini_get("session.use_cookies")) {

        $params = session_get_cookie_params();

        setcookie(
            session_name(),
            "",
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );

    }

    session_destroy();

}


/*
|--------------------------------------------------------------------------
| GENERATE ORDER NUMBER
|--------------------------------------------------------------------------
*/

function generateOrderNumber() {
    return "AQ-" . date("Ymd") . "-" . strtoupper(substr(uniqid(), -4));
}


/*
|--------------------------------------------------------------------------
| FORMAT PESO
|--------------------------------------------------------------------------
*/

function formatPeso($amount) {
    return "₱" . number_format((float)$amount, 2);
}


/*
|--------------------------------------------------------------------------
| ORDER STATUS BADGE (keep in sync with Finals/includes/auth.php)
|--------------------------------------------------------------------------
*/

function statusBadgeClass($status) {

    $map = [

        "Pending" => "status-pending",

        "Confirmed" => "status-confirmed",

        "Assigned" => "status-dispatched",

        "Accepted" => "status-accepted",

        "Picked Up" => "status-pickedup",

        "Ready for Delivery" => "status-ready",

        "Out for Delivery" => "status-delivery",

        "Delivered" => "status-delivered",

        "Cancelled" => "status-cancelled",

    ];

    return $map[$status] ?? "status-pending";

}


/*
|--------------------------------------------------------------------------
| PAYMENT STATUS (keep in sync with Finals/includes/auth.php)
|--------------------------------------------------------------------------
*/

function paymentBadgeClass($paymentStatus) {

    $map = [
        "Unpaid" => "status-pending",
        "For Verification" => "status-dispatched",
        "Paid" => "status-delivered",
        "Rejected" => "status-cancelled",
    ];

    return $map[$paymentStatus] ?? "status-pending";

}
