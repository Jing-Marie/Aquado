<?php

/*
|--------------------------------------------------------------------------
| GCASH PAYMENT PHOTOS (shared by both apps)
|--------------------------------------------------------------------------
|
| Photos are stored in the `payment_proofs` table, not in a folder: hosts
| such as Vercel do not keep uploaded files between requests. The photo's
| name is kept in orders.payment_proof. Each app serves photos through its
| own payment_proof.php after an access check.
|
*/

const PAYMENT_PROOF_MAX_BYTES = 10 * 1024 * 1024;

const PAYMENT_PROOF_TYPES = [
    "image/jpeg" => "jpg",
    "image/png" => "png",
    "image/webp" => "webp",
];


/*
 * Saves an uploaded photo and returns its name, or null with $error set.
 */
function savePaymentProof($conn, $file, &$error) {

    return storeProofPhoto($conn, $file, "Please upload a photo of the customer's GCash payment.", $error);

}


/*
 * Saves the rider's proof-of-delivery photo. It is kept in the same table
 * as the payment photos; orders.delivery_photo keeps its name.
 */
function saveDeliveryPhoto($conn, $file, &$error) {

    return storeProofPhoto($conn, $file, "Please take or upload a delivery photo as proof of delivery.", $error);

}


function storeProofPhoto($conn, $file, $missingMessage, &$error) {

    if (!$file || $file["error"] === UPLOAD_ERR_NO_FILE) {
        $error = $missingMessage;
        return null;
    }

    if ($file["error"] !== UPLOAD_ERR_OK) {
        $error = "The photo could not be uploaded. Please try again.";
        return null;
    }

    if ($file["size"] > PAYMENT_PROOF_MAX_BYTES) {
        $error = "The photo is too large (10 MB maximum).";
        return null;
    }

    $type = (new finfo(FILEINFO_MIME_TYPE))->file($file["tmp_name"]);

    if (!isset(PAYMENT_PROOF_TYPES[$type]) || getimagesize($file["tmp_name"]) === false) {
        $error = "Please upload a JPG, PNG or WEBP photo.";
        return null;
    }

    $name = bin2hex(random_bytes(16)) . "." . PAYMENT_PROOF_TYPES[$type];
    $bytes = file_get_contents($file["tmp_name"]);

    $stmt = $conn->prepare("INSERT INTO payment_proofs (name, mime_type, data) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $name, $type, $bytes);
    $saved = $stmt->execute();
    $stmt->close();

    if (!$saved) {
        $error = "The photo could not be saved. Please try again.";
        return null;
    }

    return $name;

}


/*
 * Removes a stored photo, e.g. one that was replaced or not used.
 */
function deletePaymentProof($conn, $name) {

    if ($name === null || $name === "") {
        return;
    }

    $stmt = $conn->prepare("DELETE FROM payment_proofs WHERE name = ?");
    $stmt->bind_param("s", $name);
    $stmt->execute();
    $stmt->close();

}


/*
 * Sends a stored photo to the browser.
 */
function outputPaymentProof($conn, $name) {

    if ($name === null || $name === "") {
        http_response_code(404);
        exit;
    }

    $stmt = $conn->prepare("SELECT mime_type, data FROM payment_proofs WHERE name = ?");
    $stmt->bind_param("s", $name);
    $stmt->execute();
    $proof = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$proof) {
        http_response_code(404);
        exit;
    }

    header("Content-Type: " . $proof["mime_type"]);
    header("Content-Length: " . strlen($proof["data"]));
    header("X-Content-Type-Options: nosniff");
    header("Cache-Control: private, max-age=3600");

    echo $proof["data"];
    exit;

}
