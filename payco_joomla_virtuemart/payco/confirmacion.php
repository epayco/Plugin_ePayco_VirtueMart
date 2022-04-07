<?php
//require_once('../../../../administrator/components/com_virtuemart/plugins/vmpsplugin.php');
function extractWord($text, $position){
    $words = explode('|', $text);
    $characters = -1; 
    foreach($words as $word){
       $characters += strlen($word);
       if($characters >= $position){
          return $word;
       }   
    }   
    return ''; 
 } 
 
require_once('../../../../configuration.php');
$objConf = new JConfig();
//Escriba su Host, por lo general es 'localhost'
$host = isset($objConf->host) ? $objConf->host : null;
//Escriba el nombre de usuario de la base de datos
$login = isset($objConf->user) ? $objConf->user : null;
//Escriba la contraseña del usuario de la base de datos
$password = isset($objConf->password) ? $objConf->password : null; 
//Escriba el nombre de la base de datos a utilizar
$basedatos = $objConf->db;
//prefijo de la base de datos
$pf = $objConf->dbprefix;
//conexion a mysql
$mensajeLog = "";

$conn = mysqli_connect($host, $login,$password,$basedatos);
// $conexion = mysql_connect($host, $login, $password);
if($conn){
    echo "Connected Successfully...."; 
    $estadoPol = trim($_REQUEST['x_respuesta']);
    $refVenta = trim($_REQUEST['x_extra1']);
    $refOrderId=trim($_REQUEST['x_extra3']);
    $refOrderIditem=trim($_REQUEST['x_extra3']);
    $x_test_request = trim($_REQUEST['x_test_request']);
    $x_cod_transaction_state = trim($_REQUEST['x_cod_transaction_state']);
    $x_approval_code = trim($_REQUEST['x_approval_code']);
    $x_signature = trim($_REQUEST['x_signature']);
    $x_ref_payco = trim($_REQUEST['x_ref_payco']);
    $x_transaction_id = trim($_REQUEST['x_transaction_id']);
    $x_amount = trim($_REQUEST['x_amount']);
    $x_currency_code = trim($_REQUEST['x_currency_code']);

    $sql_order =  "SELECT * FROM ".$pf."virtuemart_orders WHERE order_number = '".$refVenta."'"; 
    $query = mysqli_query($conn, $sql_order);
    if ($query){
        $row = mysqli_fetch_object($query);
        $amount_order = floatval( $row->order_total );
        $sql_order_ =  "SELECT * FROM ".$pf."virtuemart_paymentmethods WHERE virtuemart_paymentmethod_id = '".(int)$row->virtuemart_paymentmethod_id."'"; 
        $query_ = mysqli_query($conn, $sql_order_);
        $row_ = mysqli_fetch_object($query_);
        $text = $row_->payment_params;
        $position = strpos($text, 'epayco_status_order');
        $p_cust = strpos($text, 'payco_user_id');
        $p_cust_extracWord = extractWord($text, $p_cust);
        $p_cust_explode = explode('=', $p_cust_extracWord);
        $p_cust_id = preg_replace('/[\@\.\;\" "]+/', '', $p_cust_explode[1]);
        $p_key = strpos($text, 'payco_encrypt_key');
        $p_key_extracWord = extractWord($text, $p_key);
        $p_key_explode = explode('=', $p_key_extracWord);
        $p_key_id = preg_replace('/[\@\.\;\" "]+/', '', $p_key_explode[1]);
        $p_test = strpos($text, 'p_external_request');
        $p_test_extracWord = extractWord($text, $p_test);
        $p_test_explode = explode('=', $p_test_extracWord);
        $p_test = preg_replace('/[\@\.\;\" "]+/', '', $p_test_explode[1]);
        $word = extractWord($text, $position);
        $words = explode('=', $word);
        $res = preg_replace('/[0-9\@\.\;\" "]+/', '', $words[1]);
        $sql_order_status =  "SELECT * FROM ".$pf."virtuemart_orderstates WHERE order_status_name = '".$res."'"; 
        $query_order = mysqli_query($conn, $sql_order_status);
        $row_order = mysqli_fetch_object($query_order);
        $order_status_final = $row_order->order_status_code;
        $orderProduct_query = "SELECT * FROM ".$pf."virtuemart_order_items WHERE virtuemart_order_id = '".(int)$row->virtuemart_order_id."'"; 
        $orderProductQuery = mysqli_query($conn, $orderProduct_query);
        $product_row = mysqli_fetch_object($orderProductQuery);
        $product_query = "SELECT * FROM ".$pf."virtuemart_products WHERE virtuemart_product_id = '".(int)$product_row->virtuemart_product_id."'"; 
        $productQuery = mysqli_query($conn, $product_query);
        $products_ = mysqli_fetch_object($productQuery);
        $stockToUpdate = ((int)$products_->product_in_stock-(int)$product_row->product_quantity);
    }else{
        $order_status_final = "C";
    }
        $signature = hash('sha256',
            trim($p_cust_id).'^'
            .trim($p_key_id).'^'
            .$x_ref_payco.'^'
            .$x_transaction_id.'^'
            .$x_amount.'^'
            .$x_currency_code
        );
        $isTestTransaction = $x_test_request == 'TRUE' ? "yes" : "no";
        $isTestMode = $isTestTransaction == "yes" ? "true" : "false";
        $isTestPluginMode = $p_test == 'TRUE' ? "yes" : "no"; 
        if( $amount_order == floatval($x_amount)){
            if("yes" == $isTestPluginMode){
                $validation = true;
            }
            if("no" == $isTestPluginMode ){
                if($x_approval_code != "000000" && $x_cod_transaction_state == 1){
                    $validation = true;
                }else{
                    if($x_cod_transaction_state != 1){
                        $validation = true;
                    }else{
                        $validation = false;
                    }
                }
                
            }
        }else{
            $validation = false;
        }

        if($signature == $x_signature && $validation){
            switch($x_cod_transaction_state)
            {
                case 1:
                    echo 'Aceptada ' . $refVenta . '<br>';
                    
                    $sql = "UPDATE ".$pf."virtuemart_orders SET order_status ='".$order_status_final."' WHERE order_number = '".$refVenta."'";
                    $sqld = "UPDATE ".$pf."virtuemart_order_histories SET order_status_code ='".$order_status_final."' WHERE virtuemart_order_id = '".$refOrderId."'";
                    $sqli = "UPDATE ".$pf."virtuemart_order_items SET order_status ='".$order_status_final."' WHERE virtuemart_order_id = '".$refOrderId."' AND  virtuemart_order_item_id = '".$refOrderIditem."' ";
                    
                    break;
                case 2: 
                    echo 'Rechazada ' . $refVenta . '<br>';
                    if($row->order_status != "X"){
                        $stockToUpdate = ((int)$products_->product_in_stock+(int)$product_row->product_quantity);
                        $sqlProduct_ = "UPDATE ".$pf."virtuemart_products SET product_in_stock ='".$stockToUpdate."'
                        WHERE virtuemart_product_id = '".(int)$product_row->virtuemart_product_id."'";
                        mysqli_query($conn, $sqlProduct_);
                    }
                    $sql = "UPDATE ".$pf."virtuemart_orders SET order_status ='X' WHERE order_number = '".$refVenta."'";
                    $sqld = "UPDATE ".$pf."virtuemart_order_histories SET order_status_code ='X' WHERE virtuemart_order_id = '".$refOrderId."'";
                    $sqli = "UPDATE ".$pf."virtuemart_order_items SET order_status ='X' WHERE virtuemart_order_id = '".$refOrderId."' AND  virtuemart_order_item_id = '".$refOrderIditem."' ";
                break;           
                case 3:
                    echo 'Pendiente ' . $refVenta . '<br>';
                    if($row->order_status != "P"){
                        $stockToUpdate = ((int)$products_->product_in_stock-(int)$product_row->product_quantity);
                        $sqlProduct_ = "UPDATE ".$pf."virtuemart_products SET product_in_stock ='".$stockToUpdate."'
                        WHERE virtuemart_product_id = '".(int)$product_row->virtuemart_product_id."'";
                        mysqli_query($conn, $sqlProduct_);
                    }
                    $sql = "UPDATE ".$pf."virtuemart_orders SET order_status ='P' WHERE order_number = '".$refVenta."'";
                    $sqld = "UPDATE ".$pf."virtuemart_order_histories SET order_status_code ='P' WHERE virtuemart_order_id = '".$refOrderId."'";
                    $sqli = "UPDATE ".$pf."virtuemart_order_items SET order_status ='P' WHERE virtuemart_order_id = '".$refOrderId."' AND  virtuemart_order_item_id = '".$refOrderIditem."' ";
                break;
                default:
                    echo 'default ' . $refVenta . '<br>';
                    if($row->order_status != "X"){
                        $stockToUpdate = ((int)$products_->product_in_stock+(int)$product_row->product_quantity);
                        $sqlProduct_ = "UPDATE ".$pf."virtuemart_products SET product_in_stock ='".$stockToUpdate."'
                        WHERE virtuemart_product_id = '".(int)$product_row->virtuemart_product_id."'";
                        mysqli_query($conn, $sqlProduct_);
                    }
                    $sql = "UPDATE ".$pf."virtuemart_orders SET order_status ='X' WHERE order_number = '".$refVenta."'";
                    $sqld = "UPDATE ".$pf."virtuemart_order_histories SET order_status_code ='X' WHERE virtuemart_order_id = '".$refOrderId."'";
                    $sqli = "UPDATE ".$pf."virtuemart_order_items SET order_status ='X' WHERE virtuemart_order_id = '".$refOrderId."' AND  virtuemart_order_item_id = '".$refOrderIditem."' ";
                break;
            }
        }else{
            if($row->order_status != "X"){
                $stockToUpdate = ((int)$products_->product_in_stock+(int)$product_row->product_quantity);
                $sqlProduct_ = "UPDATE ".$pf."virtuemart_products SET product_in_stock ='".$stockToUpdate."'
                WHERE virtuemart_product_id = '".(int)$product_row->virtuemart_product_id."'";
                mysqli_query($conn, $sqlProduct_);
            }
            echo 'Fallida ' . $refVenta . '<br>';
            $sql = "UPDATE ".$pf."virtuemart_orders SET order_status ='X' WHERE order_number = '".$refVenta."'";
            $sqld = "UPDATE ".$pf."virtuemart_order_histories SET order_status_code ='X' WHERE virtuemart_order_id = '".$refOrderId."'";
            $sqli = "UPDATE ".$pf."virtuemart_order_items SET order_status ='X' WHERE virtuemart_order_id = '".$refOrderId."' AND  virtuemart_order_item_id = '".$refOrderIditem."' ";
            
        }
        if (mysqli_query($conn, $sql) && mysqli_query($conn, $sqld) && mysqli_query($conn, $sqli)) {
            echo "Record updated successfully";
        } else {
            die("Connection failed: " . mysqli_connect_error());
        }
}else{
    mysqli_close($conn);
}
exit;
?>