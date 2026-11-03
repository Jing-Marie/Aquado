<?php
include "../connections.php";
include "../includes/auth.php";

requireRole("driver", "../login.php");
$user = loadCurrentUser($conn);

$pageTitle = "Account";
$cssPath = "../style.css";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account - Aquado Driver</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="../style.css?v=<?php echo time(); ?>">
    <?php include __DIR__ . "/../includes/pwa_head.php"; ?>
</head>
<body>
<div class="driver-app">
    <div class="driver-header">
        <h2>DRIVER ACCOUNT</h2>
    </div>

    <div class="driver-content">
        <div class="card" style="text-align:center;">
            <div class="assignment-avatar" style="width:80px;height:80px;font-size:32px;margin:0 auto 16px;">
                <?php echo e(strtoupper(substr($user["fullname"], 0, 1))); ?>
            </div>
            <h3 style="color:var(--green-dark);"><?php echo e($user["fullname"]); ?></h3>
            <p style="color:var(--gray-500);font-size:14px;"><?php echo e($user["email"]); ?></p>
            <p style="color:var(--gray-500);font-size:14px;"><?php echo e($user["contact_number"] ?? ""); ?></p>
        </div>
        <!-- Shown on Android Chrome when the app can be installed -->
        <button type="button" class="btn btn-primary btn-block" style="display:none;margin-top:16px;" data-install-app onclick="installAquadoApp()">
            <i class="fa-solid fa-download"></i> Install Aquado Driver App
        </button>
        <a href="../logout.php" class="btn btn-secondary btn-block" style="margin-top:16px;">Logout</a>
    </div>

    <nav class="driver-bottom-nav">
        <a href="index.php"><i class="fa-solid fa-house"></i> Dashboard</a>
        <a href="routes.php"><i class="fa-solid fa-map"></i> Routes</a>
        <a href="history.php"><i class="fa-solid fa-clock-rotate-left"></i> History</a>
        <a href="account.php" class="active"><i class="fa-solid fa-user"></i> Account</a>
    </nav>
</div>
</body>
</html>
