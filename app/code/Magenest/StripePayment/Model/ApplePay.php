<?php

namespace Magenest\StripePayment\Model;

use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Model\Method\AbstractMethod;
use Stripe;

class ApplePay extends AbstractMethod
{
    const CODE = 'magenest_stripe_applepay';
    protected $_code = self::CODE;
    protected $_isGateway = true;
    protected $_canAuthorize = true;
    protected $_canCapture = true;
    protected $_canCapturePartial = true;
    protected $_canCaptureOnce = true;
    protected $_canVoid = true;
    protected $_canUseInternal = false;
    protected $_canUseCheckout = true;
    protected $_canRefund = true;
    protected $_canRefundInvoicePartial = true;
    protected $_canOrder = false;

    protected $stripeHelper;
    protected $stripeLogger;
    protected $stripeConfig;
    protected $request;

    /**
     * ApplePay constructor.
     * @param \Magenest\StripePayment\Helper\Data $stripeHelper
     * @param \Magenest\StripePayment\Helper\Config $stripeConfig
     * @param \Magenest\StripePayment\Helper\Logger $stripeLogger
     * @param \Magento\Framework\App\RequestInterface $request
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory
     * @param \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory
     * @param \Magento\Payment\Helper\Data $paymentData
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Payment\Model\Method\Logger $logger
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        \Magenest\StripePayment\Helper\Data $stripeHelper,
        \Magenest\StripePayment\Helper\Config $stripeConfig,
        \Magenest\StripePayment\Helper\Logger $stripeLogger,
        \Magento\Framework\App\RequestInterface $request,
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
        \Magento\Payment\Helper\Data $paymentData,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Payment\Model\Method\Logger $logger,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->stripeHelper = $stripeHelper;
        $this->stripeLogger = $stripeLogger;
        $this->stripeConfig = $stripeConfig;
        $this->request = $request;
        parent::__construct($context, $registry, $extensionFactory, $customAttributeFactory, $paymentData, $scopeConfig, $logger, $resource, $resourceCollection, $data);
    }

    /**
     * @param \Magento\Quote\Api\Data\CartInterface|null $quote
     * @return bool
     */
    public function isAvailable(\Magento\Quote\Api\Data\CartInterface $quote = null)
    {
        if (!class_exists(\Stripe\Stripe::class)) {
            return false;
        }
        return \Magento\Payment\Model\Method\AbstractMethod::isAvailable($quote);
    }

    /**
     * @param \Magento\Framework\DataObject $data
     * @return ApplePay|void
     * @throws LocalizedException
     */
    public function assignData(\Magento\Framework\DataObject $data)
    {
        parent::assignData($data);
        $this->_debug("Begin Stripe Applepay");
        $additionalData = $data->getData('additional_data');
        $stripeResponse = isset($additionalData['stripe_response']) ? $additionalData['stripe_response'] : "";
        $response = json_decode($stripeResponse, true);
        $this->_debug($response);
        if ($response) {
            $infoInstance = $this->getInfoInstance();
            $infoInstance->setAdditionalInformation('payment_token', $response['id']);
        } else {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('Payment data response error')
            );
        }
    }

    /**
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param float $amount
     * @return ApplePay
     * @throws LocalizedException
     * @throws \Magenest\StripePayment\Exception\StripePaymentException
     */
    public function authorize(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        try {
            $this->stripeHelper->initStripeApi();
            /** @var \Magento\Sales\Model\Order $order */
            $order = $payment->getOrder();
            $token = $payment->getAdditionalInformation('payment_token');
            $request = $this->stripeHelper->createChargeRequest($order, $amount, $token, false, false);
            $charge = Stripe\Charge::create($request);
            $stripeAmount = $charge->amount;
            $this->stripeHelper->checkTransaction($payment, $stripeAmount);
            $this->_debug($charge->getLastResponse()->json);
            $chargeId = $charge->id;
            $payment->setAdditionalInformation('stripe_charge_id', $chargeId);
            $payment->setAdditionalInformation("stripe_source_id", $token);
            $payment->setStatus(\Magento\Payment\Model\Method\AbstractMethod::STATUS_SUCCESS)
                ->setShouldCloseParentTransaction(false)
                ->setIsTransactionClosed(false)
                ->setTransactionId($chargeId)
                ->setLastTransId($chargeId)
                ->setCcTransId($chargeId);
        } catch (\Stripe\Exception\ApiErrorException $e) {
            throw new LocalizedException(__($e->getMessage()));
        }
        return parent::authorize($payment, $amount);
    }

    /**
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param float $amount
     * @return ApplePay
     * @throws LocalizedException
     * @throws \Magenest\StripePayment\Exception\StripePaymentException
     */
    public function capture(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        try {
            $this->stripeHelper->initStripeApi();
            /** @var \Magento\Sales\Model\Order $order */
            $order = $payment->getOrder();
            $orderId = $order->getIncrementId();
            $this->_debug("applepay capture, orderid: " . $orderId);
            $chargeId = $payment->getAdditionalInformation("stripe_charge_id");
            if ($chargeId) {
                $charge = Stripe\Charge::retrieve($chargeId);
                $request = $this->stripeHelper->createCaptureRequest($order, $amount);
                $charge->capture($request);
                $this->_debug($charge->getLastResponse()->json);
                $transactionId = $charge->balance_transaction;
                $payment->setStatus(\Magento\Payment\Model\Method\AbstractMethod::STATUS_SUCCESS)
                    ->setShouldCloseParentTransaction(true)
                    ->setIsTransactionClosed(true)
                    ->setTransactionId($transactionId);
            } else {
                $token = $payment->getAdditionalInformation('payment_token');
                $request = $this->stripeHelper->createChargeRequest($order, $amount, $token, true, false);
                $charge = Stripe\Charge::create($request);
                $stripeAmount = $charge->amount;
                $this->stripeHelper->checkTransaction($payment, $stripeAmount);
                $this->_debug($charge->getLastResponse()->json);
                $transactionId = $charge->balance_transaction;
                $payment->setAmount($amount);
                $payment->setTransactionId($transactionId)
                    ->setIsTransactionClosed(false)
                    ->setShouldCloseParentTransaction(false)
                    ->setCcTransId($charge->id);
                $payment->setAdditionalInformation("stripe_charge_id", $charge->id);
                $payment->setAdditionalInformation("stripe_source_id", $token);
            }
        } catch (\Stripe\Exception\ApiErrorException $e) {
            throw new LocalizedException(__($e->getMessage()));
        }

        return parent::capture($payment, $amount);
    }

    /**
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param float $amount
     * @return ApplePay
     * @throws LocalizedException
     * @throws \Magenest\StripePayment\Exception\StripePaymentException
     */
    public function refund(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        try {
            $this->stripeHelper->initStripeApi();
            $chargeId = $payment->getAdditionalInformation("stripe_charge_id");
            if ($chargeId) {
                $refundReason = $this->request->getParam('refund_reason');
                $request = $this->stripeHelper->createRefundRequest($payment, $chargeId, $amount);
                if ($refundReason) {
                    $request['reason'] = $refundReason;
                }
                $refund = Stripe\Refund::create($request);
                $this->_debug($refund->getLastResponse()->json);
                $transactionId = $refund->balance_transaction;
                if ($transactionId) {
                    $payment->setTransactionId($transactionId);
                }
                $payment->setShouldCloseParentTransaction(0);
            } else {
                throw new \Magento\Framework\Exception\LocalizedException(
                    __('Charge doesn\'t exist. Please try again later.')
                );
            }
        } catch (\Stripe\Exception\ApiErrorException $e) {
            throw new LocalizedException(__($e->getMessage()));
        }

        return parent::refund($payment, $amount);
    }

    /**
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @return ApplePay
     * @throws LocalizedException
     * @throws \Magenest\StripePayment\Exception\StripePaymentException
     */
    public function void(\Magento\Payment\Model\InfoInterface $payment)
    {
        try {
            $this->stripeHelper->initStripeApi();
            $chargeId = $payment->getAdditionalInformation("stripe_charge_id");
            if ($chargeId) {
                $refundReason = $this->request->getParam('refund_reason');
                $request = $this->stripeHelper->createRefundRequest($payment, $chargeId);
                if ($refundReason) {
                    $request['reason'] = $refundReason;
                }
                $refund = Stripe\Refund::create($request);
                $this->_debug($refund->getLastResponse()->json);
                $transactionId = $refund->balance_transaction;
                if ($transactionId) {
                    $payment->setTransactionId($transactionId);
                }
                $payment->setShouldCloseParentTransaction(1);
                $payment->setIsTransactionClosed(1);
            } else {
                throw new \Magento\Framework\Exception\LocalizedException(
                    __('Charge doesn\'t exist. Please try again later.')
                );
            }
        } catch (\Stripe\Exception\ApiErrorException $e) {
            throw new LocalizedException(__($e->getMessage()));
        }
        return parent::void($payment);
    }

    /**
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @return ApplePay
     * @throws LocalizedException
     * @throws \Magenest\StripePayment\Exception\StripePaymentException
     */
    public function cancel(\Magento\Payment\Model\InfoInterface $payment)
    {
        $this->void($payment);
        return parent::cancel($payment);
    }

    /**
     * @param array|null $debugData
     */
    protected function _debug($debugData)
    {
        $debugData = $debugData ?: '';
        $this->stripeLogger->debug(var_export($debugData, true));
    }
}
