<?php

require_once "connections.php";
require_once "includes/auth.php";


$error = "";


/*
|--------------------------------------------------------------------------
| IF USER IS ALREADY LOGGED IN
|--------------------------------------------------------------------------
*/

if (isLoggedIn()) {
    redirectByRole();
}


/*
|--------------------------------------------------------------------------
| REGISTER ACCOUNT
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] === "POST") {


    $fullname = trim($_POST["fullname"] ?? "");

    $contact = trim($_POST["contact_number"] ?? "");

    $email = trim($_POST["email"] ?? "");

    $address = trim($_POST["address"] ?? "");

    $password = $_POST["password"] ?? "";

    $confirm = $_POST["confirm_password"] ?? "";


    /*
     * VALIDATION
     */

    if (
        $fullname === "" ||
        $email === "" ||
        $password === ""
    ) {

        $error = "Please fill in all required fields.";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $error = "Please enter a valid email address.";

    } elseif (strlen($password) < 6) {

        $error = "Password must be at least 6 characters.";

    } elseif ($password !== $confirm) {

        $error = "Passwords do not match.";

    } else {


        /*
         * CHECK IF EMAIL EXISTS
         */

        $check = $conn->prepare("
            SELECT id
            FROM users
            WHERE email = ?
            LIMIT 1
        ");


        if ($check) {

            $check->bind_param("s", $email);

            $check->execute();

            $checkResult = $check->get_result();


            if ($checkResult->num_rows > 0) {

                $error = "Email is already registered.";

            } else {


                /*
                 * HASH PASSWORD
                 */

                $hash = password_hash(
                    $password,
                    PASSWORD_DEFAULT
                );


                /*
                 * INSERT CUSTOMER
                 */

                $stmt = $conn->prepare("
                    INSERT INTO users
                    (
                        fullname,
                        email,
                        password,
                        role,
                        contact_number,
                        address
                    )
                    VALUES
                    (
                        ?,
                        ?,
                        ?,
                        'customer',
                        ?,
                        ?
                    )
                ");


                if ($stmt) {

                    $stmt->bind_param(
                        "sssss",
                        $fullname,
                        $email,
                        $hash,
                        $contact,
                        $address
                    );


                    if ($stmt->execute()) {


                        /*
                         * SUCCESS
                         */

                        $stmt->close();

                        $check->close();


                        header("Location: login.php?registered=1");

                        exit();


                    } else {

                        $error =
                            "Registration failed. Please try again.";

                    }


                    $stmt->close();

                } else {

                    $error =
                        "System error. Please try again.";

                }

            }


            $check->close();

        } else {

            $error =
                "System error. Please try again.";

        }

    }

}


$pageTitle = "Create an Account";

$metaDescription = "Create a free Aquado account to order purified drinking water delivered to your home in Santa Rosa, Laguna.";

$indexable = true;

$activeNav = "auth";

$breadcrumb = "Authentication";

$portal = "customer";


include "includes/header.php";

?>


<section class="auth-page">

    <div class="auth-card">


        <h2>Create Account</h2>

        <p>
            Register to order purified water from Aquado.
        </p>


        <?php if ($error !== "") { ?>

            <div class="error-message">

                <?php echo e($error); ?>

            </div>

        <?php } ?>


        <form method="POST" action="register.php">


            <div class="form-group">

                <label>Full Name *</label>

                <input
                    type="text"
                    name="fullname"
                    placeholder="Your full name"
                    value="<?php echo e($_POST["fullname"] ?? ""); ?>"
                    required
                >

            </div>


            <div class="form-group">

                <label>Contact Number</label>

                <input
                    type="text"
                    name="contact_number"
                    placeholder="09XX XXX XXXX"
                    value="<?php echo e($_POST["contact_number"] ?? ""); ?>"
                >

            </div>


            <div class="form-group">

                <label>Email Address *</label>

                <input
                    type="email"
                    name="email"
                    placeholder="your@email.com"
                    value="<?php echo e($_POST["email"] ?? ""); ?>"
                    required
                >

            </div>


            <div class="form-group">

                <label>Delivery Address</label>

                <textarea
                    name="address"
                    rows="2"
                    placeholder="Your delivery address"
                ><?php echo e($_POST["address"] ?? ""); ?></textarea>

            </div>


            <div class="form-group">

                <label>Password *</label>

                <input
                    type="password"
                    name="password"
                    placeholder="Create password"
                    required
                >

            </div>


            <div class="form-group">

                <label>Confirm Password *</label>

                <input
                    type="password"
                    name="confirm_password"
                    placeholder="Confirm password"
                    required
                >

            </div>


            <button
                type="submit"
                class="btn btn-primary btn-block btn-lg"
            >
                Register
            </button>


        </form>


        <div class="auth-footer">

            Already have an account?

            <a href="login.php">
                Login here
            </a>

        </div>


    </div>

</section>


<?php include "includes/footer.php"; ?>