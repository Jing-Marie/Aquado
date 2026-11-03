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
| LOGIN PROCESS (admin, cashier and driver accounts only)
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

        $stmt->bind_param("s", $email);

        $stmt->execute();

        $user = $stmt->get_result()->fetch_assoc();

        $stmt->close();


        if (!$user || !password_verify($password, $user["password"])) {

            $error = "Invalid email or password.";

        } elseif (!in_array(strtolower($user["role"]), STAFF_ROLES, true)) {

            $error = "This app is for Aquado staff and drivers only. Customers can log in on the customer website.";

        } else {

            session_regenerate_id(true);

            $_SESSION["user_id"] = $user["id"];

            $_SESSION["fullname"] = $user["fullname"];

            $_SESSION["email"] = $user["email"];

            $_SESSION["role"] = strtolower($user["role"]);

            redirectByRole();

        }

    }

}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Login - Aquado Operations</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
    <?php $pwaRoot = ""; include __DIR__ . "/includes/pwa_head.php"; ?>
</head>
<body>
<div class="page">

<section class="auth-page">

    <div class="auth-card">

        <img src="assets/logo.svg" alt="Aquado" style="width:56px;margin-bottom:12px;">

        <h2>Aquado Operations</h2>

        <p>
            Staff login for Admin, Cashier and Drivers.
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

        <!-- Shown on Android Chrome when the app can be installed -->
        <button type="button" class="btn btn-secondary btn-block" style="display:none;margin-top:12px;" data-install-app onclick="installAquadoApp()">
            <i class="fa-solid fa-download"></i> Install Aquado Driver App
        </button>

    </div>

</section>

</div>
</body>
</html>
