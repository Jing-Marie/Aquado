// =====================================================
// SHARE DRIVER LOCATION
// Sends the driver's GPS position every 30 seconds so the
// customer and the station can follow active deliveries.
// onPosition(position, errorMessage) is optional.
// =====================================================

function startSharingLocation(onPosition) {

    if (!navigator.geolocation) {

        if (onPosition) {
            onPosition(null, "Geolocation is not supported by this browser.");
        }

        return;
    }


    function updateLocation() {

        navigator.geolocation.getCurrentPosition(

            function(position) {

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

                    if (!data.success) {
                        console.error("Location was not saved:", data.message);
                    }

                })
                .catch(error => {

                    console.error("Save location error:", error);

                });


                if (onPosition) {
                    onPosition([latitude, longitude]);
                }

            },

            function(error) {

                console.error("Location error:", error);

                if (onPosition) {
                    onPosition(null, "Unable to get your location: " + error.message);
                }

            },

            {
                enableHighAccuracy: true,

                timeout: 10000,

                maximumAge: 0
            }

        );

    }


    updateLocation();

    setInterval(updateLocation, 30000);

}
