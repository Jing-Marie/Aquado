<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aquado Purified Water Refilling Station</title>
    <meta name="description" content="Order purified drinking water online from Aquado in Santa Rosa, Laguna.">
    <!-- Loading screen only; search engines index home.php instead. -->
    <meta name="robots" content="noindex, follow">
    <meta name="theme-color" content="#1B4332">
    <link rel="icon" type="image/png" sizes="32x32" href="assets/icons/favicon-32.png">
    <link rel="apple-touch-icon" href="assets/icons/apple-touch-icon.png">
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
    <style>
        .splash-screen {
            min-height: 100vh;
            background: linear-gradient(160deg, #1B4332 0%, #2D6A4F 50%, #40916C 100%);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: #fff;
            text-align: center;
            padding: 24px;
        }
        .splash-logo {
            width: 120px;
            height: 120px;
            margin-bottom: 24px;
            animation: splashPulse 2s ease-in-out infinite;
        }
        .splash-screen h1 {
            font-size: 36px;
            letter-spacing: 4px;
            margin-bottom: 8px;
        }
        .splash-screen p {
            opacity: 0.9;
            font-size: 14px;
            margin-bottom: 32px;
        }
        .splash-loader {
            width: 40px;
            height: 40px;
            border: 3px solid rgba(255,255,255,0.3);
            border-top-color: #fff;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        @keyframes splashPulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }
        @keyframes spin { to { transform: rotate(360deg); } }
    </style>
</head>
<body>
<div class="splash-screen">
    <img src="assets/logo.svg" alt="Aquado" class="splash-logo">
    <h1>AQUADO</h1>
    <p>Purified Water Refilling Station<br>Santa Rosa, Laguna</p>
    <div class="splash-loader"></div>
</div>
<script>
setTimeout(function() {
    <?php if (isset($_SESSION["user_id"])) { ?>
        window.location.href = "home.php";
    <?php } elseif (isset($_COOKIE["aquado_onboarded"])) { ?>
        window.location.href = "login.php";
    <?php } else { ?>
        window.location.href = "onboarding.php";
    <?php } ?>
}, 2500);
</script>
</body>
</html>
