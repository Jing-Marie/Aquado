// =====================================================
// DRIVER LOCATIONS
// =====================================================

let driverMap;

let driverMarkers = {};

let driverData = {};


// =====================================================
// CREATE MAP
// =====================================================

driverMap = L.map("driverMap").setView(
    [14.3122, 121.1114],
    12
);


L.tileLayer(
    "https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png",
    {
        maxZoom: 19,

        attribution:
            "&copy; OpenStreetMap contributors"
    }
).addTo(driverMap);


// =====================================================
// LOAD DRIVER LOCATIONS
// =====================================================

async function loadDriverLocations() {

    try {

        const response = await fetch(
            "driver_locations.php?ajax=locations&t=" +
            Date.now()
        );


        const data = await response.json();


        if (!data.success) {

            document.getElementById(
                "mapStatus"
            ).innerText =
                "Unable to load locations.";

            return;
        }


        driverData = {};


        data.drivers.forEach(function(driver) {

            driverData[driver.driver_id] =
                driver;

        });


        updateMap(data.drivers);

        updateDriverList(data.drivers);


        document.getElementById(
            "mapStatus"
        ).innerText =
            "Updated " +
            new Date().toLocaleTimeString();


    } catch (error) {

        console.error(
            "Location loading error:",
            error
        );


        document.getElementById(
            "mapStatus"
        ).innerText =
            "Connection error.";

    }

}


// =====================================================
// UPDATE MAP
// =====================================================

function updateMap(drivers) {

    const activeMarkerIds = {};


    drivers.forEach(function(driver) {


        // No GPS location yet

        if (
            driver.latitude === null ||
            driver.longitude === null
        ) {

            return;

        }


        const markerId =
            driver.driver_id;


        activeMarkerIds[markerId] = true;


        const position = [

            driver.latitude,

            driver.longitude

        ];


        const popupContent = `

            <strong>
                ${escapeHtml(driver.driver_name)}
            </strong>

            ${driver.stale ? "<br><em>Last known location (" + escapeHtml(ageText(driver.seconds_ago)) + ")</em>" : ""}

            ${driver.orders.map(function(order) {
                return `
                    <br>
                    ${escapeHtml(order.order_number)}
                    · ${escapeHtml(order.customer_name)}
                    · <small>${escapeHtml(order.status)}</small>
                `;
            }).join("")}

        `;


        // CREATE MARKER

        if (!driverMarkers[markerId]) {


            driverMarkers[markerId] =

                L.marker(position)

                    .addTo(driverMap)

                    .bindPopup(
                        popupContent
                    );


            // CLICK DRIVER MARKER
            // ZOOM TO DRIVER

            driverMarkers[markerId].on(
                "click",
                function() {

                    driverMap.setView(
                        position,
                        18,
                        {
                            animate: true
                        }
                    );


                    driverMarkers[
                        markerId
                    ].openPopup();

                }
            );


        } else {


            // UPDATE MARKER

            driverMarkers[markerId]

                .setLatLng(position)

                .setPopupContent(
                    popupContent
                );

        }


        // GPS signal lost: keep the marker on the last known spot, shown faded.

        driverMarkers[markerId].setOpacity(
            driver.stale ? 0.55 : 1
        );

    });


    // =================================================
    // REMOVE OLD MARKERS
    // =================================================

    Object.keys(
        driverMarkers
    ).forEach(function(id) {


        if (
            !activeMarkerIds[id]
        ) {

            driverMap.removeLayer(
                driverMarkers[id]
            );


            delete driverMarkers[id];

        }

    });


    // =================================================
    // SHOW ALL ACTIVE DRIVERS
    // =================================================

    const markerList =
        Object.values(driverMarkers);


    if (
        markerList.length > 0
    ) {

        const group =
            L.featureGroup(
                markerList
            );


        driverMap.fitBounds(
            group.getBounds(),
            {
                padding: [
                    40,
                    40
                ],

                maxZoom: 15
            }
        );

    }

}


// =====================================================
// DRIVER LIST
// =====================================================

function updateDriverList(drivers) {

    const list =
        document.getElementById(
            "driverList"
        );


    if (
        drivers.length === 0
    ) {

        list.innerHTML = `

            <div class="empty-drivers">

                <i class="fa-solid fa-truck"></i>

                <p>
                    No drivers have active
                    orders right now.
                </p>

            </div>

        `;

        return;

    }


    list.innerHTML = "";


    drivers.forEach(function(driver) {


        const card =
            document.createElement(
                "div"
            );


        card.className =
            "driver-card";


        let locationStatus = "";

        let updateText = "";


        // LOCATION AVAILABLE

        if (
            driver.latitude !== null &&
            driver.longitude !== null
        ) {


            if (driver.stale) {

                locationStatus = `

                    <div class="driver-status status-waiting">

                        <i class="fa-solid fa-triangle-exclamation"></i>

                        GPS signal lost. Showing last known location.

                    </div>

                `;

            } else {

                locationStatus = `

                    <div class="driver-status status-online">

                        <i class="fa-solid fa-circle"></i>

                        Location available

                    </div>

                `;

            }


            if (
                driver.last_updated
            ) {

                updateText = `

                    <div class="last-update">

                        Last update:
                        ${escapeHtml(
                            driver.last_updated
                        )}
                        (${escapeHtml(ageText(driver.seconds_ago))})

                    </div>

                `;

            }


        } else if (!driver.online || !driver.sharing) {


            // OFFLINE / SHARING STOPPED

            locationStatus = `

                <div class="driver-status status-waiting">

                    <i class="fa-solid fa-circle-pause"></i>

                    ${driver.online
                        ? "Not sharing location"
                        : "Rider is offline. Location sharing stopped."}

                </div>

            `;


        } else {


            // WAITING

            locationStatus = `

                <div class="driver-status status-waiting">

                    <i class="fa-solid fa-clock"></i>

                    Waiting for driver location

                </div>

            `;

        }


        card.innerHTML = `

            <div class="driver-name">

                <i class="fa-solid fa-truck"></i>

                ${escapeHtml(
                    driver.driver_name
                )}

            </div>


            <div class="driver-order">

                ${driver.orders.length}
                active order(s)

            </div>


            ${driver.orders.map(function(order) {
                return `

                    <div class="driver-customer">

                        <strong>
                            ${escapeHtml(order.order_number)}
                        </strong>

                        · ${escapeHtml(order.customer_name)}
                        · ${escapeHtml(order.status)}

                    </div>


                    <div class="driver-address">

                        ${escapeHtml(order.customer_address)}

                    </div>

                `;
            }).join("")}


            ${locationStatus}

            ${updateText}

        `;


        // CLICK DRIVER CARD

        card.addEventListener(
            "click",
            function() {


                if (
                    driver.latitude !== null &&
                    driver.longitude !== null
                ) {


                    driverMap.setView(
                        [
                            driver.latitude,
                            driver.longitude
                        ],

                        18,

                        {
                            animate: true
                        }
                    );


                    if (
                        driverMarkers[
                            driver.driver_id
                        ]
                    ) {

                        driverMarkers[
                            driver.driver_id
                        ].openPopup();

                    }

                }

            }
        );


        list.appendChild(
            card
        );

    });

}


// =====================================================
// "2 min ago" text for the last GPS update
// =====================================================

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


// =====================================================
// ESCAPE HTML
// =====================================================

function escapeHtml(text) {

    if (!text) {
        return "";
    }


    const div =
        document.createElement(
            "div"
        );


    div.textContent =
        text;


    return div.innerHTML;

}


// =====================================================
// START
// =====================================================

loadDriverLocations();


// =====================================================
// UPDATE EVERY 30 SECONDS
// =====================================================

setInterval(
    loadDriverLocations,
    30000
);