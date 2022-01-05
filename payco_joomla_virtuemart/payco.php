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
		return $this->createTableSQL('Payment Payco Table');
	}

	function getTableSQLFields() {
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
			'external'=>$this->_currentMethod->p_external_request,
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
            'p_test_request'    => $test,
            'notification_url' => (JROUTE::_ (JURI::root () .'index.php?option=com_virtuemart&view=vmplg&task=pluginNotification&tmpl=component&on=' . $order['details']['BT']->order_number . '&pm=' . $order['details']['BT']->virtuemart_paymentmethod_id . '&o_id='.$order['details']['BT']->virtuemart_order_id)),
            'p_confirmation_url' =>  JURI::base() .'plugins/vmpayment/payco/payco/' .'confirmacion.php',
			'p_url_respuesta' =>  JURI::base() .'plugins/vmpayment/payco/payco/' .'response.php',
			'lang' =>  $this->_currentMethod->epayco_lang
		);
			if($this->_currentMethod->epayco_lang == "en"){
				$button = "https://multimedia.epayco.co/epayco-landing/btns/Boton-epayco-color-Ingles.png"; 
				$msgEpaycoCheckout = '<span class="animated-points">Loading payment methods</span>
				<br><small class="epayco-subtitle"> If they do not load automatically, click on the "Pay with ePayco" button</small>';
			}else{
				$button = "https://multimedia.epayco.co/epayco-landing/btns/Boton-epayco-color1.png";
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

		<form class=\"text-center\">

            <script src=\"https://checkout.epayco.co/checkout.js\" 
                class=\"epayco-button\" id=\"change\"
                data-epayco-key=\"{$post_variables['p_public_key']}\"
                data-epayco-tax-base =\"{$post_variables['p_amount_base']}\"
                data-epayco-tax =\"{$post_variables['p_tax']}\"
                data-epayco-amount=\"{$post_variables['p_amount_']}\"
                data-epayco-extra1=\"{$post_variables['p_id_factura']}\" 
                data-epayco-name=\"{$post_variables['p_product_name']}\" 
                data-epayco-description=\"{$post_variables['p_description']}\" 
                data-epayco-currency=\"{$post_variables['p_currency_code']}\" 
                data-epayco-test=\"{$test}\"
				data-epayco-external=\"{$post_variables['external']}\"
                data-epayco-response=\"{$post_variables['p_url_respuesta']}\"
                data-epayco-country=\"{$post_variables['p_country_code']}\"
                data-epayco-confirmation=\"{$post_variables['p_confirmation_url']}\" 
                data-epayco-email-billing=\"{$post_variables['p_billing_email']}\"
                data-epayco-mobilephone-billing=\"{$post_variables['p_cellphone_billing']}\"
                data-epayco-address-billing=\"{$post_variables['p_billing_adress']}\"
				data-epayco-extra2=\"{$order['details']['BT']->order_pass}\"
				data-epayco-extra3=\"{$order['details']['BT']->virtuemart_order_id}\"
				data-epayco-lang=\"{$post_variables['lang']}\"
				data-epayco-button=\"{$button}\"
                data-epayco-autoClick=\"true\"
                >
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

		$cart = VirtueMartCart::getCart();
		$cart->emptyCart();
		JRequest::setVar ('html', $js.$html);
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
	 *  None
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


	function getCosts(VirtueMartCart $cart, $method, $cart_prices) {

		if (preg_match('/%$/', $method->cost_percent_total)) {
			$cost_percent_total = substr($method->cost_percent_total, 0, -1);
		} else {
			$cost_percent_total = $method->cost_percent_total;
		}
		return ($method->cost_per_transaction + ($cart_prices['salesPrice'] * $cost_percent_total * 0.01));
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
		return $this->displayListFE($cart, $selected, $htmlIn);
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