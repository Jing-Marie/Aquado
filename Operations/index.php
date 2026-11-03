<?php
require_once "includes/auth.php";

if (isLoggedIn()) {
    redirectByRole();
}

header("Location: login.php");
exit();
