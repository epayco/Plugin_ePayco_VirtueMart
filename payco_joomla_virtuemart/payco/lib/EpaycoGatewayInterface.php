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

interface EpaycoGatewayInterface
{

    function plgVmOnPaymentResponseReceived(&$html): bool;

    function plgVmOnPaymentNotification():void;
    public function processEpaycoPayment($cart, $order): void;
}