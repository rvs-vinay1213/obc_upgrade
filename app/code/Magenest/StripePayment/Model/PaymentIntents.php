<?php

namespace Magenest\StripePayment\Model;

use Magento\Framework\Exception\LocalizedException;

class PaymentIntents extends AbstractPayment
{
    const CODE = 'magenest_stripe_paymentintents';
    public $_config;
    protected $_code = self::CODE;
    protected $_stripe;
    protected $_isGateway = true;
    protected $_canCapture = true;
    protected $_canCapturePartial = true;
    protected $_canCaptureOnce = true;
    protected $_canAuthorize = true;
    protected $_canRefund = true;
    protected $_canRefundInvoicePartial = true;
    protected $_canUseInternal = false;
    protected $_canVoid = true;

    /**
     * @param \Magento\Framework\DataObject $data
     * @return PaymentIntents
     * @throws LocalizedException
     */
    public function assignData(\Magento\Framework\DataObject $data)
    {
        $infoInstance = $this->getInfoInstance();
        $additionalData = $data->getData('additional_data');
        $sourceId = isset($additionalData['source_id']) ? $additionalData['source_id'] : false;
        $infoInstance->setAdditionalInformation('source_id', $sourceId);
        return parent::assignData($data);
    }

    /**
     * @param \Magento\Payment\Model\InfoInterface|\Magento\Sales\Model\Order\Payment $payment
     * @param float $amount
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function authorize(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        try {
            $order = $payment->getOrder();
            $this->stripeHelper->initStripeApi();
            $intentId = $payment->getAdditionalInformation('source_id');
            if ($intentId) {
                $intent = \Stripe\PaymentIntent::retrieve($intentId);
                $this->_debug($intent->getLastResponse()->json);
                $this->updatePaymentIntent($intentId, $order);
                $charges = \Stripe\Charge::all([
                    'payment_intent' => $intentId,
                    'limit' => 3,
                ]);
                $chargeFlag = false;
                foreach ($charges as $charge) {
                    $chargeStatus = $charge->status;
                    if ($chargeStatus == 'succeeded') {
                        $chargeId = $charge->id;
                        $payment->setAdditionalInformation("stripe_charge_id", $chargeId);
                        $payment->setTransactionId($chargeId)
                            ->setLastTransId($chargeId);
                        $payment->setIsTransactionClosed(0);
                        $payment->setShouldCloseParentTransaction(0);
                        $chargeFlag = true;
                        $stripeAmount = $charge->amount;
                    }
                }
                $this->stripeHelper->checkTransaction($payment, $stripeAmount);
                if (!$chargeFlag) {
                    throw new LocalizedException(
                        __("Payment failed")
                    );
                }
                return parent::authorize($payment, $amount);
            } else {
                throw new LocalizedException(
                    __("Transaction doesn't existed.")
                );
            }
        } catch (\Stripe\Exception\ApiErrorException $e) {
            throw new LocalizedException(__($e->getMessage()));
        }
    }

    /**
     * @param \Magento\Payment\Model\InfoInterface|\Magento\Sales\Model\Order\Payment $payment
     * @param float $amount
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function capture(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        try {
            $order = $payment->getOrder();
            $this->stripeHelper->initStripeApi();
            $intentId = $payment->getAdditionalInformation("source_id");
            if ($intentId) {
                $intent = \Stripe\PaymentIntent::retrieve($intentId);
                $this->_debug($intent->getLastResponse()->json);
                $this->updatePaymentIntent($intentId, $order);
                if ($intent->capture_method == 'manual') {
                    $amount = $this->stripeHelper->getPaymentAmount($order, $amount);
                    $intent->capture(['amount_to_capture' => $amount]);
                    $transactionId = $intent->charges->data[0]->balance_transaction;
                    $payment->setTransactionId($transactionId)
                        ->setIsTransactionClosed(0)
                        ->setShouldCloseParentTransaction(0);
                } else {
                    $charges = \Stripe\Charge::all([
                        'payment_intent' => $intentId,
                        'limit' => 3,
                    ]);

                    $chargeFlag = false;
                    foreach ($charges as $charge) {
                        $chargeStatus = $charge->status;
                        if ($chargeStatus == 'succeeded') {
                            $chargeId = $charge->id;
                            $payment->setAdditionalInformation("stripe_charge_id", $chargeId);
                            $transactionId = $charge->balance_transaction;
                            $payment->setTransactionId($transactionId)
                                ->setLastTransId($transactionId);
                            $payment->setIsTransactionClosed(0);
                            $payment->setShouldCloseParentTransaction(0);
                            $chargeFlag = true;
                            $stripeAmount = $charge->amount;
                        }
                    }
                    $this->stripeHelper->checkTransaction($payment, $stripeAmount);
                    if (!$chargeFlag) {
                        throw new LocalizedException(
                            __("Payment failed")
                        );
                    }
                }
                if (!$this->canCapture()) {
                    throw new \Magento\Framework\Exception\LocalizedException(__('The capture action is not available.'));
                }
                return $this;
            } else {
                throw new LocalizedException(
                    __("Transaction doesn't existed.")
                );
            }
        } catch (\Stripe\Exception\ApiErrorException $e) {
            throw new LocalizedException(__($e->getMessage()));
        }
    }

    /**
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @return PaymentIntents
     * @throws LocalizedException
     * @throws \Magenest\StripePayment\Exception\StripePaymentException
     */
    public function void(\Magento\Payment\Model\InfoInterface $payment)
    {
        try {
            $order = $payment->getOrder();
            $this->stripeHelper->initStripeApi();
            $chargeId = $payment->getAdditionalInformation("source_id");
            $intent = \Stripe\PaymentIntent::retrieve($chargeId);
            $intent->cancel();
            $payment->setShouldCloseParentTransaction(1);
            $payment->setIsTransactionClosed(1);
            return parent::void($payment);
        } catch (\Stripe\Exception\ApiErrorException $e) {
            throw new LocalizedException(__($e->getMessage()));
        }
    }

    /**
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @return PaymentIntents
     * @throws LocalizedException
     * @throws \Magenest\StripePayment\Exception\StripePaymentException
     */
    public function cancel(\Magento\Payment\Model\InfoInterface $payment)
    {
        try {
            $order = $payment->getOrder();
            $this->stripeHelper->initStripeApi();
            $chargeId = $payment->getAdditionalInformation("source_id");
            $intent = \Stripe\PaymentIntent::retrieve($chargeId);
            $intent->cancel();
            $payment->setShouldCloseParentTransaction(1);
            $payment->setIsTransactionClosed(1);
            return parent::cancel($payment);
        } catch (\Stripe\Exception\ApiErrorException $e) {
            throw new LocalizedException(__($e->getMessage()));
        }
    }

    /**
     * @param $intentId
     * @param $order
     * @throws \Stripe\Exception\ApiErrorException
     */
    private function updatePaymentIntent($intentId, $order)
    {
        $dataUpdate = [
            'description' => $this->stripeHelper->getPaymentDescription($order),
            'metadata' => $this->stripeHelper->getPaymentMetaData($order),
        ];
        if ($this->stripeConfig->sendMailCustomer()) {
            $dataUpdate['receipt_email'] = $order->getCustomerEmail();
        }
        if ($order->getIsNotVirtual()) {
            $dataUpdate['shipping'] = $this->stripeHelper->getShippingInformation($order);
        }
        \Stripe\PaymentIntent::update(
            $intentId,
            $dataUpdate
        );
    }
}
