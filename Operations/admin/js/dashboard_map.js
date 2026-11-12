// =====================================================
// MASTER DASHBOARD: LIVE RIDER MAP
// Shows the same rider positions as the Driver Locations page and refreshes
// every 30 seconds. Riders who are Offline or finished are not shown; a rider
// whose GPS signal dropped stays on the map as a faded "last known" marker.
// =====================================================

(function() {

    const mapElement = document.getElementById("dashboardMap");

    if (!mapElement || typeof L === "undefined") {
        return;
    }

    const statusElement = document.getElementById("dashboardMapStatus");

    const map = L.map("dashboardMap").setView([14.3122, 121.1114], 12);

    L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
        maxZoom: 19,
        attribution: "&copy; OpenStreetMap contributors"
    }).addTo(map);

    const markers = {};

    let fitted = false;


    function escapeHtml(text) {

        const div = document.createElement("div");

        div.textContent = text || "";

        return div.innerHTML;

    }


    function ageText(seconds) {

        if (seconds === null || seconds === undefined) {
            return "no update yet";
        }

        if (seconds < 60) {
            return "just now";
        }

        if (seconds < 3600) {
            return Math.round(seconds / 60) + " min ago";
        }

        return Math.round(seconds / 3600) + " hr ago";

    }


    async function loadRiders() {

        try {

            const response = await fetch("driver_locations.php?ajax=locations&t=" + Date.now());

            const data = await response.json();

            if (!data.success) {

                statusElement.textContent = "Unable to load rider locations.";

                return;
            }

            const seen = {};

            let visible = 0;

            let notShared = 0;

            data.drivers.forEach(function(driver) {

                if (driver.latitude === null || driver.longitude === null) {

                    notShared++;

                    return;
                }

                visible++;

                seen[driver.driver_id] = true;

                const position = [driver.latitude, driver.longitude];

                const popup =
                    "<strong>" + escapeHtml(driver.driver_name) + "</strong>" +
                    (driver.stale ? "<br><em>Last known location (" + escapeHtml(ageText(driver.seconds_ago)) + ")</em>" : "") +
                    driver.orders.map(function(order) {
                        return "<br>" + escapeHtml(order.order_number) + " · " + escapeHtml(order.customer_name) + " · <small>" + escapeHtml(order.status) + "</small>";
                    }).join("");

                if (!markers[driver.driver_id]) {

                    markers[driver.driver_id] = L.marker(position).addTo(map).bindPopup(popup);

                } else {

                    markers[driver.driver_id].setLatLng(position).setPopupContent(popup);

                }

                markers[driver.driver_id].setOpacity(driver.stale ? 0.55 : 1);

            });

            // Riders that went Offline or finished are removed from the map.
            Object.keys(markers).forEach(function(id) {

                if (!seen[id]) {

                    map.removeLayer(markers[id]);

                    delete markers[id];

                }

            });

            if (!fitted && visible > 0) {

                map.fitBounds(L.featureGroup(Object.values(markers)).getBounds(), { padding: [40, 40], maxZoom: 15 });

                fitted = true;

            }

            statusElement.textContent =
                visible + " rider(s) on the map" +
                (notShared > 0 ? " · " + notShared + " not sharing location" : "") +
                " · Updated " + new Date().toLocaleTimeString([], { hour: "numeric", minute: "2-digit" });

        } catch (error) {

            console.error("Rider map error:", error);

            // Markers stay where they were while the connection is down.
            statusElement.textContent = "Connection problem. Showing the last known positions.";

        }

    }


    loadRiders();

    setInterval(loadRiders, 30000);

})();
