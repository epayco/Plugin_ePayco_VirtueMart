<?php
/**
 * ePayco plugin
 *
 * @author ePayco
 * @version 2.2.0
 * @package VirtueMart
 * @subpackage payment
 * @link https://www.epayco.com
 * @copyright Copyright © 2016 ePayco.com
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 */

defined('JPATH_BASE') or die();

class JFormFieldGetPayco extends JFormField {

    protected function getInput()
    {
        vmJsApi::css('payco', 'plugins/vmpayment/payco/payco/assets/css/');
        vmJsApi::addJScript( '/plugins/vmpayment/payco/payco/assets/js/administrator.js');
        $banner = JURI::base() . '../plugins/vmpayment/payco/payco/images/logo.png';
        $html = '<img style="width:110px;" src="' . $banner . '" />';
        return $html;

    }
}