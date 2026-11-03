<?php
/*
 * Installable app (PWA) tags for every Operations page.
 * Set $pwaRoot to the path of the Operations folder ("" or "../") before including.
 * Any element with data-install-app is shown when Android Chrome allows installing.
 */
$pwaRoot = $pwaRoot ?? "../";
?>
    <meta name="robots" content="noindex, nofollow">
    <meta name="theme-color" content="#1B4332">
    <link rel="manifest" href="<?php echo $pwaRoot; ?>manifest.json">
    <link rel="icon" type="image/png" sizes="32x32" href="<?php echo $pwaRoot; ?>assets/icons/favicon-32.png">
    <link rel="apple-touch-icon" href="<?php echo $pwaRoot; ?>assets/icons/apple-touch-icon.png">
    <script>
    if ("serviceWorker" in navigator) {
        window.addEventListener("load", function() {
            navigator.serviceWorker.register(<?php echo json_encode($pwaRoot . "sw.js"); ?>, { scope: <?php echo json_encode($pwaRoot === "" ? "./" : $pwaRoot); ?> });
        });
    }

    // "Install App" buttons
    let aquadoInstallPrompt = null;

    window.addEventListener("beforeinstallprompt", function(event) {
        event.preventDefault();
        aquadoInstallPrompt = event;
        document.querySelectorAll("[data-install-app]").forEach(function(el) { el.style.display = ""; });
    });

    window.addEventListener("appinstalled", function() {
        aquadoInstallPrompt = null;
        document.querySelectorAll("[data-install-app]").forEach(function(el) { el.style.display = "none"; });
    });

    function installAquadoApp() {
        if (!aquadoInstallPrompt) return;
        aquadoInstallPrompt.prompt();
        aquadoInstallPrompt.userChoice.finally(function() { aquadoInstallPrompt = null; });
    }
    </script>
