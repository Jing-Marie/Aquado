<?php

require_once "connections.php";
require_once "includes/auth.php";


$error = "";


/*
|--------------------------------------------------------------------------
| IF ALREADY LOGGED IN
|--------------------------------------------------------------------------
*/

if (isLoggedIn()) {
    redirectByRole();
}


/*
|--------------------------------------------------------------------------
| LOGIN PROCESS
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim($_POST["email"] ?? "");

    $password = $_POST["password"] ?? "";


    if ($email === "" || $password === "") {

        $error = "Enter your email and password.";

    } else {

        $stmt = $conn->prepare("
            SELECT
                id,
                fullname,
                email,
                password,
                role
            FROM users
            WHERE email = ?
            LIMIT 1
        ");


        if ($stmt) {

            $stmt->bind_param("s", $email);

            $stmt->execute();

            $result = $stmt->get_result();


            if ($result->num_rows === 1) {

                $user = $result->fetch_assoc();


                /*
                 * VERIFY PASSWORD
                 */

                if (
                    password_verify($password, $user["password"])
                    && strtolower($user["role"]) !== "customer"
                ) {

                    /*
                     * STAFF ACCOUNTS USE THE OPERATIONS APP
                     */

                    $error = "Admin, cashier and driver accounts log in at the Aquado Operations app.";

                } elseif (password_verify($password, $user["password"])) {


                    /*
                     * CREATE NEW SESSION ID
                     */

                    session_regenerate_id(true);


                    /*
                     * SAVE USER SESSION
                     */

                    $_SESSION["user_id"] = $user["id"];

                    $_SESSION["fullname"] = $user["fullname"];

                    $_SESSION["email"] = $user["email"];

                    $_SESSION["role"] = strtolower($user["role"]);


                    /*
                     * REDIRECT BASED ON ROLE
                     */

                    redirectByRole();


                } else {

                    $error = "Invalid email or password.";

                }


            } else {

                $error = "Invalid email or password.";

            }


            $stmt->close();

        } else {

            $error = "System error. Please try again.";

        }

    }

}


$pageTitle = "Login";

$metaDescription = "Log in to your Aquado account to order purified water, track your delivery and view your e-receipts.";

$indexable = true;

$activeNav = "auth";

$breadcrumb = "Authentication";

$portal = "customer";


include "includes/header.php";

?>


<section class="auth-page">

    <div class="auth-card">

        <h2>Welcome Back</h2>

        <p>
            Login to your Aquado account.
        </p>


        <?php if ($error !== "") { ?>

            <div class="error-message">

                <?php echo e($error); ?>

            </div>

        <?php } ?>


        <form method="POST" action="login.php">


            <div class="form-group">

                <label>Email Address</label>

                <input
                    type="email"
                    name="email"
                    placeholder="Enter your email"
                    value="<?php echo e($_POST["email"] ?? ""); ?>"
                    required
                >

            </div>


            <div class="form-group">

                <label>Password</label>

                <input
                    type="password"
                    name="password"
                    placeholder="Enter your password"
                    required
                >

            </div>


            <button
                type="submit"
                class="btn btn-primary btn-block btn-lg"
            >
                Login
            </button>


        </form>


        <div class="auth-footer">

            Don't have an account?

            <a href="register.php">
                Register here
            </a>

        </div>


    </div>

</section>


<?php include "includes/footer.php"; ?>