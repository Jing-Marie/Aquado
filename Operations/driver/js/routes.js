// =====================================================
// ROUTE MAP (all of the driver's stops on one map)
// =====================================================


// =====================================================
// VARIABLES
// =====================================================

let map;

let driverMarker = null;

let routeLine = null;

let driverPosition = null;

// Shown with the route so the driver knows their location isn't being shared.
let locationError = "";

// Same order as ROUTE_STOPS; null until the address is found.
let stopPositions = ROUTE_STOPS.map(function() { return null; });


// =====================================================
// CREATE MAP (Santa Rosa, Laguna)
// =====================================================

map = L.map("map").setView(
    [14.3122, 121.1114],
    13
);


// =====================================================
// OPENSTREETMAP
// =====================================================

L.tileLayer(
    "https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png",
    {
        maxZoom: 19,

        attribution:
            "&copy; OpenStreetMap contributors"
    }
).addTo(map);


function setStatus(text) {
    document.getElementById("map-status").innerHTML =
        text + (locationError ? "<br>" + escapeHtml(locationError) : "");
}


// =====================================================
// FIND EACH CUSTOMER ADDRESS
// (one request per second, as OpenStreetMap asks)
// =====================================================

async function findStops() {

    for (let i = 0; i < ROUTE_STOPS.length; i++) {

        const stop = ROUTE_STOPS[i];

        try {

            const response = await fetch(
                "https://nominatim.openstreetmap.org/search" +
                "?format=json" +
                "&limit=1" +
                "&q=" +
                encodeURIComponent(stop.address)
            );

            const data = await response.json();

            if (data.length > 0) {

                stopPositions[i] = [
                    parseFloat(data[0].lat),
                    parseFloat(data[0].lon)
                ];

                L.marker(stopPositions[i])
                    .addTo(map)
                    .bindTooltip(String(i + 1), { permanent: true, direction: "top" })
                    .bindPopup(
                        "<strong>Stop " + (i + 1) + ": " + escapeHtml(stop.name) + "</strong><br>" +
                        escapeHtml(stop.task) + "<br>" +
                        "<small>" + escapeHtml(stop.address) + "</small>"
                    );

            } else {

                console.warn("Address not found:", stop.address);

            }

        } catch (error) {

            console.error("Destination error:", error);

        }

        if (i < ROUTE_STOPS.length - 1) {
            await new Promise(function(resolve) { setTimeout(resolve, 1100); });
        }

    }

    calculateRoute();

}


// =====================================================
// CALCULATE DRIVING ROUTE THROUGH ALL STOPS
// =====================================================

async function calculateRoute() {

    const foundStops = stopPositions.filter(function(p) { return p !== null; });

    const missing = ROUTE_STOPS.length - foundStops.length;

    const missingText = missing > 0
        ? " &nbsp; • &nbsp; " + missing + " address(es) not found on the map"
        : "";

    const waypoints = driverPosition
        ? [driverPosition].concat(foundStops)
        : foundStops;

    if (waypoints.length < 2) {

        if (foundStops.length === 1) {
            map.setView(foundStops[0], 15);
        }

        setStatus(
            (driverPosition || locationError ? "" : "Waiting for your location...") + missingText
        );

        return;
    }


    setStatus("Calculating route...");


    try {

        const routeURL =
            "https://router.project-osrm.org/route/v1/driving/" +
            waypoints.map(function(p) { return p[1] + "," + p[0]; }).join(";") +
            "?overview=full&geometries=geojson";

        const response = await fetch(routeURL);

        const data = await response.json();

        if (data.code !== "Ok") {

            setStatus("Could not calculate route." + missingText);

            return;
        }

        const route = data.routes[0];


        // -----------------------------------------
        // DRAW ROUTE
        // -----------------------------------------

        if (routeLine !== null) {
            map.removeLayer(routeLine);
        }

        routeLine = L.geoJSON(
            route.geometry,
            {
                style: {
                    weight: 6
                }
            }
        ).addTo(map);


        // -----------------------------------------
        // DISTANCE & ESTIMATED TIME
        // -----------------------------------------

        const distanceKm = (route.distance / 1000).toFixed(2);

        const durationMinutes = Math.round(route.duration / 60);

        setStatus(
            ROUTE_STOPS.length + " stop(s)" +
            " &nbsp; • &nbsp; " +
            "Distance: " + distanceKm + " km" +
            " &nbsp; • &nbsp; " +
            "Estimated time: " + durationMinutes + " min" +
            missingText
        );


        map.fitBounds(
            routeLine.getBounds(),
            {
                padding: [30, 30]
            }
        );

    } catch (error) {

        console.error("Routing error:", error);

        setStatus("Error calculating route.");

    }

}


// =====================================================
// DRIVER LOCATION (also saved for the customer's map)
// =====================================================

function onDriverPosition(position, errorMessage) {

    if (!position) {
        locationError = errorMessage.replace(/\.?$/, ".") + " Allow location access so customers can track you.";
        calculateRoute();
        return;
    }

    locationError = "";

    driverPosition = position;

    if (driverMarker === null) {

        driverMarker = L.marker(position)
            .addTo(map)
            .bindPopup("Your current location");

    } else {

        driverMarker.setLatLng(position);

    }

    calculateRoute();

}


// =====================================================
// ESCAPE HTML
// =====================================================

function escapeHtml(text) {

    const div = document.createElement("div");

    div.textContent = text || "";

    return div.innerHTML;

}


// =====================================================
// START (location updates every 30 seconds)
// =====================================================

findStops();

startSharingLocation(onDriverPosition);
