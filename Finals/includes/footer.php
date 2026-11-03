<?php if (($portal ?? "customer") === "customer") { ?>
<footer class="site-footer">
    <div class="footer-container">
        <div class="footer-column">
            <h3>About Aquado</h3>
            <p>Purified Water Refilling Station serving Santa Rosa, Laguna and nearby areas with clean, safe drinking water.</p>
        </div>
        <div class="footer-column">
            <h3>Connect</h3>
            <div class="footer-connect"><i class="fa-brands fa-facebook"></i> Aquado Water Station</div>
            <div class="footer-connect"><i class="fa-regular fa-envelope"></i> TagapoAquado@gmail.com</div>
            <div class="footer-connect"><i class="fa-solid fa-phone"></i> 09228112567</div>
            <div class="footer-connect"><i class="fa-solid fa-location-dot"></i> 143 Rizal Boulevard, Barangay Tagapo, Santa Rosa, Laguna</div>
        </div>
        <div class="footer-column">
            <h3>Hours</h3>
            <p>Mon – Sat: 7:00 AM – 8:00 PM<br>Sunday: 8:00 AM – 5:00 PM</p>
        </div>
    </div>
    <div class="footer-bottom">
        <p>&copy; <?php echo date("Y"); ?> Aquado Purified Water Refilling Station. All rights reserved.</p>
    </div>
</footer>
<?php } ?>
</div>

<script>
(function() {
    const profileToggle = document.getElementById("profileToggle");
    const profileDropdown = document.getElementById("profileDropdown");
    if (profileToggle && profileDropdown) {
        profileToggle.addEventListener("click", function(e) {
            e.stopPropagation();
            profileDropdown.classList.toggle("show");
        });
        document.addEventListener("click", function() {
            profileDropdown.classList.remove("show");
        });
    }
})();
</script>
</body>
</html>
