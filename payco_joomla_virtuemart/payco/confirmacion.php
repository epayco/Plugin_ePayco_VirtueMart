<?php

    function url(){
    if(isset($_SERVER['HTTPS'])){
        $protocol = ($_SERVER['HTTPS'] && $_SERVER['HTTPS'] != "off") ? "https" : "http";
    }
    else{
        $protocol = 'http';
    }
    return $protocol . "://" . $_SERVER['HTTP_HOST'] ;
    }

    error_reporting(8191);
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
    $conexion = mysql_connect($host, $login, $password);

    if(!$conexion){
        $mensajeLog .= "[".date("Y-m-d H:i:s")."] Error al conectar la base de datos - ".mysql_error()."\n";
    }
    if(!mysql_select_db($basedatos, $conexion))
    {
        $mensajeLog .= "[".date("Y-m-d H:i:s")."] Error al seleccionar la base de datos - ".mysql_error()."\n";
    }

  

    $sql = "select params from " . $pf . "extensions where element='payco'";
    $params_query = mysql_query($sql);
    
    if(mysql_num_rows($params_query) == 1)
    {
        $params = mysql_fetch_array($params_query);        
        $params = json_decode($params['params']);        
    }

   //var_export($sql);


   
    //$confirm = mysql_query("select conf from " . $pf . "virtuemart_payment_plg_payco where refventa = '" . $_REQUEST['ref_venta'] . "' and conf = 1;", $conexion);
     
    //if(mysql_num_rows($confirm) == 0){

        /*$usuarioId = $_REQUEST['usuario_id'];
        $fecha = $_REQUEST['x_fecha_transaccion'];
        $refVenta = $_REQUEST['ref_venta'];
        $refPol = $_REQUEST['ref_pol'];
        $estadoPol = $_REQUEST['estado_pol'];
        $formaPago = $_REQUEST['tipo_medio_pago'];
        $banco = $_REQUEST['medio_pago'];
        $codigo = $_REQUEST['codigo_respuesta_pol'];
        $mensaje = $_REQUEST['mensaje'];
        $valor = $_REQUEST['valor'];*/

        // consulta a la bd
        $sql = "INSERT INTO ". $pf ."virtuemart_payment_plg_payco(
                    fecha,
                    refpol,
                    estado_pol,
                    formapago,
                    codigo_respuesta_pol,
                    mensaje,
                    valor,
                    conf
                )VALUES(
                    '".$_REQUEST['x_fecha_transaccion']."',
                    '".$_REQUEST['x_id_factura']."',
                    '".$_REQUEST['x_respuesta']."',                    
                    '".$_REQUEST['x_franchise']."',
                    '".$_REQUEST['x_transaction_id']."',
                    '".$_REQUEST['x_response_reason_text']."',
                    '".$_REQUEST['x_amount']."'
                )";

        // select para actualizar la bd pedidos_confir y jos_vm_orders
        $estadoPol = $_REQUEST['x_respuesta'];
        $refVenta = $_REQUEST['x_id_factura'];
        switch($estadoPol)
        {
            case 'Aceptada':
                $result_a = mysql_query("UPDATE ".$pf."virtuemart_orders SET order_status ='C' WHERE order_number = '".$refVenta."';");
                if(!$result_a)
                {
                	die(mysql_error());
                	$mensajeLog .= "[".date("Y-m-d H:i:s")."] Error al ejecutar el query (".$sql.") la base de datos - ".mysql_error()."\n";
                }
            break;
            case 'Rechazada': 
                $result_c = mysql_query("UPDATE ".$pf."virtuemart_orders SET order_status ='X' WHERE order_number = '".$refVenta."';");
                if(!$result_c)
                {
                	$mensajeLog .= "[".date("Y-m-d H:i:s")."] Error al ejecutar el query (".$sql.") la base de datos - ".mysql_error()."\n";
                }
            break;           
            case 'Pendiente':
                $result_p = mysql_query("UPDATE ".$pf."virtuemart_orders SET order_status ='P' WHERE order_number = '".$refVenta."';");
                if(!$result_p)
                {
                	$mensajeLog .= "[".date("Y-m-d H:i:s")."] Error al ejecutar el query (".$sql.") la base de datos - ".mysql_error()."\n";
                }
            break;
        }

        $result = mysql_query($sql);
       

        echo '<html>
        <head>
            <link href="default.css" type=text/css rel=stylesheet> 
        </head>
            <body>
                <div class="">
                    <h1> Transaccion '.$_REQUEST['x_respuesta'].'</h1>
                    <h3> Apreciado cliente, la transaccion No.'. $_REQUEST['x_transaction_id'].'     
                    fue recibida por nuestro sistema.</h3>
                    <h2>Datos de compra:</h3>
                    <table >
                        <tbody>
                        <tr>
                            <th width="240"><strong> Codigo de Referencia: </strong>&nbsp;</th>
                            <td width="240">'.$_REQUEST['x_id_factura'].'</td>
                        </tr>
                        <tr>
                            <th><strong> Valor: </strong></th>
                            <td>'.$_REQUEST['x_amount'].'</td>
                        </tr>
                        <tr>
                            <th><strong> Moneda: </strong></th>
                            <td>'.$_REQUEST['x_currency_code'].'</td>
                        </tr>
                        </tbody>
                    </table>
                    <h2>Datos de la transaccion:</h2>
                    <table>
                        <tbody>
                            <tr>
                                <th width="240"><strong> Fecha de Procesamiento: </strong>&nbsp;</th>
                                <td width="240">'.$_REQUEST['x_fecha_transaccion'].'</td>
                            </tr>
                            <tr>
                                <th><strong> Recibo No.: </strong></th>
                                <td>'.$_REQUEST['x_transaction_id'].'</td>
                            </tr>
                            <tr>
                                <th><strong> Transaccion No.: </strong></th>
                                <td>'.$_REQUEST['x_ref_payco'].'</td>
                            </tr>
                            
                            <tr>
                                <th><strong> Banco o Franquicia: </strong></th>
                                <td>'.$_REQUEST['x_franchise'].'</td>
                            </tr>
                             <tr>
                                <th><strong> Codigo de aprobacion: </strong></th>
                                <td>'.$_REQUEST['x_approval_code'].'</td>
                            </tr>
                            <tr>
                                <th><strong> Codigo de Respuesta POL: </strong></th>
                                <td>'.$_REQUEST['x_response_reason_text'].'</td>
                            </tr>
                            <tr>
                                <td><a href="'.url().'">Regresar a la tienda</a></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </body>
        </html>';



?>
