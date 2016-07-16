<?php
    error_reporting(0);
    
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
    
    $conexion = mysql_connect($host, $login, $password);
    mysql_select_db($basedatos, $conexion);

    $sql = "select params from " . $pf . "extensions where element='payco';";
    $params_query = mysql_query($sql);
    
    if(mysql_num_rows($params_query) == 1)
    {
        $params = mysql_fetch_array($params_query);        
        $params = json_decode($params['params']);        
    }
    
    switch($params->estilo)
    {
        case 0:
            $estilo="default.css";
            break;
        case 1:
            $estilo="red.css";
            break;
        case 2:
            $estilo="blue.css";
            break;
        default:
            $estilo = "default.css";
            break;
    }
    
    //escapar datos
    foreach ($_REQUEST as $key => $value) {
        $_REQUEST[$key] = mysql_real_escape_string(htmlentities($value));
    }
    foreach ($_GET as $key => $value) {
        $_REQUEST[$key] = mysql_real_escape_string(htmlentities($value));
    }
    foreach ($_POST as $key => $value) {
        $_REQUEST[$key] = mysql_real_escape_string(htmlentities($value));
    }
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
    <head>
        <title>Confirmaci&oacute;n del pago</title>
        <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
        <link rel="stylesheet" type="text/css" href="./<?php echo $estilo; ?>" />
    </head>
    <body>
        <div align="center">
            <table>
                <tr>
                    <th colspan="2">
                        <?php
                            $path_img = dirname(__FILE__).'/../../../../images/'.$params->logo;
                            if(file_exists($path_img))
                            {
                                echo '<img alt="'.$_SERVER['SERVER_NAME'].'" src="../../../../images/'.$params->logo.'" />';
                            }
                            else
                            {
                                echo "<h1>".$_SERVER['SERVER_NAME']."</h1>";
                            }
                        ?>
                        <h2>Su pago est&aacute; siendo confirmado para procesar su orden...</h2>
                    </th>
                </tr>
            <tr>
            <tr>
                <td><strong>Fecha:</strong></td><td> <?php echo(date("Y-m-d",strtotime("now"))); ?></td>
            </tr>
            <tr>
                <td><strong>N&ordm; de Recibo :</strong></td><td> <?php echo $_GET['ref_venta'] ?></td>
            </tr>
            <tr>
                <td><strong>codigo pol :</strong></td><td> <?php echo $_GET['ref_pol'] ?></td>
            </tr>
            <tr>
                <td><strong>Estado de la Transaccion:</strong></td><td> <?php
                switch($_GET['estado_pol'])
                {
                    case 1: echo "Sin abrir";
                    break;
                    case 2: echo "Abierta";
                    break;
                    case 3: echo "Pagada";
                    break;
                    case 4: echo "Pagada y Abonada";
                    break;
                    case 5: echo "Cancelada";
                    break;
                    case 6: echo "Rechazada";
                    break;
                    case 7: echo "En validacion";
                    break;
                    case 8: echo "Reversada";
                    break;
                    case 9: echo "Reversada Fraudulenta";
                    break;
                    case 10: echo "Enviada Ent. Financiera";
                    break;
                    case 11: echo "Capturando datos tarjeta de credito";
                    break;
                    case 12: echo "Esperando confirmacion sistema PSE";
                    break;
                }
                ?></td>
            </tr>
            <tr>
                <td><strong>Forma de Pago:</strong></td><td> <?php
                switch($_GET['tipo_medio_pago'])
                {
                    case 1: echo " Tarjeta débito";
                    break;
                    case 2: echo " Tarjeta de crédito";
                    break;
                    case 3: echo " Tarjeta de crédito Verified by VISA";
                    break;
                    case 4: echo " Cuentas corrientes y de ahorros PSE";
                    break;
                }
                ?>
                </td>
            </tr>
            <tr>
                <td><strong>Medio de pago:</strong></td><td>
                <?php
                switch($_GET['medio_pago'])
                {
                    case 1: echo "Colpatria";
                    break;
                    case 2: echo "Bancolombia";
                    break;
                    case 3: echo "Conavi";
                    break;
                    case 4: echo "Popular";
                    break;
                    case 5: echo "Occidente";
                    break;
                    case 6: echo "AvVillas";
                    break;
                    case 8: echo "Santander";
                    break;
                    case 10: echo "VISA";
                    break;
                    case 11: echo "Master Card";
                    break;
                    case 12: echo "American Express";
                    break;
                    case 14: echo "Davivienda";
                    break;
                    case 22: echo "Diners";
                    break;
                    case 24: echo "Verified by VISA";
                    break;
                    case 25: echo "PSE";
                    break;
                }
                ?>
                </td>
            </tr>
            <tr>
                <td><strong>Banco:</strong></td><td> <?php switch($_GET['medio_pago'])
                {
                    case 1: echo "Colpatria";
                    break;
                    case 2: echo "Bancolombia";
                    break;
                    case 3: echo "Conavi";
                    break;
                    case 4: echo "Popular";
                    break;
                    case 5: echo "Occidente";
                    break;
                    case 6: echo "AvVillas";
                    break;
                    case 8: echo "Santander";
                    break;
                    case 10: echo "VISA";
                    break;
                    case 11: echo "Master Card";
                    break;
                    case 12: echo "American Express";
                    break;
                    case 14: echo "Davivienda";
                    break;
                    case 22: echo "Diners";
                    break;
                    case 24: echo "Verified by VISA";
                    break;
                    case 25: echo "PSE";
                    break;
                }
                ?>
                </td>
            </tr>
            <tr>
                <td><strong>Mensaje:</strong></td><td> <?php echo $_GET['mensaje']; ?></td>
            </tr>
            <tr>
                <td><strong>Valor:</strong></td><td> <?php echo $_GET['valor']; ?></td>
            </tr>
            </tr>
            <tr>
                <td colspan="2">
                    <h3>Gracias por comprar con nosotros! </h3>
                </td>
            </tr>
            </table>
            <br/>
            <input type="button" value="Imprimir" onclick="window.print();"/>
        </div>
    </body>
</html>