// =====================================================
// LIVE DRIVER MAP (customer tracking page)
// =====================================================

let trackingMap;

let driverMarker = null;

let customerMarker = null;

let routeLine = null;

let customerLatitude = null;

let customerLongitude = null;


function setMapStatus(text) {
    document.getElementById("customerMapStatus").innerText = text;
}


// =====================================================
// CREATE MAP (Santa Rosa, Laguna)
// =====================================================

trackingMap = L.map(
    "customerTrackingMap"
).setView(
    [14.3122, 121.1114],
    13
);


L.tileLayer(
    "https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png",
    {
        maxZoom: 19,

        attribution:
            "&copy; OpenStreetMap contributors"
    }
).addTo(trackingMap);


// =====================================================
// FIND CUSTOMER ADDRESS
// =====================================================

async function findCustomerAddress() {

    try {

        const response = await fetch(
            "https://nominatim.openstreetmap.org/search" +
            "?format=json" +
            "&limit=1" +
            "&q=" +
            encodeURIComponent(CUSTOMER_ADDRESS)
        );

        const data = await response.json();

        if (data.length > 0) {

            customerLatitude = parseFloat(data[0].lat);

            customerLongitude = parseFloat(data[0].lon);

            customerMarker = L.marker([
                customerLatitude,
                customerLongitude
            ])
            .addTo(trackingMap)
            .bindPopup(
                "<strong>Delivery Address</strong><br>" +
                escapeHtml(CUSTOMER_ADDRESS)
            );

            trackingMap.setView([customerLatitude, customerLongitude], 15);

        }

    } catch (error) {

        console.error("Address error:", error);

    }

    loadDriverLocation();

}


// =====================================================
// GET DRIVER LOCATION
// =====================================================

async function loadDriverLocation() {

    try {

        const response = await fetch(
            "get_driver_location.php?order=" +
            encodeURIComponent(TRACK_ORDER_NUMBER) +
            "&t=" +
            Date.now()
        );

        const data = await response.json();

        if (!data.success) {

            setMapStatus("Unable to load driver location.");

            return;
        }

        if (!data.tracking) {

            setMapStatus("Your order is now " + data.status + ". Refresh the page for the latest status.");

            return;
        }

        // The rider went Offline or finished: sharing has stopped, so the marker goes away.
        if (data.sharing === false) {

            removeDriverMarker();

            setMapStatus("The driver's location is not being shared right now (the rider is offline).");

            return;
        }

        if (!data.location) {

            setMapStatus("Waiting for the driver's location...");

            return;
        }


        const lat = parseFloat(data.location.latitude);

        const lng = parseFloat(data.location.longitude);

        const stale = data.location.stale === true;

        // GPS signal lost: the marker stays where the driver was last seen.
        const staleNote = stale
            ? " • GPS signal lost, showing the last known location"
            : "";

        if (driverMarker === null) {

            driverMarker = L.marker([lat, lng])
                .addTo(trackingMap)
                .bindPopup(
                    "<strong>Driver</strong><br>" +
                    (stale ? "Last known location" : "Current Location")
                );

        } else {

            driverMarker.setLatLng([lat, lng]);

            driverMarker.setPopupContent(
                "<strong>Driver</strong><br>" +
                (stale ? "Last known location" : "Current Location")
            );

        }

        driverMarker.setOpacity(stale ? 0.55 : 1);


        if (customerLatitude === null) {

            trackingMap.setView([lat, lng], 15);

            setMapStatus(
                "Driver location updated " +
                formatTime(data.location.last_updated) +
                staleNote +
                " (your address could not be found on the map)"
            );

            return;
        }

        calculateRoute(lng, lat, customerLongitude, customerLatitude, data.location.last_updated, staleNote);

    } catch (error) {

        console.error("Driver location error:", error);

        // Keep the last marker on the map when the connection drops for a moment.
        setMapStatus(
            driverMarker !== null
                ? "Connection problem. Showing the driver's last known location."
                : "Unable to load driver location."
        );

    }

}


function removeDriverMarker() {

    if (driverMarker !== null) {

        trackingMap.removeLayer(driverMarker);

        driverMarker = null;

    }

    if (routeLine !== null) {

        trackingMap.removeLayer(routeLine);

        routeLine = null;

    }

}


// =====================================================
// CALCULATE ROUTE
// =====================================================

async function calculateRoute(driverLng, driverLat, customerLng, customerLat, lastUpdated, staleNote) {

    staleNote = staleNote || "";

    try {

        const response = await fetch(
            "https://router.project-osrm.org/route/v1/driving/" +
            driverLng + "," + driverLat +
            ";" +
            customerLng + "," + customerLat +
            "?overview=full&geometries=geojson"
        );

        const data = await response.json();

        if (data.code !== "Ok") {

            setMapStatus("Driver location found, but route could not be calculated." + staleNote);

            return;
        }

        const route = data.routes[0];

        if (routeLine !== null) {
            trackingMap.removeLayer(routeLine);
        }

        routeLine = L.geoJSON(
            route.geometry,
            {
                style: {
                    weight: 6
                }
            }
        ).addTo(trackingMap);

        trackingMap.fitBounds(
            routeLine.getBounds(),
            {
                padding: [40, 40]
            }
        );

        const distanceKm = (route.distance / 1000).toFixed(2);

        const durationMinutes = Math.round(route.duration / 60);

        setMapStatus(
            "Driver is approximately " + distanceKm + " km away" +
            " • Estimated " + durationMinutes + " min" +
            " • Updated " + formatTime(lastUpdated) +
            staleNote
        );

    } catch (error) {

        console.error("Routing error:", error);

    }

}


// =====================================================
// HELPERS
// =====================================================

function formatTime(timestamp) {

    const date = new Date(String(timestamp).replace(" ", "T"));

    return isNaN(date) ? timestamp : date.toLocaleTimeString([], { hour: "numeric", minute: "2-digit" });

}


function escapeHtml(text) {

    const div = document.createElement("div");

    div.textContent = text || "";

    return div.innerHTML;

}


// =====================================================
// START (driver location refreshes every 30 seconds)
// =====================================================

findCustomerAddress();

setInterval(loadDriverLocation, 30000);
