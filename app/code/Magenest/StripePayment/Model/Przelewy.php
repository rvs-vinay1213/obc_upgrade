<?php

namespace Magenest\StripePayment\Model;


class Przelewy extends AbstractPayment
{
    const CODE = 'magenest_stripe_p24';
    protected $_code = self::CODE;

    protected $_isGateway = true;
    protected $_canAuthorize = false;
    protected $_canCapture = true;
    protected $_canCapturePartial = false;
    protected $_canCaptureOnce = true;
    protected $_canVoid = false;
    protected $_canUseInternal = false;
    protected $_canUseCheckout = true;
    protected $_canRefund = true;
    protected $_canRefundInvoicePartial = true;
    protected $_isInitializeNeeded = false;
    protected $_canOrder = false;
    protected $_infoBlockType = \Magenest\StripePayment\Block\Info\Przelewy::class;

    /**
     * @return string[]
     */
    protected function getAcceptedCurrencyCodes()
    {
        return ['eur', 'pln'];
    }
}
