<?php

require_once __DIR__ . "/auth.php";
require_once __DIR__ . "/seo.php";


/*
|--------------------------------------------------------------------------
| DEFAULT PAGE SETTINGS
|--------------------------------------------------------------------------
*/

$portal = $portal ?? "customer";

$activeNav = $activeNav ?? "";

$pageTitle = $pageTitle ?? "Aquado";

$metaDescription = $metaDescription
    ?? "Order purified drinking water online from Aquado in Santa Rosa, Laguna. Fast delivery, container swap, and GCash or cash on delivery.";

// Only public pages (home, login, register) appear in search results.
$indexable = $indexable ?? false;


/*
|--------------------------------------------------------------------------
| LOAD CURRENT USER IF LOGGED IN
|--------------------------------------------------------------------------
*/

if (!isset($user) && isLoggedIn()) {

    if (isset($conn)) {
        $user = loadCurrentUser($conn);
    } else {
        $user = null;
    }

}


/*
|--------------------------------------------------------------------------
| DEFAULT USER
|--------------------------------------------------------------------------
*/

$user = $user ?? null;


/*
|--------------------------------------------------------------------------
| GET FIRST LETTER FOR PROFILE
|--------------------------------------------------------------------------
*/

$firstLetter = "U";


if ($user && !empty($user["fullname"])) {

    $firstLetter = strtoupper(
        substr(
            trim($user["fullname"]),
            0,
            1
        )
    );

}

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >


    <title>

        <?php echo e($pageTitle); ?>

        - Aquado Purified Water

    </title>


    <!-- SEARCH ENGINES & SHARING -->

    <meta name="description" content="<?php echo e($metaDescription); ?>">

    <meta name="robots" content="<?php echo $indexable ? "index, follow" : "noindex, follow"; ?>">

    <?php if ($indexable) { ?>
        <link rel="canonical" href="<?php echo e(currentPageUrl()); ?>">
    <?php } ?>

    <meta property="og:type" content="website">
    <meta property="og:site_name" content="Aquado Purified Water">
    <meta property="og:locale" content="en_PH">
    <meta property="og:title" content="<?php echo e($pageTitle); ?> - Aquado Purified Water">
    <meta property="og:description" content="<?php echo e($metaDescription); ?>">
    <meta property="og:url" content="<?php echo e(currentPageUrl()); ?>">
    <meta property="og:image" content="<?php echo e(siblingUrl("assets/icons/og-image.jpg")); ?>">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta name="twitter:card" content="summary_large_image">

    <meta name="theme-color" content="#1B4332">
    <link rel="icon" type="image/png" sizes="32x32" href="assets/icons/favicon-32.png">
    <link rel="icon" type="image/png" sizes="192x192" href="assets/icons/icon-192.png">
    <link rel="apple-touch-icon" href="assets/icons/apple-touch-icon.png">

    <?php if (!empty($structuredData)) { ?>
        <script type="application/ld+json"><?php echo localBusinessJsonLd(); ?></script>
    <?php } ?>


    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"
    >


    <link
        rel="stylesheet"
        href="<?php echo e($cssPath ?? "style.css"); ?>?v=<?php echo time(); ?>"
    >


</head>


<body>


<div class="page">


<?php if ($portal === "customer") { ?>


<header class="aquado-header">


    <a href="home.php" class="logo-area">


        <img
            src="assets/logo.svg"
            class="logo-img"
            alt="Aquado Logo"
        >


        <div class="logo-text">

            <h1>AQUADO</h1>

            <span>
                Purified Drinking Water · Santa Rosa, Laguna
            </span>

        </div>


    </a>



    <nav class="main-nav">


        <?php if (isLoggedIn()) { ?>


            <a
                href="catalog.php"
                class="nav-link <?php echo $activeNav === "catalog" ? "active" : ""; ?>"
            >
                Product Catalog
            </a>


            <a
                href="checkout.php"
                class="nav-link <?php echo $activeNav === "checkout" ? "active" : ""; ?>"
            >
                Order Summary
            </a>


            <a
                href="order_history.php"
                class="nav-link <?php echo $activeNav === "orders" ? "active" : ""; ?>"
            >
                Orders & Receipts
            </a>


        <?php } else { ?>


            <a
                href="login.php"
                class="nav-link <?php echo $activeNav === "auth" ? "active" : ""; ?>"
            >
                Login
            </a>


            <a
                href="register.php"
                class="nav-link"
            >
                Register
            </a>


        <?php } ?>


    </nav>



    <div class="header-actions">


        <?php if (isLoggedIn()) { ?>


            <a
                href="cart.php"
                class="nav-link"
                title="Cart"
            >

                <i class="fa-solid fa-cart-shopping"></i>

            </a>



            <div
                class="profile-box"
                id="profileDropdown"
            >


                <button
                    type="button"
                    class="profile-circle"
                    id="profileToggle"
                >

                    <?php echo e($firstLetter); ?>

                </button>



                <div class="profile-dropdown">


                    <a href="profile.php">

                        <?php
                        echo e(
                            $user["fullname"]
                            ?? $_SESSION["fullname"]
                            ?? "Profile"
                        );
                        ?>

                    </a>



                    <a href="logout.php">

                        Logout

                    </a>


                </div>


            </div>


        <?php } else { ?>


            <a
                href="login.php"
                class="btn btn-outline"
            >
                Login
            </a>


            <a
                href="register.php"
                class="btn btn-primary"
            >
                Register
            </a>


        <?php } ?>


    </div>


</header>



<?php if (!empty($breadcrumb)) { ?>


<div class="hierarchy-bar">


    <span class="hierarchy-label">

        Customer App

    </span>


    <span class="hierarchy-sep">

        ›

    </span>


    <span>

        <?php echo e($breadcrumb); ?>

    </span>


</div>


<?php } ?>


<?php } ?>