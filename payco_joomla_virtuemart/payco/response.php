<?php

$url = 'https://secure.epayco.io/validation/v1/reference/'.$_GET['ref_payco'];
$responseData = agafa_dades($url,false,goter());
$jsonData = @json_decode($responseData, true);
$validationData = $jsonData['data'];

function agafa_dades($url) {
    if (function_exists('curl_init')) {
        $ch = curl_init();
        $timeout = 5;
        $user_agent='Mozilla/5.0 (Windows NT 6.1; rv:8.0) Gecko/20100101 Firefox/8.0';
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_USERAGENT, $user_agent);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
        curl_setopt($ch,CURLOPT_TIMEOUT,$timeout);
        curl_setopt($ch,CURLOPT_MAXREDIRS,10);
        $data = curl_exec($ch);
        curl_close($ch);
        return $data;
    }else{
        $data =  @file_get_contents($url);
        return $data;
    }
}
function goter(){
    $context = stream_context_create(array(
        'http' => array(
            'method' => 'POST',
            'header' => 'Content-Type: application/x-www-form-urlencoded',
            'protocol_version' => 1.1,
            'timeout' => 10,
            'ignore_errors' => true
        )
    ));
}

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

$url = $_SERVER['REQUEST_SCHEME']."://".$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'];
$server_name = str_replace('/plugins/vmpayment/payco/payco/response.php?ref_payco='.$_GET['ref_payco'],'/index.php?option=com_virtuemart&view=orders&layout=details&order_number=',$url);
$new_url = $server_name.$validationData['x_extra1']."&order_pass=".$validationData['x_extra2']."&";
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
    $estadoPol = trim($validationData['x_respuesta']);
    $refVenta = trim($validationData['x_extra1']);
    $refOrderId=trim($validationData['x_extra3']);
    $refOrderIditem=trim($validationData['x_extra3']);
    $x_test_request = trim($validationData['x_test_request']);
    $x_cod_transaction_state = trim($validationData['x_cod_transaction_state']);
    $isTestTransaction = $x_test_request == 'TRUE' ? "yes" : "no";
    $isTestMode = $isTestTransaction == "yes" ? true : false;
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
    switch($x_cod_transaction_state)
    {
        case 1:
            $sqlProduct_ = "UPDATE ".$pf."virtuemart_products SET product_in_stock ='".$stockToUpdate."'
                                     WHERE virtuemart_product_id = '".(int)$product_row->virtuemart_product_id."'";
            if($row->order_status == "P" || $row->order_status == "X"){
            }
            $sql = "UPDATE ".$pf."virtuemart_orders SET order_status ='".$order_status_final."' WHERE order_number = '".$refVenta."'";
            $sqld = "UPDATE ".$pf."virtuemart_order_histories SET order_status_code ='".$order_status_final."' WHERE virtuemart_order_id = '".$refOrderId."'";
            $sqli = "UPDATE ".$pf."virtuemart_order_items SET order_status ='".$order_status_final."' WHERE virtuemart_order_id = '".$refOrderId."' AND  virtuemart_order_item_id = '".$refOrderIditem."' ";
        break;
        case 2: 
            if($row->order_status != "X"){
                $stockToUpdate = ((int)$products_->product_in_stock+(int)$product_row->product_quantity);
                $sqlProduct_ = "UPDATE ".$pf."virtuemart_products SET product_in_stock ='".$stockToUpdate."'
                WHERE virtuemart_product_id = '".(int)$product_row->virtuemart_product_id."'";
            }
            $sql = "UPDATE ".$pf."virtuemart_orders SET order_status ='X' WHERE order_number = '".$refVenta."'";
            $sqld = "UPDATE ".$pf."virtuemart_order_histories SET order_status_code ='X' WHERE virtuemart_order_id = '".$refOrderId."'";
            $sqli = "UPDATE ".$pf."virtuemart_order_items SET order_status ='X' WHERE virtuemart_order_id = '".$refOrderId."' AND  virtuemart_order_item_id = '".$refOrderIditem."' ";
        break;           
        case 3:
            if($row->order_status == "P" || $row->order_status == "X"){
                $sqlProduct_ = "UPDATE ".$pf."virtuemart_products SET product_in_stock ='".$stockToUpdate."'
                WHERE virtuemart_product_id = '".(int)$product_row->virtuemart_product_id."'";
            }
            $sql = "UPDATE ".$pf."virtuemart_orders SET order_status ='P' WHERE order_number = '".$refVenta."'";
            $sqld = "UPDATE ".$pf."virtuemart_order_histories SET order_status_code ='P' WHERE virtuemart_order_id = '".$refOrderId."'";
            $sqli = "UPDATE ".$pf."virtuemart_order_items SET order_status ='P' WHERE virtuemart_order_id = '".$refOrderId."' AND  virtuemart_order_item_id = '".$refOrderIditem."' ";
            
        break;
        default:
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
    if (mysqli_query($conn, $sql) && mysqli_query($conn, $sqld) && mysqli_query($conn, $sqli)) {} else {
        die("Connection failed: " . mysqli_connect_error());
    }
}else{
    mysqli_close($conn);
}
header("Location:". $new_url);
exit;
?>