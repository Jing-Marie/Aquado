<?php

require_once "includes/auth.php";


logoutUser();


header("Location: home.php");

exit();

?>