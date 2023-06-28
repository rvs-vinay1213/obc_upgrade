<?php

namespace Magenest\StripePayment\Model;

use Magento\Framework\Exception\LocalizedException;

class WeChatPay extends AbstractPayment
{
    const CODE = 'magenest_stripe_wechatpay';
    protected $_code = self::CODE;

    protected $_isGateway = true;
    protected $_canAuthorize = true;
    protected $_canCapture = true;
    protected $_canCapturePartial = false;
    protected $_canCaptureOnce = true;
    protected $_canVoid = false;
    protected $_canUseInternal = false;
    protected $_canUseCheckout = true;
    protected $_canRefund = true;
    protected $_canRefundInvoicePartial = true;
    protected $_isInitializeNeeded = true;
    protected $_canOrder = false;

    /**
     * @return string
     */
    public function getConfigPaymentAction()
    {
        return \Magento\Payment\Model\Method\AbstractMethod::ACTION_AUTHORIZE;
    }

    /**
     * @param \Magento\Framework\DataObject $data
     * @return $this|WeChatPay
     * @throws LocalizedException
     */
    public function assignData(\Magento\Framework\DataObject $data)
    {
        $this->_debug("Function: assignData");
        $infoInstance = $this->getInfoInstance();
        $additionalData = $data->getData('additional_data');
        parent::assignData($data);

        $stripeResponse = isset($additionalData['stripe_response']) ? $additionalData['stripe_response'] : "";
        $response = json_decode($stripeResponse, true);
        if ($response) {
            $sourceId = isset($response['id']) ? $response['id'] : false;
            $infoInstance->setAdditionalInformation('stripe_response', $stripeResponse);
            $infoInstance->setAdditionalInformation('stripe_source_id', $sourceId);
        }
        $infoInstance->setAdditionalInformation("stripe_uid", uniqid());
        return $this;
    }

    /**
     * @param string $paymentAction
     * @param object $stateObject
     * @return WeChatPay
     * @throws LocalizedException
     */
    public function initialize($paymentAction, $stateObject)
    {
        try {
            $payment = $this->getInfoInstance();
            $order = $payment->getOrder();
            $quoteId = $order->getQuoteId();
            $sourceId = $payment->getAdditionalInformation("stripe_source_id");
            $sourceModel = $this->sourceFactory->create();
            $sourceModel->setData("source_id", $sourceId);
            $sourceModel->setData("method", $payment->getMethod());
            $sourceModel->setData("quote_id", $quoteId);
            $sourceModel->isObjectNew(true);
            $sourceModel->save();
            return parent::initialize($paymentAction, $stateObject);
        } catch (\Exception $e) {
            throw new LocalizedException(__($e->getMessage()));
        }
    }

    /**
     * @return string[]
     */
    protected function getAcceptedCurrencyCodes()
    {
        return ['aud', 'cad', 'eur', 'gbp', 'hkd', 'jpy', 'sgd', 'usd'];
    }
}
