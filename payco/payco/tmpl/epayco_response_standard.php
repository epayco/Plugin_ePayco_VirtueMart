
<?php

defined('_JEXEC') or die('Restricted access');

/**
 * ePayco plugin
 *
 * @author Developers ePayco <ricardo.saldarriaga@epayco.com>
 * @version 2.2.0
 * @package VirtueMart
 * @subpackage payment
 * @link https://www.epayco.com
 * @copyright Copyright (c) 2004 - 2018 VirtueMart Team. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 */
$payment = $viewData["payment"];

?>
<div class="landingResumen">
    <nav class="navEpayco">
        <img src="https://secure.epayco.co/img/new-logo.svg" alt="logo">
    </nav>
    <div class="containerResumen">
        <div class="hole"></div>
        <div class="containerFacture">
            <div class="transaction">
                <img src="<?php echo $payment['iconUrl']; ?>" alt="check" style="display: block; margin: auto; border-bottom: 25px;">
                <div class="transactionText">
                    <div class="h1Facture h1Bold" style="color: <?php echo $payment['iconColor']; ?>;">
                        <?php echo $payment['message']; ?>
                    </div>
                    <div class="h1Facture">
                        <h2 style="font-size: 22px; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;font-weight: bold"><?php echo $payment['epayco_refecence']; ?> #<?php echo $payment['refPayco']; ?></h2>
                    </div>
                    <div class="">
                        <?php echo $payment['fecha']; ?>
                    </div>
                </div>
            </div>
            <div class="medioPago">
                <div class="medios">
                    <div class="h2Facture"> <?php echo $payment['paymentMethod']; ?></div>
                    <div class="parDescription">
                        <div class="titleAndText">
                            <div class="h3Facture"> <?php echo $payment['payment_method']; ?> </div>
                            <div class="pageAndImage">
                                <img class="metodoPago" src="<?php echo $payment['franchise_logo']; ?>" id="metodoPagoId" alt="logoTransacción">
                                <div class="pFacture"><?php echo $payment['x_cardnumber']; ?></div>
                            </div>
                        </div>
                        <div class="titleAndTextRight">
                            <div class="h3Facture"><?php echo $payment['authorizations']; ?>
                            </div>
                            <div class="pFacture">
                                <?php echo $payment['authorization']; ?>
                            </div>
                        </div>
                    </div>
                    <div class="parDescription">
                        <div class="titleAndText">
                            <div class="h3Facture"><?php echo $payment['receipt']; ?></div>
                            <div class="pFacture"><?php echo $payment['factura']; ?></div>
                        </div>
                        <div class="titleAndTextRight">
                            <div class="h3Facture"><?php echo $payment['iPaddress']; ?></div>
                            <div class="pFacture"><?php echo $payment['ip']; ?></div>
                        </div>
                    </div>
                    <?php if ($payment['is_cash']) : ?>
                        <div class="parDescription">
                            <div class="titleAndText">
                                <div class="h3Facture"><?php echo $payment['response']; ?></div>
                                <div class="pFacture"><?php echo $payment['response_reason_text']; ?></div>
                            </div>
                            <div class="titleAndTextRight">
                                <div class="h3Facture"><?php echo $payment['expirationDateText']; ?></div>
                                <div class="pFacture"><?php echo $payment['expirationDate']; ?></div>
                            </div>
                        </div>
                        <div class="parDescription">
                            <div class="pFacture"><?php echo $payment['ticket_header']; ?></div>
                        </div>
                        <div class="parDescription">
                            <div class="titleAndText">
                                <div class="h3Facture"><?php echo $payment['code']; ?></div>
                                <div class="pFacture"><?php echo $payment['codeProject']; ?></div>
                            </div>
                            <div class="titleAndTextRight">
                                <div class="h3Facture">Pin</div>
                                <div class="pFacture"><?php echo $payment['pin']; ?></div>
                            </div>
                        </div>

                    <?php else : ?>
                        <div class="parDescription">
                            <div class="titleAndTextComplete">
                                <div class="h3Facture"><?php echo $payment['response']; ?></div>
                                <div class="pFacture"><?php echo $payment['response_reason_text']; ?></div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="medios">
                    <div class="h2Facture"> <?php echo $payment['purchase']; ?></div>
                    <div class="parDescription">
                        <div class="titleAndText">
                            <div class="h3Facture"><?php echo $payment['reference']; ?></div>
                            <div class="pFacture"><?php echo $payment['refPayco']; ?></div>
                        </div>
                        <div class="titleAndTextRight">
                            <div class="h3Facture"><?php echo $payment['description']; ?></div>
                            <div class="pFacture"><?php echo $payment['descripcion_order']; ?></div>
                        </div>
                    </div>
                    <div class="parDescription">
                        <div class="titleAndText">
                            <div class="h3Facture"><?php echo $payment['totalValue']; ?></div>
                            <div class="pFacture">$<?php echo $payment['valor']; ?> <?php echo $payment['currency']; ?></div>
                        </div>
                        <div class="titleAndTextRight">
                            <div class="h3Facture">Subtotal</div>
                            <div class="pFacture">$<?php echo $payment['x_amount_base']; ?> <?php echo $payment['currency']; ?></div>
                        </div>
                    </div>
                </div>
                
            </div>
        </div>
    </div>
</div>


<!-- Fuente personalizada -->
<style>
    @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap');

    #transactionBody {
        height: 550px;
        max-width: 550px;
        margin: auto;
        position: relative;
    }

    .div-description {
        max-height: 46px;
        display: flex;
        flex-direction: column;
    }

    .description-title {
        font-size: 16px;
        font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif !important;
        color: darkgray;
        margin: 0px;
    }

    .descripcion-payment {
        font-size: 15px;
        font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif !important;
        margin: 0px;
    }

    @media only screen and (max-width: 425px) {
        #transactionBody {
            padding-bottom: 200px;
        }
    }


    .landingResumen {
        box-sizing: border-box;
        margin: 0;
        padding: 0;
        font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif !important;
        print-color-adjust: exact;
        -webkit-print-color-adjust: exact;
    }

    .navEpayco {
        justify-content: center;
        height: 6.5rem;
        background: #1d1d1d;
        print-color-adjust: exact;
        -webkit-print-color-adjust: exact;
    }

    .containerResumen {
        flex-direction: column;
        justify-content: flex-start;
        height: fit-content;
        gap: 1rem;
        padding-top: 3rem;
        padding-bottom: 4.8rem;
    }

    .containerResumen,
    .navEpayco {
        display: flex;
        align-items: center;
        width: 100%;
        /*height: 82px;*/
    }

    .navEpayco {
        display: flex;
        align-items: center;
        width: 100%;
        height: 55px !important;
    }

    .hole {
        padding-top: 1.6rem;
        overflow: visible;
        width: 557px;
        height: 0px;
        border-radius: 1.6rem;
        background: #1d1d1d;
        print-color-adjust: exact;
        -webkit-print-color-adjust: exact;
    }

    .containerFacture,
    .hole {
        display: flex;
        justify-content: center;
    }

    @media (max-width: 570px) {
        .hole {
            width: 95vw;
        }
    }

    .containerFacture {
        position: relative;
        align-items: center;
        transform: translateY(-1.95rem);
        flex-direction: column;
        background: #f9f9f9;
        height: fit-content;
        width: 490px;
        padding: 32px 24px 40px;
        gap: 18px;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, .1), 0 2px 4px -1px rgba(0, 0, 0, .06);
        border-radius: 0 0 10px 10px;
        border-right: 1px solid var(--grey-grey-80, #cacaca);
        border-bottom: 1px solid var(--grey-grey-80, #cacaca);
        border-left: 1px solid var(--grey-grey-80, #cacaca);
        box-shadow: 0 8px 16px 0 rgba(0, 0, 0, .08);
        top: 5px;
    }

    @media (max-width: 570px) {
        .containerFacture {
            width: 76vw;
        }
    }

    .containerFacture,
    .hole {
        display: flex;
        justify-content: center;
    }

    .transaction {
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        color: #1d1d1d;
        gap: 1.6rem;
    }

    .transactionText {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: .5rem;
    }

    @media (max-width: 570px) {
        .h1Facture {
            font-size: 20px;
        }
    }

    .h1Bold {
        font-weight: 600;
    }

    .h1Facture {
        font-size: 24px;
        display: block;
        font-style: normal;
        font-weight: bold;
        line-height: normal;
    }

    .pFacture {
        font-size: 16px;
        color: #000;
        width: 100%;
        box-sizing: border-box;
        overflow-wrap: break-word;
        word-break: break-word;
        white-space: pre-line; /* Permite saltos de línea si hay */
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .h3Facture,
    .pFacture {
        font-style: normal;
        font-weight: 400;
        line-height: normal;
    }

    @media (max-width: 570px) {

        .buttonContact,
        .buttonPrint {
            margin-top: 1rem;
            width: 90vw;
        }
    }

    .buttonContact,
    .buttonPrint {
        background: #1d1d1d;
        color: #fff;
        height: 2.8rem;
        width: 599px;
        cursor: pointer;
        border-radius: 6px;
        border: none;
        font-size: 18px;
        font-style: normal;
        font-weight: 600;
        line-height: normal;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .buttonContact,
    .buttonPrint:hover {
        background: #1d1d1dc7;
    }

    .buttonContact,
    .buttonPrint a {
        text-decoration: none;
    }

    .medioPago,
    .medios {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }

    .medioPago {
        width: 100%;
        justify-content: center;
        align-items: center;
    }

    .medioPago,
    .medios {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }

    .medios {
        width: 380px;
        align-items: self-start;
    }

    @media (max-width: 570px) {
        .medios {
            width: 65vw;
        }
    }

    .h2Facture {
        font-size: 16px;
        display: block;
        font-weight: 700;
    }

    .parDescription {
        display: flex;
        width: 100%;
        justify-content: space-between;
        align-items: flex-start;
    }

    .titleAndText,
    .titleAndTextRight {
        display: flex;
        flex-direction: column;
        width: 150px;
    }

    @media (max-width: 570px) {
        .titleAndText {
            margin-right: -10vw;
            width: 10.8rem;
        }
    }

    .h3Facture {
        font-size: 14px;
        color: #747474;
    }

    .pageAndImage {
        gap: 1rem;
        width: fit-content;
    }

    .metodoPago,
    .pageAndImage {
        display: flex;
        justify-content: center;
    }

    .metodoPago {
        align-items: center;
        height: 1.5rem !important;
    }

    .titleAndTextRight {
        margin: 0 -5px;
    }

    .titleAndText,
    .titleAndTextRight {
        display: flex;
        flex-direction: column;
        width: 150px;
    }

    .titleAndTextComplete {
        display: flex;
        flex-direction: column;
        width: 100%;
    }
</style>