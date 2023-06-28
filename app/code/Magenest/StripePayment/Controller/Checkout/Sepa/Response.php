<?php

namespace Magenest\StripePayment\Controller\Checkout\Sepa;

use Magenest\StripePayment\Exception\StripePaymentException;
use Magento\Sales\Model\Order;

class Response extends \Magenest\StripePayment\Controller\Checkout\Response
{
    /**
     * @param $charge
     * @return bool
     */
    public function processCharge($charge)
    {
        /**
         * @var \Magento\Sales\Model\Order\Payment $payment
         * @var \Magento\Customer\Model\Session $customerSession
         * @var \Magento\Quote\Model\Quote $quote
         * @var \Magento\Sales\Model\Order $order
         */
        try {
            $this->waitStripeNotification();
            $sourceId = $charge->source->id;
            $sourceModel = $this->sourceFactory->create()->load($sourceId);
            $orderId = $sourceModel->getOrderId();
            if ($orderId) {
                $order = $this->stripeHelper->continueProcessOrder($orderId);
            } else {
                throw new StripePaymentException(__("Cannot get order info"));
            }
            if (isset($order) && $order->getCanSendNewEmailFlag()) {
                try {
                    $this->stripeLogger->debug("Email send for order " . $orderId);
                    $this->orderSender->send($order);
                } catch (\Exception $e) {
                    $this->stripeLogger->critical($e->getMessage());
                }
            }
            return true;
        } catch (\Exception $e) {
            $this->stripeHelper->debugException($e);
            return false;
        }
    }
}
