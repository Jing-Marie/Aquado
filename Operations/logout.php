<?php

require_once "includes/auth.php";


// A rider who logs out stops sharing their location.
if (isLoggedIn() && currentRole() === "driver") {

    try {

        require_once "connections.php";

        $stmt = $conn->prepare("UPDATE driver_locations SET is_sharing = 0 WHERE driver_id = ?");

        $driverId = currentUserId();

        $stmt->bind_param("i", $driverId);

        $stmt->execute();

        $stmt->close();

    } catch (Throwable $e) {

        error_log("Could not stop location sharing on logout: " . $e->getMessage());

    }

}


logoutUser();


header("Location: login.php");

exit();
