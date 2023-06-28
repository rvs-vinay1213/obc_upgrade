<?php

namespace Magenest\StripePayment\Model;


class Eps extends AbstractPayment
{
    const CODE = 'magenest_stripe_eps';
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

    /**
     * @return string[]
     */
    protected function getAcceptedCurrencyCodes()
    {
        return ['eur'];
    }
}
