<?php

namespace Magenest\StripePayment\Model;

use Magenest\StripePayment\Exception\StripePaymentException;
use Magento\Framework\Exception\LocalizedException;
use Stripe;

class Sepa extends AbstractPayment
{
    const CODE = 'magenest_stripe_sepa';
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
    protected $_isInitializeNeeded = true;

    /**
     * @param \Magento\Framework\DataObject $data
     * @return $this|Sepa
     * @throws LocalizedException
     */
    public function assignData(\Magento\Framework\DataObject $data)
    {
        parent::assignData($data);
        $additionalData = $data->getData('additional_data');
        $stripeResponse = isset($additionalData['stripe_response']) ? $additionalData['stripe_response'] : "";
        $response = json_decode($stripeResponse, true);
        $infoInstance = $this->getInfoInstance();
        if ($response) {
            $infoInstance->setAdditionalInformation('stripe_response', $stripeResponse);
        }
        return $this;
    }

    /**
     * @param string $paymentAction
     * @param object $stateObject
     * @return Sepa
     * @throws LocalizedException
     * @throws StripePaymentException
     */
    public function initialize($paymentAction, $stateObject)
    {
        try {
            $this->stripeHelper->initStripeApi();
            $payment = $this->getInfoInstance();
            $order = $payment->getOrder();
            $sourceId = $payment->getAdditionalInformation("stripe_source_id");
            $stripeResponseJson = $payment->getAdditionalInformation('stripe_response');
            $this->setSepaAdditionalInformation($payment, $stripeResponseJson);
            $amount = $order->getBaseGrandTotal();
            $chargeRequest = $this->stripeHelper->createChargeRequest($order, $amount, $sourceId);
            $charge = Stripe\Charge::create($chargeRequest);
            $this->_debug($charge->getLastResponse()->json);
            $chargeId = $charge->id;
            $payment->setAdditionalInformation("stripe_charge_id", $chargeId);
            $chargeStatus = $charge->status;
            $totalDue = $order->getTotalDue();
            $baseTotalDue = $order->getBaseTotalDue();
            $stateObject->setData('state', \Magento\Sales\Model\Order::STATE_PROCESSING);
            if ($chargeStatus == 'pending') {
                $order->setCanSendNewEmailFlag(false);
            }
            if ($chargeStatus == 'succeeded') {
                $transactionId = $charge->balance_transaction;
                $payment->setTransactionId($transactionId)
                    ->setLastTransId($transactionId);
                $payment->setAmountAuthorized($totalDue);
                $payment->setBaseAmountAuthorized($baseTotalDue);
                $payment->capture(null);
            }
            if ($chargeStatus == 'failed') {
                throw new StripePaymentException(
                    __("Payment failed")
                );
            }
            return parent::initialize($paymentAction, $stateObject);
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
        return $this->checkCapture($payment, $amount);
    }

    /**
     * @return string[]
     */
    protected function getAcceptedCurrencyCodes()
    {
        return ['eur'];
    }

    protected function setSepaAdditionalInformation($payment, $stripeResponseJson)
    {
        $stripeResponse = json_decode($stripeResponseJson ?: '', true);
        $sourceAdditionalInformation = [];
        $sourceAdditionalInformation[] = [
            'label' => "Payment Method",
            'value' => "SEPA Direct Debit"
        ];
        $sourceAdditionalInformation[] = [
            'label' => "Back Code",
            'value' => isset($stripeResponse['sepa_debit']['bank_code']) ? $stripeResponse['sepa_debit']['bank_code'] : ""
        ];
        $sourceAdditionalInformation[] = [
            'label' => "Branch Code",
            'value' => isset($stripeResponse['sepa_debit']['branch_code']) ? $stripeResponse['sepa_debit']['branch_code'] : ""
        ];
        $sourceAdditionalInformation[] = [
            'label' => "Country",
            'value' => isset($stripeResponse['sepa_debit']['country']) ? $stripeResponse['sepa_debit']['country'] : ""
        ];
        $sourceAdditionalInformation[] = [
            'label' => "Fingerprint",
            'value' => isset($stripeResponse['sepa_debit']['fingerprint']) ? $stripeResponse['sepa_debit']['fingerprint'] : ""
        ];
        $sourceAdditionalInformation[] = [
            'label' => "Last 4",
            'value' => isset($stripeResponse['sepa_debit']['last4']) ? $stripeResponse['sepa_debit']['last4'] : ""
        ];
        $sourceAdditionalInformation[] = [
            'label' => "Mandate Reference",
            'value' => isset($stripeResponse['sepa_debit']['mandate_reference']) ? $stripeResponse['sepa_debit']['mandate_reference'] : ""
        ];
        $sourceAdditionalInformation[] = [
            'label' => "Mandate Url",
            'value' => isset($stripeResponse['sepa_debit']['mandate_url']) ? $stripeResponse['sepa_debit']['mandate_url'] : ""
        ];
        $payment->setAdditionalInformation("stripe_source_additional_information", json_encode($sourceAdditionalInformation));
        $payment->setAdditionalInformation("stripe_sepa_mandate_reference", isset($stripeResponse['sepa_debit']['mandate_reference']) ? $stripeResponse['sepa_debit']['mandate_reference'] : "");
        $payment->setAdditionalInformation("stripe_sepa_mandate_url", isset($stripeResponse['sepa_debit']['mandate_url']) ? $stripeResponse['sepa_debit']['mandate_url'] : "");
    }
}
