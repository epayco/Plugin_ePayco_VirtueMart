<?php
defined('_JEXEC') or die('Restricted access');
/**
 * @version $Id$
 * @package    VirtueMart
 * @subpackage Plugins  - Payco
 * @package VirtueMart
 * @subpackage Payment
 * @author ePayco
 * @link https://virtuemart.net
 * @copyright Copyright (c) 2004 - 2018 VirtueMart Team. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * VirtueMart is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 * @version $Id$
 */

class  EpaycoPaymentPlugin extends AbstractPaymentPlugin
{
    public function __construct(&$subject, $config) {
        parent::__construct($subject, $config, 'Epayco');
    }

    public function processEpaycoPayment($cart, $order):void
    {

        if (!($this->_currentMethod = $this->getVmPluginMethod($order['details']['BT']->virtuemart_paymentmethod_id))) {
            exit(); // Another method was selected, do nothing
        }

        if (!$this->selectedThisElement($this->_currentMethod->payment_element)) {
            exit();
        }

        $this->getPaymentCurrency($this->_currentMethod);
    
        $this->logInfo('plgVmConfirmedOrder order number: ' . $order['details']['BT']->order_number, 'message');
        $subscribe_id = NULL;

        if (!class_exists('VirtueMartModelOrders')) {
            require(VMPATH_ADMIN . DS . 'models' . DS . 'orders.php');
        }

        if (!class_exists('VirtueMartModelCurrency')) {
            require(VMPATH_ADMIN . DS . 'models' . DS . 'currency.php');
        }

        $email_currency = $this->getEmailCurrency($this->_currentMethod);

        $name = $order['details']['BT']->first_name;
        if (isset($order['details']['BT']->middle_name) and $order['details']['BT']->middle_name) {
            $name .= $order['details']['BT']->middle_name;
        }
        $address = $order['details']['BT']->address_1;
        if (isset($order['details']['BT']->address_2) and $order['details']['BT']->address_2) {
            $name .= $order['details']['BT']->address_2;
        }
        $quantity=0;
        foreach ($cart->products as $key => $product) {
            $quantity = $quantity + $product->quantity;
            $nameproduct = $product->product_name;
        }

        if (count($cart->products) > 1) {
            $nameproduct .= ', etc';
        }

        $p_amount_float = round(floatval($order['details']['BT']->order_total), 2);
        $iva = round(floatval($order['details']['BT']->order_billTaxAmount), 2);
        $baseDevolucionIva = $p_amount_float - $iva;

        $payMethod = $order['details']['BT']->virtuemart_paymentmethod_id;

        $parts = explode('|', $this->_currentMethod->payment_params);
        $payco_public_key = '';
        $payco_private_key = '';
        $epayco_lang = '';
        $payco_user_id = '';
        $p_test_request = '';
        $p_external_request = '';

        foreach ($parts as $part) {
            $keyValue = explode('=', $part, 2);
            if (count($keyValue) === 2) {
                $key = trim($keyValue[0]);
                $value = trim($keyValue[1], '"');
                if ($key === 'payco_public_key') {
                    $payco_public_key = $value;
                } elseif ($key === 'payco_private_key') {
                    $payco_private_key = $value;
                } elseif ($key === 'epayco_lang') {
                    $epayco_lang = $value;
                } elseif ($key === 'payco_user_id') {
                    $payco_user_id = $value;
                } elseif ($key === 'p_test_request') {
                    $p_test_request = $value;
                } elseif ($key === 'p_external_request') {
                    $p_external_request = $value;
                }
            }
        }

        if ($p_test_request == "TRUE") {
            $test = "true";
        } else {
            $test = "false";
        }

        if ($p_external_request == "TRUE") {
            $external = "false";
        } else {
            $external = "true";
        }


        $autoclick = "true";
        $ip = $this->getIp();
        $currency_model = VmModel::getModel('currency');
        $currency_payment = $currency_model->getCurrency()->currency_code_3;
        $retourParams = $this->setRetourParams($order, $this->getContext());
        $baseUrl = JURI::base();
        $post_variables = array(
            'SOCIETE' => $order['details']['BT']->company,
            'NOM' => $order['details']['BT']->last_name,
            'PRENOM' => $name,
            'p_billing_adress' => $address,
            'CODEPOSTAL' => $order['details']['BT']->zip,
            'VILLE' => $order['details']['BT']->city,
            'p_billing_country' => ShopFunctions::getCountryByID($order['details']['BT']->virtuemart_country_id, 'country_2_code'),
            'p_cellphone_billing' => !empty($order['details']['BT']->phone_1) ? $order['details']['BT']->phone_1 : $order['details']['BT']->phone_2,
            'p_billing_email' => $order['details']['BT']->email,
            'MODULE' => 'VirtueMart',
            'MODULE_VERSION' => '3.6.10',
            'external' => $external,
            'p_description'     => 'ORDEN DE COMPRA # ' . $order['details']['BT']->order_number,
            'p_cust_id_cliente' => $payco_user_id,
            'p_product_name' => $nameproduct,
            'p_id_factura' => $order['details']['BT']->order_number,
            'p_country_code'    => ShopFunctions::getCountryByID($order['details']['ST']->virtuemart_country_id, 'country_2_code'),
            'extra_3'          => $order['details']['BT']->virtuemart_order_id,
            'p_amount_'          => $p_amount_float,
            'p_tax'             => $iva,
            'p_amount_base'     => $baseDevolucionIva,
            'p_currency_code'   => $currency_payment,
            'p_url_status'    => $baseUrl. 'index.php?option=com_virtuemart&view=pluginresponse&task=pluginnotification&tmpl=component',
            'p_public_key'      => $payco_public_key,
            'p_private_key'      => $payco_private_key,
            'p_test_request'    => $test,
            'notification_url' => $baseUrl. 'index.php?option=com_virtuemart&view=vmplg&task=pluginNotification&tmpl=component&on=' . $order['details']['BT']->order_number . '&pm=' . $order['details']['BT']->virtuemart_paymentmethod_id . '&o_id=' . $order['details']['BT']->virtuemart_order_id,
            'lang' =>  $epayco_lang,
            'responseUrl' =>  $baseUrl . "index.php?option=com_virtuemart&amp;" .
                "view=pluginresponse&amp;task=pluginresponsereceived&amp;payment_method_id=" . $payMethod
        );

        $paymentData = array(
            'order_number' => $order['details']['BT']->order_number,
            'virtuemart_order_id' => $order['details']['BT']->virtuemart_order_id,
            'payment_name' => $this->renderPluginName($this->_currentMethod),
            'payment_order_total' => $order['details']['BT']->order_total,
            'payment_currency' => $currency_payment
        );
        //$this->storePSPluginInternalData($paymentData);


        if ($epayco_lang == "en") {
            $button = JURI::base() . 'plugins/vmpayment/payco/payco/images/Boton-color-Ingles.png';
            $msgEpaycoCheckout = '<span class="animated-points">Loading payment methods</span>
                <br><small class="epayco-subtitle"> If they do not load automatically, click on the "Pay with ePayco" button</small>';
        } else {
            $button = JURI::base() . 'plugins/vmpayment/payco/payco/images/Boton-color-espanol.png';
            $msgEpaycoCheckout = '<span class="animated-points">Cargando métodos de pago</span>
                <br><small class="epayco-subtitle"> Si no se cargan automáticamente, de clic en el botón "Pagar con ePayco</small>';
        }

        $js = '<style>
            .epayco-title{
                max-width: 900px;
                display: block;
                margin:auto;
                color: #444;
                font-weight: 700;
                margin-bottom: 25px;
            }
            .loader-container{
                position: relative;
                padding: 20px;
                color: #ff5700;
            }
            .epayco-subtitle{
                font-size: 14px;
            }
            .epayco-button-render{
                transition: all 500ms cubic-bezier(0.000, 0.445, 0.150, 1.025);
                transform: scale(1.1);
                box-shadow: 0 0 4px rgba(0,0,0,0);
            }
            .epayco-button-render:hover {
                transform: scale(1.2);
            }

            .animated-points::after{
                content: "";
                animation-duration: 2s;
                animation-fill-mode: forwards;
                animation-iteration-count: infinite;
                animation-name: animatedPoints;
                animation-timing-function: linear;
                position: absolute;
            }
            .animated-background {
                animation-duration: 2s;
                animation-fill-mode: forwards;
                animation-iteration-count: infinite;
                animation-name: placeHolderShimmer;
                animation-timing-function: linear;
                color: #f6f7f8;
                background: linear-gradient(to right, #7b7b7b 8%, #999 18%, #7b7b7b 33%);
                background-size: 800px 104px;
                position: relative;
                background-clip: text;
                -webkit-background-clip: text;
                -webkit-text-fill-color: transparent;
            }
            .loading::before{
                -webkit-background-clip: padding-box;
                background-clip: padding-box;
                box-sizing: border-box;
                border-width: 2px;
                border-color: currentColor currentColor currentColor transparent;
                position: absolute;
                margin: auto;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                content: " ";
                display: inline-block;
                background: center center no-repeat;
                background-size: cover;
                border-radius: 50%;
                border-style: solid;
                width: 30px;
                height: 30px;
                opacity: 1;
                -webkit-animation: loaderAnimation 1s infinite linear,fadeIn 0.5s ease-in-out;
                -moz-animation: loaderAnimation 1s infinite linear, fadeIn 0.5s ease-in-out;
                animation: loaderAnimation 1s infinite linear, fadeIn 0.5s ease-in-out;
            }
            @keyframes animatedPoints{
                33%{
                    content: "."
                }

                66%{
                    content: ".."
                }

                100%{
                    content: "..."
                }
            }

            @keyframes placeHolderShimmer{
                0%{
                    background-position: -800px 0
                }
                100%{
                    background-position: 800px 0
                }
            }
            @keyframes loaderAnimation{
                0%{
                    -webkit-transform:rotate(0);
                    transform:rotate(0);
                    animation-timing-function:cubic-bezier(.55,.055,.675,.19)
                }

                50%{
                    -webkit-transform:rotate(180deg);
                    transform:rotate(180deg);
                    animation-timing-function:cubic-bezier(.215,.61,.355,1)
                }
                100%{
                    -webkit-transform:rotate(360deg);
                    transform:rotate(360deg)
                }
            }

        </style>';

        $html = " 
        <div class=\"loader-container\">
                <div class=\"loading\"></div>
            </div>
            <p style=\"text-align: center;\" class=\"epayco-title\" >
            \"{$msgEpaycoCheckout}\"
            </p>
            <center>
                <a id=\"btn_epayco\" style=\"text-align: center;\" href=\"#\">
                    <img src=\"{$button}\">
                </a>
            </center>
        <form class=\"text-center\">
           <script src=\"https://checkout.epayco.co/checkout.js\"></script>
            <script>
                var handler = ePayco.checkout.configure({
                    key: \"{$post_variables['p_public_key']}\",
                    test: \"{$test}\"
                })
                var extras_epayco = {
                    extra5:\"P31\"
                }
                var date = new Date().getTime();
                var data = {
                    name: \"{$post_variables['p_product_name']}\",
                    description: \"{$post_variables['p_description']}\",
                    invoice: \"{$post_variables['p_id_factura']}\",
                    currency: \"{$post_variables['p_currency_code']}\",
                    amount: \"{$post_variables['p_amount_']}\".toString(),
                    tax_base: \"{$post_variables['p_amount_base']}\".toString(),
                    tax: \"{$post_variables['p_tax']}\".toString(),
                    taxIco: \"0\",
                    country: \"{$post_variables['p_country_code']}\",
                    lang: \"{$post_variables['lang']}\",
                    external: \"{$post_variables['external']}\",
                    confirmation: \"{$post_variables['notification_url']}\",
                    response: \"{$post_variables['responseUrl']}\",
                    address_billing: \"{$post_variables['p_billing_adress']}\",
                    email_billing: \"{$post_variables['p_billing_email']}\",
                    extra1: \"{$post_variables['p_id_factura']}\",
                    extra2: \"{$order['details']['BT']->order_pass}\",
                    extra3: \"{$order['details']['BT']->virtuemart_order_id}\",
                    extra4: \"{$payMethod}\",
                    autoclick: \"{$autoclick}\",
                    ip: \"{$ip}\",
                    test: \"{$test}\".toString(),
                    extras_epayco: extras_epayco
                }
                const apiKey = \"{$post_variables['p_public_key']}\";
                const privateKey = \"{$post_variables['p_private_key']}\";
                var openChekout = function () {
                        if(localStorage.getItem(\"invoicePayment\") == null){
                        localStorage.setItem(\"invoicePayment\", data.invoice);
                            makePayment(privateKey,apiKey,data, data.external == \"true\"?true:false)
                        }else{
                            if(localStorage.getItem(\"invoicePayment\") != data.invoice){
                                localStorage.removeItem(\"invoicePayment\");
                                localStorage.setItem(\"invoicePayment\", data.invoice);
                                makePayment(privateKey,apiKey,data, data.external == \"true\"?true:false)
                            }else{
                                makePayment(privateKey,apiKey,data, data.external == \"true\"?true:false)
                            }
                        }
                }
                var makePayment = function (privatekey, apikey, info, external) {
                    const headers = { \"Content-Type\": \"application/json\" } ;
                    headers['privatekey'] = privatekey;
                    headers['apikey'] = apikey;
                    var payment =   function (){
                        return  fetch(\"https://cms.epayco.co/checkout/payment/session\", {
                            method: 'POST',
                            body: JSON.stringify(info),
                            headers
                        })
                            .then(res =>  res.json())
                            .catch(err => err);
                    }
                    payment()
                        .then(session => {
                            if(session.data.sessionId != undefined){
                                localStorage.removeItem(\"sessionPayment\");
                                localStorage.setItem(\"sessionPayment\", session.data.sessionId);
                                const handlerNew = window.ePayco.checkout.configure({
                                    sessionId: session.data.sessionId,
                                    external: external,
                                });
                                handlerNew.openNew()
                            }else{
                                handler.open(data)
                            }
                        })
                        .catch(error => {
                            error.message;
                        });
                }
                var bntPagar = document.getElementById(\"btn_epayco\");
                bntPagar.addEventListener(\"click\", openChekout);
                openChekout()
            </script>
            <script src=\"https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js\"></script>
            <script>
                window.onload = function() {
                    document.addEventListener(\"contextmenu\", function(e){
                        e.preventDefault();
                    }, false);
                } 
                $(document).keydown(function (event) {
                    if (event.keyCode == 123) {
                        return false;
                    } else if (event.ctrlKey && event.shiftKey && event.keyCode == 73) {        
                        return false;
                    }
                });
            </script>
        </form>";
        $this->restorStock($post_variables['p_id_factura']);
        $cart = VirtueMartCart::getCart();
        $cart->emptyCart();
        vRequest::setVar('html', $js . $html);
    }

    private function getIp()
    {
        $ipaddress = '';
        if (isset($_SERVER['HTTP_CLIENT_IP']))
            $ipaddress = $_SERVER['HTTP_CLIENT_IP'];
        else if (isset($_SERVER['HTTP_X_FORWARDED_FOR']))
            $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
        else if (isset($_SERVER['HTTP_X_FORWARDED']))
            $ipaddress = $_SERVER['HTTP_X_FORWARDED'];
        else if (isset($_SERVER['HTTP_X_CLUSTER_CLIENT_IP']))
            $ipaddress = $_SERVER['HTTP_X_CLUSTER_CLIENT_IP'];
        else if (isset($_SERVER['HTTP_FORWARDED_FOR']))
            $ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
        else if (isset($_SERVER['HTTP_FORWARDED']))
            $ipaddress = $_SERVER['HTTP_FORWARDED'];
        else if (isset($_SERVER['REMOTE_ADDR']))
            $ipaddress = $_SERVER['REMOTE_ADDR'];
        else
            $ipaddress = 'UNKNOWN';
        return $ipaddress;
    }

    public function restorStock($refVenta)
    {
        $db = JFactory::getDBO();
        $pf = $db->getPrefix();
        $q = 'SELECT * FROM `' . $pf . 'virtuemart_orders' . '` WHERE ';
        $q .= ' `order_number` = "' . (string)$refVenta . '"';
        $db->setQuery($q);
        $sql_order = $db->loadObjectList();
        if ($sql_order) {
            $orderProduct_query = "SELECT * FROM " . $pf . "virtuemart_order_items WHERE virtuemart_order_id = '" . (int)$sql_order[0]->virtuemart_order_id . "'";
            $db->setQuery($orderProduct_query);
            $orderProductQuery = $db->loadObjectList();
            $productsData = [];
            if ($orderProductQuery) {
                foreach ($orderProductQuery as $product_item) {
                    $product_query = "SELECT * FROM " . $pf . "virtuemart_products WHERE virtuemart_product_id = '" . (int)$product_item->virtuemart_product_id . "'";
                    $db->setQuery($product_query);
                    $productQuery = $db->loadObjectList();
                    if ($productQuery) {
                        foreach ($productQuery as $product_data) {
                            $stockToUpdate = ((int)$product_data->product_in_stock - (int)$product_item->product_quantity);
                            $products['id'] = $product_data->virtuemart_product_id;
                            $products['quantity'] = $stockToUpdate;
                            $productsData[] = $products;
                        }
                    }
                }
            }
        }
        foreach ($productsData as $miProduct) {
            $sqlProduct_ = "UPDATE " . $pf . "virtuemart_products SET product_in_stock ='" . $miProduct['quantity'] . "'
              WHERE virtuemart_product_id = '" . (int)$miProduct['id'] . "'";
            $db->setQuery($sqlProduct_)->execute();
        }
    }

    private function setRetourParams($order, $context)
    {
        $params = $order['details']['BT']->virtuemart_paymentmethod_id . ':' . $order['details']['BT']->order_number . ':' . $context;
        if (!class_exists('vmCrypt')) {
            require(VMPATH_ADMIN . DS . 'helpers' . DS . 'vmcrypt.php');
        }
        $cryptedParams = vmCrypt::encrypt($params);
        $cryptedParams = base64_encode($cryptedParams);
        return $cryptedParams;
    }

    private function getContext()
    {
        $session = JFactory::getSession();
        return $session->getId();
    }

}
