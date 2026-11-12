<?php
include "../connections.php";
include "../includes/auth.php";
include "../includes/workflow.php";

requireRole("driver", "../login.php");

// The rider switches between Online and Offline. Offline stops sharing the GPS location.
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    setDriverOnline($conn, currentUserId(), ($_POST["online"] ?? "") === "1");
}

$back = ($_POST["return"] ?? "") === "account" ? "account.php" : "index.php";

header("Location: " . $back);
exit();
