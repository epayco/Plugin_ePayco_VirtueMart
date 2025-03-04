<?php

defined('_JEXEC') or die('Direct Access to ' . basename(__FILE__) . 'is not allowed.');

/**
 * @version $Id$
 * @package    VirtueMart
 * @subpackage Plugins  - Payco
 * @package VirtueMart
 * @subpackage Payment
 * @author Developers ePayco <ricardo.saldarriaga@epayco.com>
 * @link https://virtuemart.net
 * @copyright Copyright (c) 2004 - 2018 VirtueMart Team. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * VirtueMart is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 * @version $Id$
 */

if (!class_exists('vmPSPlugin')) {
    //require(VMPATH_PLUGINLIBS . DS . 'vmpsplugin.php');
    require(JPATH_VM_PLUGINS . DS . 'vmpsplugin.php');
}

if (!class_exists('EpaycoPaymentPlugin')) {
    require_once(dirname(__FILE__) . '/payco/lib/abstract_payment.php');
    require_once(dirname(__FILE__) . '/payco/lib/EpaycoPaymentPlugin.php');
    require_once(dirname(__FILE__) . '/payco/lib/TicketPaymentPlugin.php');
}


class plgVmPaymentPayco extends vmPSPlugin
{


    private $epayco;
    private $ticket;
    protected $_subject;

    protected $_config;


    function __construct(&$subject, $config)
    {

        parent::__construct($subject, $config);
        $this->_subject = $subject;
        $this->_config = $config;
        $this->epayco = new EpaycoPaymentPlugin($subject, $config);
        //$this->ticket = new TicketPaymentPlugin($subject, $config);


       // $this->_logInfo('Payco plugin cargado.');

        // Load the language file
        $lang = JFactory::getLanguage();
        $lang->load('plg_vmpayment_payco', JPATH_ADMINISTRATOR);

        // Define the plugin parameters
        $this->_loggable = TRUE;
        $this->tableFields = array_keys($this->getTableSQLFields());
        //$this->tableFields = array('id', 'payment_method_id', 'opciones_pago');
        $this->_tablepkey = 'id'; //virtuemart_kap_id';
        $this->_tableId = 'id'; //'virtuemart_kap_id';
        $this->config_ = $config;
        // Set the configuration
        $varsToPush = $this->getVarsToPush();
        $this->setConfigParameterable($this->_configTableFieldName, $varsToPush);
        //$this->setConfigParameterable($this->_configTableFieldName, $this->tableFields);
        if (method_exists($this, 'setCryptedFields')) {
            $this->setCryptedFields(array('account'));
        }
    }


    private function _logInfo($message) {
        error_log($message, 3, dirname(__FILE__) . '/payco/logs/payco.log');
    }

    protected function getVmPluginCreateTableSQL()
    {
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

        if ($db->hasUTF8mb4Support()) {
            $creaTabSql = $creaTabSql
                . ' DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;';
        } else {
            $creaTabSql = $creaTabSql
                . ' DEFAULT CHARSET=utf8 DEFAULT COLLATE=utf8_unicode_ci;';
        }

        $db->setQuery($creaTabSql)->execute();
        return $this->createTableSQL('Payment Payco Table');
    }

    function getTableSQLFields()
    {

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

        if ($db->hasUTF8mb4Support()) {
            $creaTabSql = $creaTabSql
                . ' DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;';
        } else {
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
            'tax_id'                                 => 'smallint(1)'
        );
        return $SQLfields;
    }

    // Event called when selecting the payment method
    function plgVmConfirmedOrder($cart, $order)
    {
    }


    function plgVmgetPaymentCurrency($virtuemart_paymentmethod_id, &$paymentCurrencyId)
    {
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


    function redirectToCart()
    {
        $app = JFactory::getApplication();
        $app->redirect(JRoute::_('index.php?option=com_virtuemart&view=cart&lg=&Itemid=' . vRequest::getInt('Itemid'), false), vmText::_('VMPAYMENT_KLIKANDPAY_ERROR_TRY_AGAIN'));
    }

    function plgVmOnUserPaymentCancel()
    {
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
     * Display stored payment data for an order
     *
     * @see components/com_virtuemart/helpers/vmPSPlugin::plgVmOnShowOrderBEPayment()
     */
    function plgVmOnShowOrderBEPayment($virtuemart_order_id, $payment_method_id)
    {
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

    private function rmspace($buffer)
    {
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
    protected function checkConditions($cart, $method, $cart_prices)
    {

        $this->_currentMethod = $method;
        $this->convert_condition_amount($method);
        $address = $cart->getST();
        $amount = $this->getCartAmount($cart_prices);
        $amount_cond = ($amount >= $this->_currentMethod->min_amount and $amount <= $this->_currentMethod->max_amount
            or
            ($this->_currentMethod->min_amount <= $amount and ($this->_currentMethod->max_amount == 0)));
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


    function convert_condition_amount(&$method)
    {
        $method->min_amount = (float)str_replace(',', '.', $method->min_amount);
        $method->max_amount = (float)str_replace(',', '.', $method->max_amount);
    }

    /**
     * plgVmOnPaymentNotification() -It can be used to validate the payment data as entered by the user.
     * Return:
     * Parameters:
     * None
     * @author Valerie Isaksen
     */
    function plgVmOnPaymentNotification():void
    {

        if (!class_exists('VirtueMartModelOrders')) {
            require(VMPATH_ADMIN . DS . 'models' . DS . 'orders.php');
        }

        $mb_data = vRequest::getRequest();

        $is_reditect = false;
        if (isset($mb_data['ref_payco'])) {
            $url = 'https://secure.epayco.io/validation/v1/reference/'.$mb_data['ref_payco'];
            $responseData = $this->agafa_dades($url,false,$this->goter());
            $jsonData = @json_decode($responseData, true);
            $validationData = $jsonData['data'];
            $x_id_invoice = $validationData['x_id_invoice'];
            $x_ref_payco = $validationData['x_ref_payco'];
            $x_extra2 = $validationData['x_extra2'];
            $x_amount = $validationData['x_amount'];
            $x_transaction_id = $validationData['x_transaction_id'];
            $x_currency_code = $validationData['x_currency_code'];
            $x_signature = $validationData['x_signature'];
            $x_cod_transaction_state = $validationData['x_cod_transaction_state'];
            $x_response= $validationData['x_response'];
            $is_reditect = true;
        }


        if (isset($mb_data['x_id_invoice'])) {
            $x_id_invoice = $mb_data['x_id_invoice'];
            $x_ref_payco = $mb_data['x_ref_payco'];
            $x_extra2 = $mb_data['x_extra2'];
            $x_amount = $mb_data['x_amount'];
            $x_transaction_id = $mb_data['x_transaction_id'];
            $x_currency_code = $mb_data['x_currency_code'];
            $x_signature = $mb_data['x_signature'];
            $x_cod_transaction_state = $mb_data['x_cod_transaction_state'];
            $x_response= $mb_data['x_response'];
        }

        $virtuemart_paymentmethod_id = $mb_data['pm'];
        $order_number = $mb_data['on'];
        //$this->debugLog(var_export($retourParams, true), 'plgVmOnPaymentNotification getRetourParams', 'debug', false);
        $virtuemart_order_id = VirtueMartModelOrders::getOrderIdByOrderNumber($order_number);

        if (!($virtuemart_order_id)) {
            exit();
        }

        $this->updateStatusOrder($virtuemart_order_id, $x_response, $x_amount, $x_ref_payco, $x_transaction_id, $x_currency_code,$x_signature);
        $app = JFactory::getApplication();
        $responseUrl = JURI::base() . "index.php?option=com_virtuemart&amp;view=orders&amp;layout=details&amp;order_number=" . $order_number .
            "&order_pass=".$x_extra2."&";
        if($is_reditect){
            $this->receivedEpaycoTransaction($mb_data);
            //$app->redirect($responseUrl);
        }else{
            die("Confirmacion exitosa");
        }
    }

    function plgVmOnPaymentResponseReceived(&$html): bool
    {
        if (!class_exists('VirtueMartModelOrders')) {
            require(VMPATH_ADMIN . DS . 'models' . DS . 'orders.php');
        }

        if (!($payment_method = $this->getVmPluginMethod($_REQUEST['payment_method_id']))) {
            return false;
        }

        $mb_data = vRequest::getRequest();
        if (isset($mb_data['ref_payco'])) {
            $payment = $this->receivedEpaycoTransaction($mb_data);
            $this->updateStatusOrder( $payment['orderId'], $payment['status'], $payment['valor'], $payment['refPayco'], $payment['x_transaction_id'], $payment['currency'], $payment['x_signature']);
            $params = array(
                "payment" => $payment
            );
            $html = $this->renderByLayout('epayco_response_standard', $params);
            //reset cart
            $this->emptyCart();
            //JRequest::setVar('html', $html);
        }
        return TRUE;
    }

    function updateStatusOrder($order_id, $statusOrder,$x_amount, $x_ref_payco, $x_transaction_id, $x_currency_code, $x_signature){

        $orderModel=VmModel::getModel('orders');
        $orderDetails = $orderModel->getOrder($order_id);

        if (!($orderDetails)) {
            exit();
        }

        if ($orderDetails) {
            if (number_format(floatval($orderDetails['details']['BT']->order_total), 2, '.', '') == number_format(floatval($x_amount), 2, '.', '')) {
                $validation = true;
            } else {
                $validation = false;
            }
            $method = $this->getVmPluginMethod($orderDetails['details']['BT']->virtuemart_paymentmethod_id);
            $orderStatus = $orderDetails['details']['BT']->order_status;

            $signature = hash(
                'sha256',
                trim($method->payco_user_id) . '^'
                . trim($method->payco_encrypt_key) . '^'
                . $x_ref_payco . '^'
                . $x_transaction_id . '^'
                . $x_amount . '^'
                . $x_currency_code
            );

            switch ($statusOrder) {
                case 'Aceptada':
                    $status = 'C';
                    break;
                case 'Rechazada':
                case 'Fallida':
                case 'abandonada':
                case 'Cancelada':
                    $status = 'X';
                    break;
                case 'Pendiente':
                    $status = 'P';
                    break;
                default:
                    $status = 'P';
                    break;
            }

            $nb_history = count($orderDetails['history']);
            $orderDetails['customer_notified']=1;
            $orderDetails['order_status'] = $status;
            //$customer_total = (number_format((float)$order['details']['BT']->order_total, 2, '.', ''));
            //$orderDetails['comments'] = vmText::sprintf('VMPAYMENT_TCO_PAYMENT_STATUS_CONFIRMED', $order_number);
            $orderDetails['virtuemart_order_id'] = $order_id;
            // Guardar los cambios
            try{
                if($signature == $x_signature){
                    if($orderStatus !== $status){
                        //$result = $orderModel->updateOrder($orderDetails);
                        $result = $orderModel->updateStatusForOneOrder($order_id, $orderDetails, true);
                        if($status=='X'){
                            //reponer inventario
                            $db = JFactory::getDbo();

                            foreach ($orderDetails['items'] as $item) {
                                $productId = $item->virtuemart_product_id;
                                $cantidadVendida = $item->product_quantity;

                                // Sumar del stock
                                $query = $db->getQuery(true)
                                    ->update($db->quoteName('#__virtuemart_products'))
                                    ->set($db->quoteName('product_in_stock') . ' = ' . $db->quoteName('product_in_stock') . ' + ' . (int) $cantidadVendida)
                                    ->where($db->quoteName('virtuemart_product_id') . ' = ' . (int) $productId);

                                $db->setQuery($query);
                                $db->execute();
                            }

                        }
                        if (!$result) {
                            die('no se pudo actualziar la orden.');
                        }
                    }
                }else{
                    die('Firma no valida.');
                }

            }catch(\Exception $e){
                die($e->getMessage());
            }

        } else {
            die('Orden no encontrada.');
        }
    }


    public function receivedEpaycoTransaction($mb_data):array
    {
        $url = 'https://secure.epayco.io/validation/v1/reference/'.$mb_data['ref_payco'];
        $responseData = $this->agafa_dades($url,false,$this->goter());
        $jsonData = @json_decode($responseData, true);
        $validationData = $jsonData['data'];
        $x_signature = $validationData['x_signature'];
        $x_amount = $validationData['x_amount'];
        $x_amount_base = $validationData['x_amount_base'];
        $x_cardnumber = $validationData['x_cardnumber'];
        $x_id_invoice = $validationData['x_id_invoice'];
        $x_franchise = $validationData['x_franchise'];
        $x_transaction_id = $validationData['x_transaction_id'];
        $x_transaction_date = $validationData['x_transaction_date'];
        $x_transaction_state = $validationData['x_transaction_state'];
        $x_customer_ip = $validationData['x_customer_ip'];
        $x_description = $validationData['x_description'];
        $x_response= $validationData['x_response'];
        $x_response_reason_text= $validationData['x_response_reason_text'];
        $x_approval_code= $validationData['x_approval_code'];
        $x_ref_payco= $validationData['x_ref_payco'];
        $x_tax= $validationData['x_tax'];
        $x_currency_code= $validationData['x_currency_code'];
        $x_extra3= $validationData['x_extra3'];
        $x_cod_transaction_state = $validationData['x_cod_transaction_state'];
        $x_signature = $validationData['x_signature'];
        $baseUlr = JURI::base() . 'plugins/vmpayment/payco/payco/images/';
        switch ($x_response) {
            case 'Aceptada': {
                $iconUrl = $baseUlr.'check.png';
                $iconColor = '#67C940';
                $message = vmText::_('PLG_VMPAYMENT_PAYCO_PAYMENT_STATUS_SUCCESS');
            }break;
            case 'Pendiente':
            case 'Pending':{
                $iconUrl = $baseUlr.'warning.png';
                $iconColor = '#FFD100';
                $message = vmText::_('PLG_VMPAYMENT_PAYCO_PAYMENT_STATUS_PENDING');

            }break;
            default: {
                $iconUrl = $baseUlr.'error.png';
                $iconColor = '#E1251B';
                $message = vmText::_('PLG_VMPAYMENT_PAYCO_PAYMENT_STATUS_FAIL');
            }break;
        }

        $donwload_url = JURI::base() .'index.php?option=com_virtuemart&view=vmplg&task=&tmpl=component&on=' . $x_id_invoice .
            '&pm=' . $_REQUEST['payment_method_id'].
            '&o_id=' . $x_extra3.
            '&refPayco='.$x_ref_payco.
            '&fecha='.$x_transaction_date.
            '&franquicia='.$x_franchise.
            '&descuento=0'.
            '&autorizacion='.$x_approval_code.
            '&valor='.$x_amount.
            '&estado='.$x_response.
            '&descripcion='.$x_description.
            '&respuesta='.$x_response.
            '&ip='.$x_customer_ip;
        $is_cash = false;
        if($x_franchise == 'EF'||
            $x_franchise == 'GA'||
            $x_franchise == 'PR'||
            $x_franchise == 'RS'||
            $x_franchise == 'SR'
        ){
            $x_cardnumber_ = null;
            $is_cash = true;
        }else{
            if($x_franchise == 'PSE' || $x_franchise == 'DP'){
                $x_cardnumber_ = null;
            }else{
                $x_cardnumber_ = isset($x_cardnumber)?substr($x_cardnumber, -8):null;
            }

        }
        $payment = [
            'franchise_logo' => 'https://secure.epayco.co/img/methods/'.$x_franchise.'.svg',
            'x_amount_base' => $x_amount_base,
            'x_cardnumber' => $x_cardnumber_,
            'status' => $x_response,
            'type' => "",
            'refPayco' => $x_ref_payco,
            'factura' => $x_id_invoice,
            'descripcion_order' => $x_description,
            'valor' => $x_amount,
            'iva' => $x_tax,
            'estado' => $x_transaction_state,
            'response_reason_text' => $x_response_reason_text,
            'respuesta' => $x_response,
            'fecha' => $x_transaction_date,
            'currency' => $x_currency_code,
            'name' => '',
            'card' => '',
            'message' => $message,
            'error_message' => vmText::_('PLG_VMPAYMENT_PAYCO_ERROR_MESSAGE'),
            'error_description' => vmText::_('PLG_VMPAYMENT_PAYCO_ERROR_DESCRIPTION'),
            'payment_method'  => vmText::_('PLG_VMPAYMENT_PAYCO_PAYMENTMETHOD'),
            'response'=>  vmText::_('PLG_VMPAYMENT_PAYCO_PAYMENT_RESPONSE'),
            'dateandtime' => vmText::_('PLG_VMPAYMENT_PAYCO_PAYMENT_DATE'),
            'authorization' => $x_approval_code,
            'iconUrl' => $iconUrl,
            'iconColor' => $iconColor,
            'epayco_icon' => $baseUlr.'logo_white.png',
            'ip' => $x_customer_ip,
            'totalValue' => vmText::_('PLG_VMPAYMENT_PAYCO_PAYMENT_TOTAL'),
            'description' => vmText::_('PLG_VMPAYMENT_PAYCO_PAYMENT_DESCRIPTION'),
            'reference' => vmText::_('PLG_VMPAYMENT_PAYCO_PAYMENT_REFERENCE'),
            'purchase' => vmText::_('PLG_VMPAYMENT_PAYCO_PAYMENT_PURCHASE'),
            'iPaddress' => vmText::_('PLG_VMPAYMENT_PAYCO_PAYMENT_IP'),
            'receipt' => vmText::_('PLG_VMPAYMENT_PAYCO_PAYMENT_RECEIPT'),
            'authorizations' =>  vmText::_('PLG_VMPAYMENT_PAYCO_PAYMENT_AUTHORIZATION'),
            'paymentMethod'  => vmText::_('PLG_VMPAYMENT_PAYCO_PAYMENT_METHOD'),
            'epayco_refecence'  => vmText::_('PLG_VMPAYMENT_PAYCO_PAYMENT_REFERENCE'),
            'donwload_url' => $donwload_url,
            'donwload_text' =>'donwload_text',
            'code' =>'code'??null,
            'is_cash' => $is_cash,
            'orderId' => $x_extra3,
            'x_cod_transaction_state' => $x_cod_transaction_state,
            'x_transaction_id' => $x_transaction_id,
            'x_signature' => $x_signature
         ];
        if($is_cash){
            $payment['pin'] = $validationData['x_pin'];
            $payment['codeProject'] = $validationData['x_dod_project'];
            $payment['expirationDate'] = $validationData['x_expires_date'];
            $payment['expirationDateText'] = vmText::_('PLG_VMPAYMENT_PAYCO_PAYMENT_EXPIRATION_DATE');
        }

        return $payment;
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
    function plgVmOnStoreInstallPaymentPluginTable($jplugin_id)
    {
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
    public function plgVmOnSelectCheckPayment(VirtueMartCart $cart, &$msg)
    {
        if (!($this->_currentMethod = $this->getVmPluginMethod($cart->virtuemart_paymentmethod_id))) {
            return NULL; // Another method was selected, do nothing
        }
        if (!$this->selectedThisElement($this->_currentMethod->payment_element)) {
            return NULL;
        }
        return $this->onSelectCheck($cart);
    }



    public function plgVmDisplayListFEPayment(VirtueMartCart $cart, &$paymentMethods, &$htmlIn) {
        if ($this->getPluginMethods($cart->vendorId) === 0) {
            return false;
        }

        foreach ($this->methods as $this->_currentMethod) {
            $paymentMethodId = $this->_currentMethod->virtuemart_paymentmethod_id;
            // Obtener métodos de pago permitidos desde la configuración
            $opciones_pago = $this->_currentMethod->enabled_methods;
            // Si no hay métodos configurados, omitir este método
            if (empty($opciones_pago)) {
               // continue;
            }

            if (!$this->_currentMethod->payment_element == 'payco') {
                continue;
            }

            $this->epayco->paymendInfo = $this->_currentMethod;
            $html =  $this->epayco->plgVmDisplayListFEPayment($cart, $paymentMethods, $htmlIn);

            //$methodSalesPrice = $this->setCartPrices($cart, $cart->cartPrices, $this->_currentMethod);
            //$html = $this->getPluginHtml($this->_currentMethod, $paymentMethods, $methodSalesPrice);

            foreach ($opciones_pago as $method) {
                /*if ($method == 'Ticket') {
                     $this->_currentMethod->payment_name = 'ticket';
                     $this->ticket->paymendInfo = $this->_currentMethod;
                     $html = $this->ticket->plgVmDisplayListFEPayment($cart, $paymentMethods, $htmlIn);
                }
                if ($method == 'Epayco') {
                    $this->_currentMethod->payment_name = 'epayco';
                    $this->epayco->paymendInfo = $this->_currentMethod;
                    $html = $this->epayco->plgVmDisplayListFEPayment($cart, $paymentMethods, $htmlIn);
                    }
                */

            }


            // Agregar descripción y logo si está configurado
            if (!empty($this->paymendInfo->payment_desc)) {
                $html .= '<div class="vmpayment_description">' . $this->paymendInfo->payment_desc . '</div>';
            }
            if (!empty($this->paymendInfo->payment_logo)) {
                $html .= '<div class="vmpayment_cardinfo">';
                $html .= '<img src="' . JURI::root() . $this->paymendInfo->payment_logo . '" alt="' . $this->paymendInfo->payment_name . '" width="150px"/>';
                $html .= '</div>';
            }

            $htmla[] = $html;
        }

        $htmlIn[] = $htmla;
        return TRUE;
    }


    public function plgVmonSelectedCalculatePricePayment(VirtueMartCart $cart, array &$cart_prices, &$cart_prices_name)
    {
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
    function plgVmOnCheckAutomaticSelectedPayment(VirtueMartCart $cart, array $cart_prices = array())
    {
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
    public function plgVmOnShowOrderFEPayment($virtuemart_order_id, $virtuemart_paymentmethod_id, &$payment_name)
    {
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
    function plgVmonShowOrderPrintPayment($order_number, $method_id)
    {
        return $this->onShowOrderPrint($order_number, $method_id);
    }

    function plgVmDeclarePluginParamsPayment($name, $id, &$data) {
        return $this->declarePluginParams('payment', $name, $id, $data);
    }

    function plgVmDeclarePluginParamsPaymentVM3(&$data)
    {
        return $this->declarePluginParams('payment', $data);
    }

    function plgVmSetOnTablePluginParamsPayment($name, $id, &$table)
    {
        return $this->setOnTablePluginParams($name, $id, $table);
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


    private function getRetourParams($cryptedParams)
    {
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


    private function getContext()
    {
        $session = JFactory::getSession();
        return $session->getId();
    }


    private function isValidContext($context)
    {
        if ($this->getContext() == $context) {
            return true;
        }
        return false;
    }



    /**
     * @param string $message
     * @param string $title
     * @param string $type
     * @param bool $echo
     * @param bool $doVmDebug
     */
    public function debugLog($message, $title = '', $type = 'message', $echo = false, $doVmDebug = false)
    {
        if ($this->_currentMethod->debug) {
            $this->debug($message, $title, true);
        }

        if ($echo) {
            echo $message . '<br/>';
        }
        parent::debugLog($message, $title, $type, $doVmDebug);
    }

    public function debug($subject, $title = '', $echo = true)
    {
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
        return stream_context_create(array(
            'http' => array(
                'method' => 'POST',
                'header' => 'Content-Type: application/x-www-form-urlencoded',
                'protocol_version' => 1.1,
                'timeout' => 10,
                'ignore_errors' => true
            )
        ));
    }
    

}
