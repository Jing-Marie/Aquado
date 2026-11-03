<?php

require_once "connections.php";
require_once "includes/auth.php";


/*
|--------------------------------------------------------------------------
| LOAD CURRENT USER
|--------------------------------------------------------------------------
*/

$user = loadCurrentUser($conn);


/*
|--------------------------------------------------------------------------
| PAGE SETTINGS
|--------------------------------------------------------------------------
*/

$pageTitle = "Purified Water Delivery in Santa Rosa, Laguna";

$metaDescription = "Aquado Purified Water Refilling Station in Barangay Tagapo, Santa Rosa, Laguna. Order 5-gallon slim and round refills, containers and PET bottles online with fast home delivery, container swap, GCash or cash on delivery.";

$indexable = true;

$structuredData = true;

$activeNav = "home";

$portal = "customer";


include "includes/header.php";

?>


<section class="hero-section">

    <h2>Pure Water, Delivered Fresh</h2>

    <p>
        Order purified drinking water online from Aquado.
        Fast delivery, container swap, and easy GCash or COD payment.
    </p>


    <div class="hero-actions">

        <?php if (isLoggedIn()) { ?>

            <a href="catalog.php" class="btn btn-primary btn-lg">
                Product Catalog
            </a>

            <a href="order_history.php" class="btn btn-outline btn-lg">
                My Orders
            </a>

        <?php } else { ?>

            <a href="login.php" class="btn btn-primary btn-lg">
                Login to Order
            </a>

            <a href="register.php" class="btn btn-outline btn-lg">
                Create Account
            </a>

        <?php } ?>

    </div>

</section>


<section class="features-section">

    <h2 class="section-title">
        Why Choose Aquado?
    </h2>


    <div class="features-grid">


        <div class="feature-card">

            <i class="fa-solid fa-droplet"></i>

            <h3>Purified Quality</h3>

            <p>
                Multi-stage filtration ensures every drop meets
                the highest safety standards.
            </p>

        </div>


        <div class="feature-card">

            <i class="fa-solid fa-truck-fast"></i>

            <h3>Fast Delivery</h3>

            <p>
                Track your order as our delivery team brings
                fresh water to your doorstep.
            </p>

        </div>


        <div class="feature-card">

            <i class="fa-solid fa-arrows-rotate"></i>

            <h3>Container Swap</h3>

            <p>
                Exchange your empty containers for filled ones
                with no hassle and no waiting.
            </p>

        </div>


        <div class="feature-card">

            <i class="fa-solid fa-mobile-screen"></i>

            <h3>Easy Payment</h3>

            <p>
                Pay via GCash or Cash on Delivery.
                Secure and convenient.
            </p>

        </div>


    </div>

</section>


<?php include "includes/footer.php"; ?>