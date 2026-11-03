// =====================================================
// PAYMENT PHOTO PREVIEW AND SIZE CHECK
// Vercel rejects request bodies over 4.5 MB before PHP runs, and phone
// photos are often bigger. Large photos are shrunk in the browser first.
// =====================================================

const PROOF_SKIP_BELOW_BYTES = 3 * 1024 * 1024;
const PROOF_MAX_SIDE_PX = 1600;
const PROOF_JPEG_QUALITY = 0.8;

// Returns a smaller JPEG copy of the photo, or the original file when it is
// already small enough or cannot be read by the browser.
function shrinkProofPhoto(file) {

    if (file.size <= PROOF_SKIP_BELOW_BYTES) {
        return Promise.resolve(file);
    }

    return new Promise(function(resolve) {

        const url = URL.createObjectURL(file);
        const img = new Image();

        img.onload = function() {

            const scale = Math.min(1, PROOF_MAX_SIDE_PX / Math.max(img.width, img.height));
            const canvas = document.createElement("canvas");
            canvas.width = Math.round(img.width * scale);
            canvas.height = Math.round(img.height * scale);
            canvas.getContext("2d").drawImage(img, 0, 0, canvas.width, canvas.height);
            URL.revokeObjectURL(url);

            canvas.toBlob(function(blob) {
                if (blob && blob.size < file.size) {
                    resolve(new File([blob], "payment-proof.jpg", { type: "image/jpeg" }));
                } else {
                    resolve(file);
                }
            }, "image/jpeg", PROOF_JPEG_QUALITY);

        };

        img.onerror = function() {
            URL.revokeObjectURL(url);
            resolve(file);
        };

        img.src = url;

    });

}


// Shows the chosen photo, shrinking it first when needed.
document.querySelectorAll(".proof-input").forEach(function(input) {

    input.addEventListener("change", function() {

        const preview = input.closest("form").querySelector(".proof-preview");

        if (!input.files || !input.files[0]) {
            preview.style.display = "none";
            return;
        }

        const original = input.files[0];

        shrinkProofPhoto(original).then(function(photo) {

            if (photo !== original && typeof DataTransfer !== "undefined") {
                const transfer = new DataTransfer();
                transfer.items.add(photo);
                input.files = transfer.files;
            }

            preview.src = URL.createObjectURL(photo);
            preview.style.display = "block";

        });

    });

});
