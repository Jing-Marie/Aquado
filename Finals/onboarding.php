<?php
include "connections.php";
include "includes/auth.php";

$step = (int)($_GET["step"] ?? 1);
if ($step < 1 || $step > 3) {
    $step = 1;
}

$steps = [
    1 => ["icon" => "fa-droplet", "title" => "Pure Water at Your Door", "text" => "Aquado delivers premium purified drinking water straight to your home in Santa Rosa, Laguna."],
    2 => ["icon" => "fa-cart-shopping", "title" => "Easy Online Ordering", "text" => "Browse our catalog, choose your water type, toggle container swap, and checkout in minutes."],
    3 => ["icon" => "fa-location-dot", "title" => "Real-Time Tracking", "text" => "Follow your delivery from dispatch to your doorstep. View e-receipts anytime."],
];

$current = $steps[$step];
$pageTitle = "Welcome";
$portal = "customer";

include "includes/header.php";
?>

<section class="onboarding-page">
    <div class="onboarding-card">
        <i class="fa-solid <?php echo e($current["icon"]); ?>"></i>
        <h2><?php echo e($current["title"]); ?></h2>
        <p><?php echo e($current["text"]); ?></p>

        <div class="onboarding-dots">
            <?php for ($i = 1; $i <= 3; $i++) { ?>
                <span class="<?php echo $i === $step ? "active" : ""; ?>"></span>
            <?php } ?>
        </div>

        <?php if ($step < 3) { ?>
            <a href="onboarding.php?step=<?php echo $step + 1; ?>" class="btn btn-primary btn-block btn-lg">Next</a>
        <?php } else { ?>
            <a href="home.php" class="btn btn-primary btn-block btn-lg">Get Started</a>
            <p class="auth-footer" style="margin-top:16px;">
                Already have an account? <a href="login.php">Login</a>
            </p>
        <?php } ?>

        <?php if ($step > 1) { ?>
            <a href="onboarding.php?step=<?php echo $step - 1; ?>" style="display:block;margin-top:12px;font-size:14px;color:var(--gray-500);">Back</a>
        <?php } ?>
    </div>
</section>

<script>
if (<?php echo $step; ?> === 3) {
    document.cookie = "aquado_onboarded=1; path=/; max-age=31536000";
}
</script>

<?php include "includes/footer.php"; ?>
