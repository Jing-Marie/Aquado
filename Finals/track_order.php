<?php
header("Location: order_history.php?tab=tracking" . (isset($_GET["order"]) ? "&order=" . urlencode($_GET["order"]) : ""));
exit();
