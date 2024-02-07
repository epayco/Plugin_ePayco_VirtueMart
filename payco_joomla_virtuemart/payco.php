<?php

defined('_JEXEC') or die('Direct Access to ' . basename(__FILE__) . 'is not allowed.');

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
 *
 * 
 * Account set up >Set up > URL transaction accepted
 * http://mywebsite.com/index.php?option=com_virtuemart&view=vmplg&task=pluginresponsereceived&po=
 *
 * Account set up >Set up > URL refused/cancelled transaction
 * http://mywebsite.com/index.php?option=com_virtuemart&view=vmplg&task=pluginUserPaymentCancel&po=
 *
 *  * Account set up > Dynamic Set up > Dynamic return URL
 * http://mywebsite.com/index.php?option=com_virtuemart&view=vmplg&task=notify&tmpl=component&po=
 */

if (!class_exists('vmPSPlugin')) {
    require(VMPATH_PLUGINLIBS . DS . 'vmpsplugin.php');
}

class plgVmPaymentPayco extends vmPSPlugin {

    function __construct(& $subject, $config) {

        parent::__construct($subject, $config);
        $this->_loggable = TRUE;
        $this->tableFields = array_keys($this->getTableSQLFields());
        $this->_tablepkey = 'id'; //virtuemart_kap_id';
        $this->_tableId = 'id'; //'virtuemart_kap_id';
        $varsToPush = $this->getVarsToPush();
        $this->setConfigParameterable($this->_configTableFieldName, $varsToPush);
        if (method_exists($this, 'setCryptedFields')) {
            $this->setCryptedFields(array('account'));
        }

    }

    protected function getVmPluginCreateTableSQL() {
        $query = "CREATE TABLE IF NOT EXISTS `epaycos` (";
        $query .= "PRIMARY KEY (`id`),
        `order_status_name` varchar(64),
        `order_status_code`	char(1)
        ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COMMENT='epaycos table' AUTO_INCREMENT=1 ;";
		
        $db = JFactory::getDBO();
        $db->setQuery($query);

        $creaTabSql = 'CREATE TABLE IF NOT EXISTS ' . $db->quoteName('#__utf8_conversion')
        . ' (' . $db->quoteName('paycosss') . ' tinyint(4) NOT NULL DEFAULT 0'
        . ') ENGINE=InnoDB';

        if ($db->hasUTF8mb4Support())
        {
            $creaTabSql = $creaTabSql
                . ' DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;';
        }
        else
        {
            $creaTabSql = $creaTabSql
                . ' DEFAULT CHARSET=utf8 DEFAULT COLLATE=utf8_unicode_ci;';
        }

    $db->setQuery($creaTabSql)->execute();
        return $this->createTableSQL('Payment Payco Table');
    }

    function getTableSQLFields() {

        $query = "CREATE TABLE IF NOT EXISTS `epayco` (";
        $query .= "PRIMARY KEY (`id`),
        `order_status_name` varchar(64),
        `order_status_code`	char(1)
        ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COMMENT='epayco' AUTO_INCREMENT=1 ;";

        $db = JFactory::getDBO();
        $db->setQuery($query);

        $creaTabSql = 'CREATE TABLE IF NOT EXISTS ' . $db->quoteName('#__utf8_conversion')
        . ' (' . $db->quoteName('paycos') . ' tinyint(4) NOT NULL DEFAULT 0'
        . ') ENGINE=InnoDB';

        if ($db->hasUTF8mb4Support())
        {
            $creaTabSql = $creaTabSql
                . ' DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;';
        }
        else
        {
            $creaTabSql = $creaTabSql
                . ' DEFAULT CHARSET=utf8 DEFAULT COLLATE=utf8_unicode_ci;';
        }

    $db->setQuery($creaTabSql)->execute();
        $SQLfields = array(
            'id'                                     => 'int(11) UNSIGNED NOT NULL AUTO_INCREMENT',
            'virtuemart_order_id'                    => 'int(1) UNSIGNED',
            'order_number'                           => 'char(64)',
            'virtuemart_paymentmethod_id'            => 'mediumint(1) UNSIGNED',
            'payment_name'                           => 'varchar(5000)',
            'payment_order_total'                    => 'decimal(15,5) NOT NULL',
            'payment_currency'                       => 'smallint(1)',
            'email_currency'                         => 'smallint(1)',
            'cost_per_transaction'                   => 'decimal(10,2)',
            'cost_percent_total'                     => 'decimal(10,2)',
            'tax_id'                                 => 'smallint(1)');
        return $SQLfields;
    }

    function plgVmConfirmedOrder($cart, $order) {

        if (!($this->_currentMethod = $this->getVmPluginMethod($order['details']['BT']->virtuemart_paymentmethod_id))) {
            return NULL; // Another method was selected, do nothing
        }

        if (!$this->selectedThisElement($this->_currentMethod->payment_element)) {
            return FALSE;
        }

        $this->getPaymentCurrency($this->_currentMethod);
        $this->_p_test_request =$this->_currentMethod->p_test_request;
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

        foreach ($cart->products as $key => $product) {
            $quantity = $quantity + $product->quantity;
            $nameproduct = $product->product_name;
        }

        if (count($cart->products) > 1) {
            $nameproduct .= ', etc';
        }

        $p_amount_float = round(floatval($order['details']['BT']->order_total),2);
        $iva =round(floatval($order['details']['BT']->order_billTaxAmount),2);
        $baseDevolucionIva = $p_amount_float - $iva;
        if ($this->_currentMethod->p_test_request=="TRUE") {
            $test= "true";
        }else{
            $test= "false";
        }

        if ($this->_currentMethod->p_external_request=="TRUE") {
            $external= "false";
        }else{
            $external= "true";
        }
        $autoclick="true";
        $ip=$this->getIp();
        $orderstatusurl = (JROUTE::_(JURI::root() ."index.php?option=com_virtuemart&view=orders&layout=details&order_number=".$order['details']['BT']->order_number."&order_pass=".$order['details']['BT']->order_pass, true) . '&');
        $currency_model = VmModel::getModel('currency');
        $currency_payment=$currency_model->getCurrency()->currency_code_3;
        $retourParams = $this->setRetourParams($order, $this->getContext());
        $post_variables = Array(
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
            'external'=>$external,
            'p_description'     => 'ORDEN DE COMPRA # '.$order['details']['BT']->order_number,
            'p_cust_id_cliente' => $this->_currentMethod->payco_user_id,
            'p_product_name' => $nameproduct,
            'p_id_factura' => $order['details']['BT']->order_number,
            'p_country_code'	=> ShopFunctions::getCountryByID ($order['details']['ST']->virtuemart_country_id, 'country_2_code'),
            'extra_3'          => $order['details']['BT']->virtuemart_order_id,
            'p_amount_'          => $p_amount_float,
            'p_tax'             => $iva,
            'p_amount_base' 	=> $baseDevolucionIva,
            'p_currency_code'   => $currency_payment,
            'p_url_status'	=> 	(JROUTE::_ (JURI::root () . 'index.php?option=com_virtuemart&view=pluginresponse&task=pluginnotification&tmpl=component')),
            'p_public_key'      => $this->_currentMethod->payco_public_key,
            'p_private_key'      => $this->_currentMethod->payco_private_key,
            'p_test_request'    => $test,
            'notification_url' => (JROUTE::_ (JURI::root () .'index.php?option=com_virtuemart&view=vmplg&task=pluginNotification&tmpl=component&on=' . $order['details']['BT']->order_number . '&pm=' . $order['details']['BT']->virtuemart_paymentmethod_id . '&o_id='.$order['details']['BT']->virtuemart_order_id)),
            'p_confirmation_url' =>  JURI::base() .'plugins/vmpayment/payco/payco/' .'confirmacion.php',
            'p_url_respuesta' =>  JURI::base() .'plugins/vmpayment/payco/payco/' .'response.php',
            'lang' =>  $this->_currentMethod->epayco_lang
        );
            if($this->_currentMethod->epayco_lang == "en"){
                $button = 'plugins/vmpayment/payco/payco/images/Boton-color-Ingles.png';
                $msgEpaycoCheckout = '<span class="animated-points">Loading payment methods</span>
                <br><small class="epayco-subtitle"> If they do not load automatically, click on the "Pay with ePayco" button</small>';
            }else{
                $button = 'plugins/vmpayment/payco/payco/images/Boton-color-espanol.png';
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
            ".$msgEpaycoCheckout."
            </p>
            <center>
                <a id=\"btn_epayco\" style=\"text-align: center;\" href=\"#\">
                    <img src=\".$button.\">
                </a>
            </center>
        <form class=\"text-center\">
            <script src=\"https://checkout.epayco.co/checkout.js\"></script>
            <script>
                var handler = ePayco.checkout.configure({
                    key: \"{$post_variables['p_public_key']}\",
                    test: \"{$test}\"
                })
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
                    confirmation: \"{$post_variables['p_confirmation_url']}\",
                    response: \"{$post_variables['p_url_respuesta']}\",
                    address_billing: \"{$post_variables['p_billing_adress']}\",
                    email_billing: \"{$post_variables['p_billing_email']}\",
                    extra1: \"{$post_variables['p_id_factura']}\",
                    extra2: \"{$order['details']['BT']->order_pass}\",
                    extra3: \"{$order['details']['BT']->virtuemart_order_id}\",
                    autoclick: \"{$autoclick}\",
                    ip: \"{$ip}\",
                    test: \"{$test}\".toString()
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
        vRequest::setVar ('html', $js.$html);
    }


    function plgVmgetPaymentCurrency($virtuemart_paymentmethod_id, &$paymentCurrencyId) {
        if (!($method = $this->getVmPluginMethod($virtuemart_paymentmethod_id))) {
            return NULL; // Another method was selected, do nothing
        }
        if (!$this->selectedThisElement($method->payment_element)) {
            return FALSE;
        }
        $this->getPaymentCurrency($method);
        $paymentCurrencyId = $method->payment_currency;
        return TRUE;
    }


    function redirectToCart() {
        $app = JFactory::getApplication();
        $app->redirect(JRoute::_('index.php?option=com_virtuemart&view=cart&lg=&Itemid=' . vRequest::getInt('Itemid'), false), vmText::_('VMPAYMENT_KLIKANDPAY_ERROR_TRY_AGAIN'));
    }

    function plgVmOnUserPaymentCancel() {
        if (!class_exists('VirtueMartModelOrders')) {
            require(VMPATH_ADMIN . DS . 'models' . DS . 'orders.php');
        }
        $order_number = vRequest::getUword('on');
        if (!$order_number) {
            return FALSE;
        }

        if (!$virtuemart_order_id = VirtueMartModelOrders::getOrderIdByOrderNumber($order_number)) {
            return NULL;
        }

        if (!($paymentTable = $this->getDataByOrderId($virtuemart_order_id))) {
            return NULL;
        }

        $session = JFactory::getSession();
        $return_context = $session->getId();
        $field = $this->_name . '_custom';
        if (strcmp($paymentTable->$field, $return_context) === 0) {
            $this->handlePaymentUserCancel($virtuemart_order_id);
        }
        return TRUE;
    }


    /**
     * plgVmOnPaymentNotification() -It can be used to validate the payment data as entered by the user.
     * Return:
     * Parameters:
     * None
     * @author Valerie Isaksen
     */
    function plgVmOnPaymentNotification() {

        if (!class_exists('VirtueMartModelOrders')) {
            require(VMPATH_ADMIN . DS . 'models' . DS . 'orders.php');
        }

        $po = vRequest::getString('po', '');
        if (!$po) {
            return;
        }

            $mb_data = vRequest::getRequest();
        if (!isset($mb_data['x_id_invoice'])) {
            return;
        }

        $order_number2 = $mb_data['x_id_invoice'];
        $retourParams = $this->getRetourParams($po);
        $virtuemart_paymentmethod_id = $retourParams['virtuemart_paymentmethod_id'];
        $order_number = $retourParams['order_number'];
        $context = $retourParams['context'];
        $this->_currentMethod = $this->getVmPluginMethod($virtuemart_paymentmethod_id);
        if (!$this->selectedThisElement($this->_currentMethod->payment_element)) {
            return;
        }
        $this->debugLog(var_export($retourParams, true), 'plgVmOnPaymentNotification getRetourParams', 'debug', false);

        if (!($virtuemart_order_id = VirtueMartModelOrders::getOrderIdByOrderNumber($order_number))) {
            return FALSE;
        }

        if (!($payments = $this->getDatasByOrderId($virtuemart_order_id))) {
            $this->debugLog('no payments found', 'getDatasByOrderId', 'debug', false);
            return FALSE;
        }

        $orderModel = VmModel::getModel('orders');
        $order = $orderModel->getOrder($virtuemart_order_id);
        return TRUE;
    }
    public function restorStock($refVenta){
        $db = JFactory::getDBO();
        $pf = $db->getPrefix();
        $q = 'SELECT * FROM `' . $pf.'virtuemart_orders' . '` WHERE ';
        $q .= ' `order_number` = "' . (string)$refVenta.'"';
        $db->setQuery($q);
        $sql_order = $db->loadObjectList();
        if($sql_order){
            $orderProduct_query = "SELECT * FROM ".$pf."virtuemart_order_items WHERE virtuemart_order_id = '".(int)$sql_order[0]->virtuemart_order_id."'";
            $db->setQuery($orderProduct_query);
            $orderProductQuery = $db->loadObjectList();
            $productsData = [];
            if($orderProductQuery){
                foreach ($orderProductQuery as $product_item) {
                    $product_query = "SELECT * FROM ".$pf."virtuemart_products WHERE virtuemart_product_id = '".(int)$product_item->virtuemart_product_id."'";
                    $db->setQuery($product_query);
                    $productQuery = $db->loadObjectList();
                    if($productQuery){
                        foreach ($productQuery as $product_data) {
                            $stockToUpdate = ((int)$product_data->product_in_stock-(int)$product_item->product_quantity);
                            $products['id']=$product_data->virtuemart_product_id;
                            $products['quantity']=$stockToUpdate;
                            $productsData[] = $products;
                        }
                    }
                }
            }
        }
        foreach ($productsData as $miProduct){
            $sqlProduct_ = "UPDATE ".$pf."virtuemart_products SET product_in_stock ='".$miProduct['quantity']."'
              WHERE virtuemart_product_id = '".(int)$miProduct['id']."'";
            $db->setQuery($sqlProduct_)->execute();
        }
    }
    /**
     * Display stored payment data for an order
     *
     * @see components/com_virtuemart/helpers/vmPSPlugin::plgVmOnShowOrderBEPayment()
     */
    function plgVmOnShowOrderBEPayment($virtuemart_order_id, $payment_method_id) {
        if (!$this->selectedThisByMethodId($payment_method_id)) {
            return NULL; // Another method was selected, do nothing
        }

        $db = JFactory::getDBO();
        $q = 'SELECT * FROM `' . $this->_tablename . '` WHERE ';
        $q .= ' `virtuemart_order_id` = ' . $virtuemart_order_id;
        $db->setQuery($q);
        $payments = $db->loadObjectList();
        $html = '<table class="adminlist" >' . "\n";
        $html .= $this->getHtmlHeaderBE();
        $first = TRUE;
        $lang = JFactory::getLanguage();

        foreach ($payments as $payment) {

            $html .= '<tr class="row1"><td>' . vmText::_('VMPAYMENT_PAYCO_DATE') . '</td><td align="left">' . $payment->created_on . '</td></tr>';
            // Now only the first entry has this data when creating the order
            if ($first) {
                $html .= $this->getHtmlRowBE('PAYCO_PAYMENT_NAME', $payment->payment_name);
                // keep that test to have it backwards compatible. Old version was deleting that column  when receiving an IPN notification
                if ($payment->payment_order_total and  $payment->payment_order_total != 0.00) {
                    $html .= $this->getHtmlRowBE('PAYCO_PAYMENT_ORDER_TOTAL', ($payment->payment_order_total) . " " . shopFunctions::getCurrencyByID($payment->payment_currency, 'currency_code_3'));
                }
                if ($payment->email_currency and  $payment->email_currency != 0) {
                    $html .= $this->getHtmlRowBE('PAYCO_PAYMENT_EMAIL_CURRENCY', shopFunctions::getCurrencyByID($payment->email_currency, 'currency_code_3'));
                }
                $first = FALSE;
            } 
        }
        $html .= '</table>' . "\n";
        return $html;
    }

    private function rmspace($buffer) {
        return preg_replace('~>\s*\n\s*<~', '><', $buffer);
    }



    /**
     * Check if the payment conditions are fulfilled for this payment method
     *
     * @author: Valerie Isaksen
     *
     * @param $cart_prices: cart prices
     * @param $payment
     * @return true: if the conditions are fulfilled, false otherwise
     *
     */
    protected function checkConditions($cart, $method, $cart_prices) {

        $this->_currentMethod = $method;
        $this->convert_condition_amount($method);
        $address = $cart->getST();
        $amount = $this->getCartAmount($cart_prices);
        $amount_cond = ($amount >= $this->_currentMethod->min_amount AND $amount <= $this->_currentMethod->max_amount
            OR
            ($this->_currentMethod->min_amount <= $amount AND ($this->_currentMethod->max_amount == 0)));
        $countries = array();
        if (!empty($method->countries)) {
            if (!is_array($method->countries)) {
                $countries[0] = $method->countries;
            } else {
                $countries = $method->countries;
            }
        }

        // probably did not gave his BT:ST address

        if (!is_array($address)) {
            $address = array();
            $address['virtuemart_country_id'] = 0;
        }

        if (!isset($address['virtuemart_country_id'])) {
            $address['virtuemart_country_id'] = 0;
        }

        if (in_array($address['virtuemart_country_id'], $countries) || count($countries) == 0) {
            if ($amount_cond) {
                return TRUE;
            }
        }

        $this->debugLog(' FALSE', 'checkConditions', 'debug');
        return FALSE;
    }


    function convert_condition_amount(&$method) {
        $method->min_amount = (float)str_replace(',', '.', $method->min_amount);
        $method->max_amount = (float)str_replace(',', '.', $method->max_amount);
    }


    /**
     * We must reimplement this triggers for joomla 1.7
     */
    /**
     * Create the table for this plugin if it does not yet exist.
     * This functions checks if the called plugin is active one.
     * When yes it is calling the standard method to create the tables
     *
     * @author Valérie Isaksen
     *
     */
    function plgVmOnStoreInstallPaymentPluginTable($jplugin_id) {
        return $this->onStoreInstallPluginTable($jplugin_id);
    }

    /**
     * This event is fired after the payment method has been selected. It can be used to store
     * additional payment info in the cart.
     *
     * @author Max Milbers
     * @author Valérie isaksen
     *
     * @param VirtueMartCart $cart: the actual cart
     * @return null if the payment was not selected, true if the data is valid, error message if the data is not vlaid
     *
     */
    public function plgVmOnSelectCheckPayment(VirtueMartCart $cart, &$msg) {
        if (!($this->_currentMethod = $this->getVmPluginMethod($cart->virtuemart_paymentmethod_id))) {
            return NULL; // Another method was selected, do nothing
        }
        if (!$this->selectedThisElement($this->_currentMethod->payment_element)) {
            return NULL;
        }
        return $this->onSelectCheck($cart);
    }


    /**
     * plgVmDisplayListFEPayment
     * This event is fired to display the pluginmethods in the cart (edit shipment/payment) for exampel
     *
     * @param object $cart Cart object
     * @param integer $selected ID of the method selected
     * @return boolean True on succes, false on failures, null when this plugin was not selected.
     * On errors, JError::raiseWarning (or JError::raiseError) must be used to set a message.
     *
     * @author Valerie Isaksen
     */
    public function plgVmDisplayListFEPayment(VirtueMartCart $cart, $selected = 0, &$htmlIn) {
        if ($this->getPluginMethods($cart->vendorId) === 0) {
            if (empty($this->_name)) {
                $app = JFactory::getApplication();
                $app->enqueueMessage(vmText::_('COM_VIRTUEMART_CART_NO_' . strtoupper($this->_psType)));
                return FALSE;
            } else {
                return FALSE;
            }
        }
        $method_name = $this->_psType . '_name';
        $idN = 'virtuemart_'.$this->_psType.'method_id';
        $htmla = array();
        $html = '';
        foreach ($this->methods as $this->_currentMethod) {
            if ($this->checkConditions($cart, $this->_currentMethod, $cart->cartPrices)) {
                $cartPrices=$cart->cartPrices;
                $methodSalesPrice = $this->setCartPrices($cart, $cartPrices, $this->_currentMethod);
                $this->_currentMethod->$method_name = $this->renderPluginName($this->_currentMethod);
                $html = $this->getPluginHtml($this->_currentMethod, $selected, $methodSalesPrice);  
                $html .= '<span class="vmpayment_description">'.$this->_currentMethod->payment_desc.'</span>';
                $html .= '<br>
                <span class="vmpayment_cardinfo">
                    <img src="https://multimedia.epayco.co/epayco-landing/btns/epayco-logo-fondo-oscuro-lite.png"  width="200px" style="padding-left: 26px;">
                </span>
                ';  $htmla[] = $html;
            }
        }
        $htmlIn[] = $htmla;
        return TRUE;
    }


    public function plgVmonSelectedCalculatePricePayment(VirtueMartCart $cart, array &$cart_prices, &$cart_prices_name) {
        return $this->onSelectedCalculatePrice($cart, $cart_prices, $cart_prices_name);
    }


    /**
     * plgVmOnCheckAutomaticSelectedPayment
     * Checks how many plugins are available. If only one, the user will not have the choice. Enter edit_xxx page
     * The plugin must check first if it is the correct type
     *
     * @author Valerie Isaksen
     * @param VirtueMartCart cart: the cart object
     * @return null if no plugin was found, 0 if more then one plugin was found,  virtuemart_xxx_id if only one plugin is found
     *
     */
    function plgVmOnCheckAutomaticSelectedPayment(VirtueMartCart $cart, array $cart_prices = array()) {
        return $this->onCheckAutomaticSelected($cart, $cart_prices);
    }


    /**
     * This method is fired when showing the order details in the frontend.
     * It displays the method-specific data.
     *
     * @param integer $order_id The order ID
     * @return mixed Null for methods that aren't active, text (HTML) otherwise
     * @author Max Milbers
     * @author Valerie Isaksen
     */
    public function plgVmOnShowOrderFEPayment($virtuemart_order_id, $virtuemart_paymentmethod_id, &$payment_name) {
        $this->onShowOrderFE($virtuemart_order_id, $virtuemart_paymentmethod_id, $payment_name);
    }


    /**
     * This method is fired when showing when priting an Order
     * It displays the the payment method-specific data.
     *
     * @param integer $_virtuemart_order_id The order ID
     * @param integer $method_id  method used for this order
     * @return mixed Null when for payment methods that were not selected, text (HTML) otherwise
     * @author Valerie Isaksen
     */
    function plgVmonShowOrderPrintPayment($order_number, $method_id) {
        return $this->onShowOrderPrint($order_number, $method_id);
    }

    function plgVmDeclarePluginParamsPaymentVM3( &$data) {
        return $this->declarePluginParams('payment', $data);
    }

    function plgVmSetOnTablePluginParamsPayment($name, $id, &$table) {
        return $this->setOnTablePluginParams($name, $id, $table);
    }

    private function setRetourParams($order, $context) {
        $params = $order['details']['BT']->virtuemart_paymentmethod_id . ':' . $order['details']['BT']->order_number . ':' . $context;
        if (!class_exists('vmCrypt')) {
            require(VMPATH_ADMIN . DS . 'helpers' . DS . 'vmcrypt.php');
        }
        $cryptedParams = vmCrypt::encrypt($params);
        $cryptedParams = base64_encode($cryptedParams);
        return $cryptedParams;
    }

    private function getIp(){
        $ipaddress = '';
        if (isset($_SERVER['HTTP_CLIENT_IP']))
            $ipaddress = $_SERVER['HTTP_CLIENT_IP'];
        else if(isset($_SERVER['HTTP_X_FORWARDED_FOR']))
            $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
        else if(isset($_SERVER['HTTP_X_FORWARDED']))
            $ipaddress = $_SERVER['HTTP_X_FORWARDED'];
        else if(isset($_SERVER['HTTP_X_CLUSTER_CLIENT_IP']))
            $ipaddress = $_SERVER['HTTP_X_CLUSTER_CLIENT_IP'];
        else if(isset($_SERVER['HTTP_FORWARDED_FOR']))
            $ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
        else if(isset($_SERVER['HTTP_FORWARDED']))
            $ipaddress = $_SERVER['HTTP_FORWARDED'];
        else if(isset($_SERVER['REMOTE_ADDR']))
            $ipaddress = $_SERVER['REMOTE_ADDR'];
        else
            $ipaddress = 'UNKNOWN';
        return $ipaddress;
    }

    private function getRetourParams($cryptedParams) {
        if (!class_exists('vmCrypt')) {
            require(VMPATH_ADMIN . DS . 'helpers' . DS . 'vmcrypt.php');
        }
        $cryptedParams = base64_decode($cryptedParams);
        $params = vmCrypt::decrypt($cryptedParams);
        $paramsArray = explode(":", $params);
        $retourParams['virtuemart_paymentmethod_id'] = $paramsArray[0];
        $retourParams['order_number'] = $paramsArray[1];
        $retourParams['context'] = $paramsArray[2];
        return $retourParams;
    }


    private function getContext() {
        $session = JFactory::getSession();
        return $session->getId();
    }


    private function isValidContext($context) {
        if ($this->getContext() == $context) {
            return true;
        }
        return false;
    }


    function getOrderBEFields() {
        $fields = array('RESPONSE', 'NUMXKP', 'SCOREXKP', 'TRANSACTIONID', 'AUTHID');
        return $fields;
    }

    /**
     * @param string $message
     * @param string $title
     * @param string $type
     * @param bool $echo
     * @param bool $doVmDebug
     */
    public function debugLog($message, $title = '', $type = 'message', $echo = false, $doVmDebug = false) {
        if ($this->_currentMethod->debug) {
            $this->debug($message, $title, true);
        }

        if ($echo) {
            echo $message . '<br/>';
        }
        parent::debugLog($message, $title, $type, $doVmDebug);
    }

    public function debug($subject, $title = '', $echo = true) {
        $debug = '<div style="display:block; margin-bottom:5px; border:1px solid red; padding:5px; text-align:left; font-size:10px;white-space:nowrap; overflow:scroll;">';
        $debug .= ($title) ? '<br /><strong>' . $title . ':</strong><br />' : '';
        if (is_array($subject)) {
            $debug .= str_replace("=>", "&#8658;", str_replace("Array", "<font color=\"red\"><b>Array</b></font>", nl2br(str_replace(" ", " &nbsp; ", print_r($subject, true)))));
        } else {
            $debug .= str_replace("=>", "&#8658;", str_replace("Array", "<font color=\"red\"><b>Array</b></font>", print_r($subject, true)));
        }
        $debug .= '</div>';
        if ($echo) {
            echo $debug;
        } else {
            return $debug;
        }
    }
}
