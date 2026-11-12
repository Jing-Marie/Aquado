// =====================================================
// SHARE DRIVER LOCATION
// Sends the driver's GPS position every 30 seconds while the rider is
// Online and on an active delivery, so the customer and the station can
// follow it. The server tells us to stop (response.stop) when the rider goes
// Offline or has no active delivery left; sharing then stops for good.
//
// If the GPS signal or the connection drops for a moment, nothing is
// overwritten: the customer and admin keep seeing the last saved position
// (marked as "last known") until a new one arrives.
//
// onPosition(position, errorMessage, stopped) is optional.
// =====================================================

let locationTimer = null;

let locationStopped = false;


function stopSharingLocation(onPosition, message) {

    locationStopped = true;

    if (locationTimer !== null) {

        clearInterval(locationTimer);

        locationTimer = null;

    }

    if (onPosition) {
        onPosition(null, message || "Location sharing is off.", true);
    }

}


function startSharingLocation(onPosition) {

    if (!navigator.geolocation) {

        if (onPosition) {
            onPosition(null, "Geolocation is not supported by this browser.");
        }

        return;
    }

    locationStopped = false;


    function updateLocation() {

        if (locationStopped) {
            return;
        }

        navigator.geolocation.getCurrentPosition(

            function(position) {

                if (locationStopped) {
                    return;
                }

                const latitude = position.coords.latitude;

                const longitude = position.coords.longitude;


                // -----------------------------------------
                // SAVE DRIVER LOCATION TO DATABASE
                // -----------------------------------------

                fetch("save_location.php", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/x-www-form-urlencoded"
                    },
                    body: new URLSearchParams({
                        latitude: latitude,
                        longitude: longitude
                    })
                })
                .then(response => response.json())
                .then(data => {

                    // Offline or no active delivery: stop sharing.
                    if (data.stop) {

                        stopSharingLocation(onPosition, data.message);

                        return;
                    }

                    if (!data.success) {
                        console.error("Location was not saved:", data.message);
                    }

                })
                .catch(error => {

                    // Connection problem: the last saved position stays on the maps.
                    console.error("Save location error:", error);

                });


                if (onPosition) {
                    onPosition([latitude, longitude]);
                }

            },

            function(error) {

                // GPS signal lost: keep trying; the last saved position stays visible.
                console.error("Location error:", error);

                if (onPosition) {
                    onPosition(null, "Unable to get your location: " + error.message);
                }

            },

            {
                enableHighAccuracy: true,

                timeout: 15000,

                maximumAge: 10000
            }

        );

    }


    updateLocation();

    locationTimer = setInterval(updateLocation, 30000);

    // Send a fresh position as soon as the connection comes back.
    window.addEventListener("online", updateLocation);

}
