<?php

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
$host = $objConf->host;
//Escriba el nombre de usuario de la base de datos
$login = $objConf->user;
//Escriba la contraseña del usuario de la base de datos
$password = $objConf->password;
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
$sql_order =  "SELECT * FROM ".$pf."virtuemart_orders WHERE order_number = '".$refVenta."'"; 
$query = mysqli_query($conn, $sql_order);
if ($query){
    $row = mysqli_fetch_object($query);
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
}else{
    $order_status_final = "C";
}

switch($estadoPol)
{
    case 'Aceptada':
        echo 'Aceptada ' . $refVenta . '<br>';
        $sql = "UPDATE ".$pf."virtuemart_orders SET order_status ='".$order_status_final."' WHERE order_number = '".$refVenta."'";
        $sqld = "UPDATE ".$pf."virtuemart_order_histories SET order_status_code ='".$order_status_final."' WHERE virtuemart_order_id = '".$refOrderId."'";
        $sqli = "UPDATE ".$pf."virtuemart_order_items SET order_status ='".$order_status_final."' WHERE virtuemart_order_id = '".$refOrderId."' AND  virtuemart_order_item_id = '".$refOrderIditem."' ";

         if (mysqli_query($conn, $sql) && mysqli_query($conn, $sqld) && mysqli_query($conn, $sqli)) {
            echo "Record updated successfully";
        } else {
            die("Connection failed: " . mysqli_connect_error());
        }

    break;
    case 'Rechazada': 
        echo 'Rechazada ' . $refVenta . '<br>';
        $sql = "UPDATE ".$pf."virtuemart_orders SET order_status ='X' WHERE order_number = '".$refVenta."'";
        $sqld = "UPDATE ".$pf."virtuemart_order_histories SET order_status_code ='X' WHERE virtuemart_order_id = '".$refOrderId."'";
        $sqli = "UPDATE ".$pf."virtuemart_order_items SET order_status ='X' WHERE virtuemart_order_id = '".$refOrderId."' AND  virtuemart_order_item_id = '".$refOrderIditem."' ";

         if (mysqli_query($conn, $sql) && mysqli_query($conn, $sqld) && mysqli_query($conn, $sqli)) {
        echo "Record updated successfully";
        } else {

            die("Connection failed: " . mysqli_connect_error());
        }
    break;           

    case 'Pendiente':
        echo 'Pendiente ' . $refVenta . '<br>';
            $sql = "UPDATE ".$pf."virtuemart_orders SET order_status ='P' WHERE order_number = '".$refVenta."'";
            $sqld = "UPDATE ".$pf."virtuemart_order_histories SET order_status_code ='P' WHERE virtuemart_order_id = '".$refOrderId."'";
            $sqli = "UPDATE ".$pf."virtuemart_order_items SET order_status ='P' WHERE virtuemart_order_id = '".$refOrderId."' AND  virtuemart_order_item_id = '".$refOrderIditem."' ";

         if (mysqli_query($conn, $sql) && mysqli_query($conn, $sqld) && mysqli_query($conn, $sqli)) {
        echo "Record updated successfully";
        } else {
            die("Connection failed: " . mysqli_connect_error());
        }
    break;
}
}else{
    mysqli_close($conn);
}


exit;
?>