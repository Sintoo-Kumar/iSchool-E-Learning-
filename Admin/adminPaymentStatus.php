<?php
  define('TITLE', 'Payment Status');
  define('PAGE', 'paymentstatus');
  include('./adminInclude/header.php'); 
  header("Pragma: no-cache");
  header("Cache-Control: no-cache");
  header("Expires: 0");
  include('../dbConnection.php');
 
  require_once("../PaytmKit/lib/config_paytm.php");
  require_once("../PaytmKit/lib/encdec_paytm.php");

  $ORDER_ID = "";
  $requestParamList = array();
  $responseParamList = array();

  if (isset($_POST["ORDER_ID"]) && $_POST["ORDER_ID"] != "") {
      $ORDER_ID = $_POST["ORDER_ID"];
      $requestParamList = array("MID" => PAYTM_MERCHANT_MID, "ORDERID" => $ORDER_ID);
      $StatusCheckSum = getChecksumFromArray($requestParamList, PAYTM_MERCHANT_KEY);
      $requestParamList['CHECKSUMHASH'] = $StatusCheckSum;

      $responseParamList = getTxnStatusNew($requestParamList);
  }
?>  

<div class="container">
    <h2 class="text-center my-4">Payment Status</h2>
    <form method="post" action="">
        <div class="form-group row">
            <label class="offset-sm-3 col-form-label">Order ID: </label>
            <div>
                <input class="form-control mx-3" id="ORDER_ID" tabindex="1" maxlength="20" size="20" name="ORDER_ID" autocomplete="off" value="<?php echo $ORDER_ID ?>">
            </div>
            <div>
                <input class="btn btn-primary mx-4" value="View" type="submit">
            </div>
        </div>
    </form>
</div>

<div class="container">
    <?php
    if (isset($responseParamList) && count($responseParamList) > 0) {
        if (isset($responseParamList["ORDERID"])) {
            $sql = "SELECT order_id FROM courseorder WHERE order_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $responseParamList["ORDERID"]);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                ?>
                <div class="row justify-content-center">
                    <div class="col-auto">
                        <h2 class="text-center">Payment Receipt</h2>
                        <table class="table table-bordered">
                            <tbody>
                            <?php
                            foreach ($responseParamList as $paramName => $paramValue) {
                                ?>
                                <tr>
                                    <td><label><?php echo $paramName ?></label></td>
                                    <td><?php echo $paramValue ?></td>
                                </tr>
                                <?php
                            }
                            ?>
                            <tr>
                                <td></td>
                                <td><button class="btn btn-primary" onclick="javascript:window.print();">Print Receipt</button></td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php
            } else {
                echo "<div class='alert alert-danger'>Order ID not found in database.</div>";
            }
        } else {
            echo "<div class='alert alert-warning'>Invalid response from payment gateway. Please try again.</div>";
        }
    }
    ?>
</div>

<?php
include('./adminInclude/footer.php'); 
?>
