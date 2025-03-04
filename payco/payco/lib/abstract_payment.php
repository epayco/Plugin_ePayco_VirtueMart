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
if (!class_exists('vmPSPlugin')) require(JPATH_VM_PLUGINS . DS . 'vmpsplugin.php');
require_once(dirname(__FILE__) . '/EpaycoGatewayInterface.php');



abstract class AbstractPaymentPlugin extends vmPSPlugin implements EpaycoGatewayInterface {

    public $paymentName;

    public $paymendInfo;

    public function __construct(&$subject, $config, $paymentName) {
        parent::__construct($subject, $config);
        $this->paymentName = $paymentName;
        $this->tableFields = array_keys($this->getTableSQLFields());
        $this->_tablepkey = 'id';
        $this->_tableId = 'id';
        $varsToPush = array(
            'enabled_methods' => array('', 'char')
        );
        $this->setConfigParameterable($this->_configTableFieldName, $varsToPush);
        //$this->createPaymentMethods();
    }

    public function getTableSQLFields() {
        return array(
            'id'                            => 'int(11) UNSIGNED NOT NULL AUTO_INCREMENT',
            'virtuemart_order_id' 					=> 'int(1) UNSIGNED',
            'order_number'                  => 'char(64)',
            'virtuemart_paymentmethod_id'   => 'mediumint(1) UNSIGNED',
            'payment_name'                  => 'varchar(5000)',
            'payment_order_total'           => 'decimal(15,5) NOT NULL',
            'payment_currency'              => 'smallint(1)',
            'cost_per_transaction'          => 'decimal(10,2)',
            'cost_percent_total'            => 'decimal(10,2)',
            'tax_id'                        => 'smallint(1)',
            'reference'                     => 'char(32)'
        );
    }

    public function plgVmConfirmedOrder($cart, $order) {
        if ($this->paymentName === 'Epayco') {
            return $this->processEpaycoPayment($cart, $order);
        }

        /*elseif ($this->paymentName === 'Ticket') {
            return $this->processTicketPayment($order);
        }*/
        return false;
    }

    public function plgVmDisplayListFEPayment(VirtueMartCart $cart, &$paymentMethods, &$htmlIn) {

        $method = $this->getPluginMethod($cart->virtuemart_paymentmethod_id);
        /*if ($method->payment_element !== 'payco') {
            return false;
        }

        if (!$this->paymendInfo->payment_element == 'payco') {
            return false;
        }*/

        // Generar HTML del método de pago
        /*$html = '<fieldset>';
        $html .= '<input type="radio" name="virtuemart_paymentmethod_id'. $this->paymendInfo->virtuemart_paymentmethod_id . '" value="' .$this->paymendInfo->payment_name . '" /> ';
        $html .= '<label>' . $this->paymendInfo->payment_name . '</label><br>';
        $html .= '</fieldset>';
        */
        $methodSalesPrice = $this->setCartPrices($cart, $cart->cartPrices, $this->paymendInfo);
        $html = $this->getPluginHtml($this->paymendInfo, $paymentMethods, $methodSalesPrice);


        return $html;
    }

    function plgVmOnPaymentResponseReceived(&$html): bool
    {
        return TRUE;
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
    }

    public function processEpaycoPayment($cart, $order):void
    {
    }




    private function createPaymentMethods()
    {
        $db = JFactory::getDBO();
        $paymentMethods = array(
            array('Epayco', 'epayco'),
            array('Ticket', 'ticket')
        );

        foreach ($paymentMethods as $method) {
            list($payment_name, $element) = $method;

            // Verificar si el método de pago ya existe
            $query = $db->getQuery(true)
                ->select($db->quoteName('virtuemart_paymentmethod_id'))
                ->from($db->quoteName('#__virtuemart_paymentmethods'))
                ->where($db->quoteName('payment_element') . ' = ' . $db->quote($element));
            $db->setQuery($query);
            $exists = $db->loadResult();

            if (!$exists) {
                // Insertar el nuevo método de pago
                $columns = [ 'payment_element', 'published', 'ordering', 'virtuemart_vendor_id'];
                $values = [ $db->quote($element), 1, 0, 1];

                $query = $db->getQuery(true)
                    ->insert($db->quoteName('#__virtuemart_paymentmethods'))
                    ->columns($db->quoteName($columns))
                    ->values(implode(',', $values));
                $db->setQuery($query);
                $db->execute();

            }
        }
    }



}
