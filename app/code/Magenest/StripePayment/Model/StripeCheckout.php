<?php

namespace Magenest\StripePayment\Model;

use Magenest\StripePayment\Exception\StripePaymentException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Model\Method\AbstractMethod;

class StripeCheckout extends AbstractMethod
{
    const CODE = 'magenest_stripe_checkout';
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
    protected $_isInitializeNeeded = true;
    protected $storeManagerInterface;
    protected $stripeCard;
    protected $_helper;
    protected $_config;
    protected $stripeLogger;

    /**
     * StripeCheckout constructor.
     * @param \Magento\Store\Model\StoreManagerInterface $storeManagerInterface
     * @param \Magenest\StripePayment\Helper\Data $dataHelper
     * @param StripePaymentMethod $stripePaymentMethod
     * @param \Magenest\StripePayment\Helper\Logger $stripeLogger
     * @param \Magenest\StripePayment\Helper\Config $config
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
        \Magento\Store\Model\StoreManagerInterface $storeManagerInterface,
        \Magenest\StripePayment\Helper\Data $dataHelper,
        StripePaymentMethod $stripePaymentMethod,
        \Magenest\StripePayment\Helper\Logger $stripeLogger,
        \Magenest\StripePayment\Helper\Config $config,
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
        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $paymentData,
            $scopeConfig,
            $logger,
            $resource,
            $resourceCollection,
            $data
        );
        $this->_helper = $dataHelper;
        $this->_config = $config;
        $this->stripeCard = $stripePaymentMethod;
        $this->stripeLogger = $stripeLogger;
        $this->storeManagerInterface = $storeManagerInterface;
    }

    public function initialize($paymentAction, $stateObject)
    {
        try {
            if (!class_exists(\Stripe\Stripe::class)) {
                throw new StripePaymentException(
                    __("Stripe PHP library was not installed")
                );
            }
            $payment = $this->getInfoInstance();
            $order = $payment->getOrder();
            $order->setCanSendNewEmailFlag(false);
            $seesionId = $this->createCheckoutSession($order);
            $payment->setAdditionalInformation("stripe_checkout_session_id", $seesionId);
            return parent::initialize($paymentAction, $stateObject);
        } catch (\Stripe\Exception\ApiErrorException $e) {
            throw new LocalizedException(__($e->getMessage()));
        } catch (StripePaymentException $e) {
            throw new LocalizedException(__($e->getMessage()));
        }
    }

    /**
     * @param \Magento\Payment\Model\InfoInterface|\Magento\Sales\Model\Order\Payment $payment
     * @param float $amount
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function authorize(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        $order = $payment->getOrder();
        $payment->setAdditionalInformation("stripe_checkout_finish", true);
        $chargeId = $payment->getAdditionalInformation("stripe_charge_id");
        $intent = \Stripe\PaymentIntent::retrieve($chargeId);
        $this->_debug($intent->getLastResponse()->json);
        $stripeAmount = $intent->amount;
        $this->_helper->checkTransaction($payment, $stripeAmount);
        $payment->setTransactionId($chargeId)
            ->setIsTransactionClosed(false)
            ->setShouldCloseParentTransaction(false);
        $payment->setMethod($this->_code);
        $order->setCanSendNewEmailFlag(true);
        return parent::authorize($payment, $amount);
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
            $this->_helper->initStripeApi();
            $chargeId = $payment->getAdditionalInformation("stripe_charge_id");
            $intent = \Stripe\PaymentIntent::retrieve($chargeId);
            $this->_debug($intent->getLastResponse()->json);
            if ($intent->capture_method == 'manual') {
                $amount = $this->_helper->getPaymentAmount($order, $amount);
                $intent->capture(['amount_to_capture' => $amount]);
                $payment->setMethod($this->_code);
                $order->setCanSendNewEmailFlag(true);
            } else {
                $stripeAmount = $intent->amount;
                $this->_helper->checkTransaction($payment, $stripeAmount);
            }
            $transactionId = $intent->charges->data[0]->balance_transaction;
            $payment->setAmount($amount);
            $payment->setTransactionId($transactionId)
                ->setIsTransactionClosed(false)
                ->setShouldCloseParentTransaction(false);
            $payment->setAdditionalInformation("stripe_checkout_finish", true);
        } catch (\Stripe\Exception\ApiErrorException $e) {
            throw new LocalizedException(__($e->getMessage()));
        }
        return parent::capture($payment, $amount);
    }

    /**
     * @param \Magento\Payment\Model\InfoInterface|\Magento\Sales\Model\Order\Payment $payment
     * @param float $amount
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function refund(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        try {
            $this->_helper->initStripeApi();
            /** @var \Magento\Sales\Model\Order $order */
            $order = $payment->getOrder();
            $amount = $this->_helper->getPaymentAmount($order, $amount);
            $chargeId = $payment->getAdditionalInformation("stripe_charge_id");
            $intent = \Stripe\PaymentIntent::retrieve($chargeId);
            \Stripe\Refund::create([
                'amount' => $amount,
                'payment_intent' => $chargeId,
            ]);
            $transactionId = $intent->charges->data[0]->balance_transaction;
            $payment->setTransactionId($transactionId);
            $payment->setShouldCloseParentTransaction(0);
        } catch (\Stripe\Exception\ApiErrorException $e) {
            throw new LocalizedException(__($e->getMessage()));
        }
    }

    /**
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @return StripeCheckout
     * @throws LocalizedException
     * @throws StripePaymentException
     */
    public function void(\Magento\Payment\Model\InfoInterface $payment)
    {
        try {
            $this->_helper->initStripeApi();
            $chargeId = $payment->getAdditionalInformation("stripe_charge_id");
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
     * @return StripeCheckout
     * @throws LocalizedException
     * @throws StripePaymentException
     */
    public function cancel(\Magento\Payment\Model\InfoInterface $payment)
    {
        try {
            $this->_helper->initStripeApi();
            $chargeId = $payment->getAdditionalInformation("stripe_charge_id");
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
     * @param array $debugData
     */
    protected function _debug($debugData)
    {
        $this->stripeLogger->debug(var_export($debugData, true));
    }

    /**
     * @param $order
     * @return string
     * @throws StripePaymentException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Stripe\Exception\ApiErrorException
     */
    public function createCheckoutSession($order)
    {
        $paymentAction = $this->_config->getStripeCheckoutPaymentAction();
        $isCollectBilling = $this->_config->isStripeCheckoutCollectBilling();
        $submitType = $this->_config->getStripeCheckoutSubmitType();
        $this->_helper->initStripeApi();

        $postData = [
            'customer_email' => $order->getCustomerEmail(),
            'billing_address_collection' => $isCollectBilling?'required':'auto',
            'submit_type' => $submitType,
            'payment_method_types' => ['card'],
            'line_items' => $this->getLineItem($order),
            'payment_intent_data' => [
                'capture_method' => ($paymentAction=='authorize_capture')?'automatic':'manual',
                'description' => $this->_helper->getPaymentDescription($order),
                'metadata' => $this->_helper->getPaymentMetaData($order),
            ],
            'success_url' => $this->storeManagerInterface->getStore()->getBaseUrl() . "stripe/checkout_checkout/success",
            'cancel_url' => $this->storeManagerInterface->getStore()->getBaseUrl() . "stripe/checkout_checkout/cancel",
        ];
        if ($this->_config->sendMailCustomer()) {
            $postData['payment_intent_data']['receipt_email'] = $order->getCustomerEmail();
        }
        if ($order->getIsNotVirtual()) {
            $postData['payment_intent_data']['shipping'] = $this->_helper->getShippingInformation($order);
        }
        $session = \Stripe\Checkout\Session::create($postData);
        $sourceId = $session->payment_intent;
        $payment = $order->getPayment();
        $payment->setAdditionalInformation("stripe_charge_id", $sourceId);
        return $session->id;
    }

    private function getLineItem($order)
    {
        $checkoutDescription = $this->_config->getStripeCheckoutDescription();
        $checkoutImageUrl = $this->_config->getStripeCheckoutImageUrl();
        $checkoutTitle = $this->_config->getStripeCheckoutTitle();
        $amount = $this->_helper->getPaymentAmount($order, $order->getBaseGrandTotal());
        $arrayResult = [
            [
                'name' => $checkoutTitle,
                'amount' => $amount,
                'currency' => $order->getBaseCurrencyCode(),
                'quantity' => 1,
            ]
        ];
        if ($checkoutDescription) {
            $arrayResult[0]['description'] = $checkoutDescription;
        }
        if ($checkoutImageUrl) {
            $arrayResult[0]['images'] = [$checkoutImageUrl];
        }
        return $arrayResult;
    }
}
