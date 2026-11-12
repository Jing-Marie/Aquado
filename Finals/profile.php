<?php
include "connections.php";
include "includes/auth.php";

requireLogin();
$user = loadCurrentUser($conn);
$message = "";
$messageIsError = false;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $fullname = trim($_POST["fullname"] ?? "");
    $contact = trim($_POST["contact_number"] ?? "");
    $address = trim($_POST["address"] ?? "");

    // Contact number and address stay required, as they are when registering.
    if ($fullname === "" || $contact === "" || $address === "") {
        $message = "Full name, contact number and delivery address are required.";
        $messageIsError = true;
    } else {
        $stmt = $conn->prepare("UPDATE users SET fullname = ?, contact_number = ?, address = ? WHERE id = ?");
        $stmt->bind_param("sssi", $fullname, $contact, $address, $user["id"]);
        if ($stmt->execute()) {
            $message = "Profile updated successfully.";
            $user = loadCurrentUser($conn);
        }
        $stmt->close();
    }
}

$pageTitle = "Profile";
$portal = "customer";
include "includes/header.php";
?>

<section class="content-page">
    <h2>My Profile</h2>

    <?php if ($message !== "") { ?>
        <div class="<?php echo $messageIsError ? "error-message" : "success-message"; ?>"><?php echo e($message); ?></div>
    <?php } ?>

    <div class="card">
        <form method="POST">
            <div class="form-group">
                <label>Full Name</label>
                <input type="text" name="fullname" value="<?php echo e($user["fullname"]); ?>" required>
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" value="<?php echo e($user["email"]); ?>" disabled>
            </div>
            <div class="form-group">
                <label>Contact Number <span class="req">*</span></label>
                <input type="text" name="contact_number" value="<?php echo e($user["contact_number"] ?? ""); ?>" required>
            </div>
            <div class="form-group">
                <label>Delivery Address <span class="req">*</span></label>
                <textarea name="address" rows="2" required><?php echo e($user["address"] ?? ""); ?></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Save Changes</button>
        </form>
    </div>
</section>

<?php include "includes/footer.php"; ?>
