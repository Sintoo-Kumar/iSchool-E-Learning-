<?php
header("Pragma: no-cache");
header("Cache-Control: no-cache");
header("Expires: 0");

include('../dbConnection.php');
session_start();

// Required files for Paytm integration
require_once("./lib/config_paytm.php");
require_once("./lib/encdec_paytm.php");

$paytmChecksum = "";
$paramList = array();
$isValidChecksum = false;

// Fetch POST data sent from Paytm
$paramList = $_POST;
$paytmChecksum = isset($_POST["CHECKSUMHASH"]) ? $_POST["CHECKSUMHASH"] : "";

// Log incoming data for debugging
file_put_contents("paytm_log.txt", print_r($paramList, true));

// Verify checksum to validate received data
$isValidChecksum = verifychecksum_e($paramList, PAYTM_MERCHANT_KEY, $paytmChecksum);

if ($isValidChecksum === true) {
    if ($_POST["STATUS"] === "TXN_SUCCESS") {
        echo "<b>Transaction status is success</b><br/>";

        // Process successful transaction
        if (isset($_POST['ORDERID']) && isset($_POST['TXNAMOUNT'])) {
            $order_id = $conn->real_escape_string($_POST['ORDERID']);
            $stu_email = $conn->real_escape_string($_SESSION['stuLogEmail'] ?? '');
            $course_id = $conn->real_escape_string($_SESSION['course_id'] ?? '');
            $status = $conn->real_escape_string($_POST['STATUS']);
            $respmsg = $conn->real_escape_string($_POST['RESPMSG']);
            $amount = $conn->real_escape_string($_POST['TXNAMOUNT']);
            $date = $conn->real_escape_string($_POST['TXNDATE']);

            if (!empty($stu_email) && !empty($course_id)) {
                $sql = "INSERT INTO courseorder (order_id, stu_email, course_id, status, respmsg, amount, order_date)
                        VALUES ('$order_id', '$stu_email', '$course_id', '$status', '$respmsg', '$amount', '$date')";
                
                if ($conn->query($sql) === true) {
                    echo "Redirecting to My Profile....";
                    echo "<script>
                            setTimeout(() => {
                                window.location.href = '../Student/myCourse.php';
                            }, 1500);
                          </script>";
                } else {
                    echo "<b>Error saving transaction details.</b><br/>";
                    file_put_contents("paytm_log.txt", "DB Error: " . $conn->error . "\n", FILE_APPEND);
                }
            } else {
                echo "<b>Session data missing. Please log in again.</b>";
            }
        }
    } else {
        echo "<b>Transaction status is failure</b><br/>";
    }
} else {
    echo "<b>Checksum mismatched. Transaction could be suspicious.</b>";
    file_put_contents("paytm_log.txt", "Checksum mismatch detected\n", FILE_APPEND);
}
?>
