<?php

namespace Magenest\StripePayment\Model;

use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Model\Method\AbstractMethod;
use Stripe;

class AbstractPayment extends AbstractMethod
{
    /** Code */
    const CODE = 'magenest_stripe';
    /** @var string */
    protected $_code = self::CODE;
    /** @var bool */
    protected $_isGateway = true;
    /** @var bool */
    protected $_canAuthorize = false;
    /** @var bool */
    protected $_canCapture = true;
    /** @var bool */
    protected $_canCapturePartial = false;
    /** @var bool */
    protected $_canCaptureOnce = true;
    /** @var bool */
    protected $_canVoid = false;
    /** @var bool */
    protected $_canUseInternal = false;
    /** @var bool */
    protected $_canUseCheckout = true;
    /** @var bool */
    protected $_canRefund = true;
    /** @var bool */
    protected $_canRefundInvoicePartial = true;
    /** @var bool */
    protected $_isInitializeNeeded = false;

    /** @var bool */
    protected $_canOrder = false;
    /** @var \Magenest\StripePayment\Helper\Data */
    protected $stripeHelper;
    /** @var \Magenest\StripePayment\Helper\Logger */
    protected $stripeLogger;
    /** @var \Magenest\StripePayment\Helper\Config */
    protected $stripeConfig;
    /** @var \Magento\Framework\App\RequestInterface */
    protected $request;
    /** @var \Magento\Framework\Message\ManagerInterface */
    protected $_messageManager;
    /** @var \Magento\Store\Model\StoreManagerInterface */
    protected $storeManager;
    /** @var SourceFactory */
    protected $sourceFactory;

    /**
     * AbstractPayment constructor.
     *
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     * @param \Magenest\StripePayment\Helper\Config $stripeConfig
     * @param \Magenest\StripePayment\Helper\Data $stripeHelper
     * @param \Magenest\StripePayment\Helper\Logger $stripeLogger
     * @param \Magento\Framework\App\RequestInterface $request
     * @param \Magento\Store\Model\StoreManagerInterface $storeManagerInterface
     * @param SourceFactory $sourceFactory
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
        \Magenest\StripePayment\Helper\Config $stripeConfig,
        \Magenest\StripePayment\Helper\Data $stripeHelper,
        \Magenest\StripePayment\Helper\Logger $stripeLogger,
        \Magenest\StripePayment\Model\SourceFactory $sourceFactory,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Framework\App\RequestInterface $request,
        \Magento\Store\Model\StoreManagerInterface $storeManagerInterface,
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
        $this->stripeLogger = $stripeLogger;
        $this->stripeHelper = $stripeHelper;
        $this->stripeConfig = $stripeConfig;
        $this->sourceFactory = $sourceFactory;
        $this->_messageManager = $messageManager;
        $this->request = $request;
        $this->storeManager = $storeManagerInterface;
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
    }

    /**
     * Get Config Payment Action
     *
     * @return string
     */
    public function getConfigPaymentAction()
    {
        return parent::ACTION_AUTHORIZE_CAPTURE;
    }

    /**
     * Capture
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param float $amount
     * @return AbstractPayment
     * @throws LocalizedException
     * @throws Stripe\Exception\ApiErrorException
     * @throws \Magenest\StripePayment\Exception\StripePaymentDuplicateException
     * @throws \Magenest\StripePayment\Exception\StripePaymentException
     */
    public function capture(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        try {
            $this->stripeHelper->initStripeApi();
            $order = $payment->getOrder();
            $sourceId = $payment->getAdditionalInformation("stripe_source_id");
            $chargeRequest = $this->stripeHelper->createChargeRequest($order, $amount, $sourceId);
            $uid = $payment->getAdditionalInformation("stripe_uid");
            $charge = \Stripe\Charge::create($chargeRequest, [
                "idempotency_key" => $uid
            ]);
            $stripeAmount = $charge->amount;
            $this->stripeHelper->checkTransaction($payment, $stripeAmount);
            $this->_debug($charge->getLastResponse()->json);
            $chargeId = $charge->id;
            $payment->setAdditionalInformation("stripe_charge_id", $chargeId);
            $chargeStatus = $charge->status;
            if ($chargeStatus == 'succeeded') {
                $transactionId = $charge->balance_transaction;
                $payment->setTransactionId($transactionId)
                    ->setLastTransId($transactionId);
                $payment->setIsTransactionClosed(1);
                $payment->setShouldCloseParentTransaction(1);
            } else {
                throw new LocalizedException(
                    __("Payment failed")
                );
            }
            return parent::capture($payment, $amount);
        } catch (\Stripe\Error\Base $e) {
            if ($e->getStripeCode() == 'idempotency_key_in_use') {
                throw new \Magenest\StripePayment\Exception\StripePaymentDuplicateException(__($e->getMessage()));
            } else {
                throw new LocalizedException(__($e->getMessage()));
            }
        }
    }

    /**
     * Refund
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param float $amount
     * @return AbstractPayment
     * @throws LocalizedException
     * @throws Stripe\Exception\ApiErrorException
     * @throws \Magenest\StripePayment\Exception\StripePaymentException
     */
    public function refund(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        try {
            $this->stripeHelper->initStripeApi();
            $chargeId = $payment->getAdditionalInformation("stripe_charge_id");
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
            return parent::refund($payment, $amount);
        } catch (\Stripe\Error\Base $e) {
            throw new LocalizedException(__($e->getMessage()));
        }
    }

    /**
     * @param mixed $debugData
     */
    protected function _debug($debugData)
    {
        $this->stripeLogger->debug(var_export($debugData, true));
    }

    /**
     * @param string $currencyCode
     * @return bool
     */
    public function canUseForCurrency($currencyCode)
    {
        if (!in_array(strtolower($currencyCode), $this->getAcceptedCurrencyCodes())) {
            return false;
        }
        return true;
    }

    /**
     * @return string[]
     */
    protected function getAcceptedCurrencyCodes()
    {
        return ['aud', 'cad', 'eur', 'gbp', 'hkd', 'jpy', 'nzd', 'sgd', 'usd'];
    }

    /**
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param $amount
     * @return AbstractPayment
     * @throws LocalizedException
     * @throws Stripe\Exception\ApiErrorException
     * @throws \Magenest\StripePayment\Exception\StripePaymentDuplicateException
     * @throws \Magenest\StripePayment\Exception\StripePaymentException
     */
    public function checkCapture(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        try {
            $this->stripeHelper->initStripeApi();
            $order = $payment->getOrder();
            $chargeId = $payment->getAdditionalInformation("stripe_charge_id");
            if (!$chargeId) {
                throw new LocalizedException(
                    __("Charge doesn't exist. Please try again later.")
                );
            } else {
                $charge = Stripe\Charge::retrieve($chargeId);
            }
            $stripeAmount = $charge->amount;
            $this->stripeHelper->checkTransaction($payment, $stripeAmount);
            $chargeStatus = $charge->status;
            if ($chargeStatus == 'pending') {
                throw new LocalizedException(
                    __("Payment is pending. Cannot capture this payment")
                );
            }
            if ($chargeStatus == 'succeeded') {
                $order->setCanSendNewEmailFlag(true);
                $transactionId = $charge->balance_transaction;
                $payment->setTransactionId($transactionId)
                    ->setLastTransId($transactionId);
                $payment->setIsTransactionClosed(1);
                $payment->setShouldCloseParentTransaction(1);
            }
            if ($chargeStatus == 'failed') {
                throw new LocalizedException(
                    __("Payment failed")
                );
            }

            if (!$this->canCapture()) {
                throw new \Magento\Framework\Exception\LocalizedException(__('The capture action is not available.'));
            }

            return $this;
        } catch (\Stripe\Error\Base $e) {
            if ($e->getStripeCode() == 'idempotency_key_in_use') {
                throw new \Magenest\StripePayment\Exception\StripePaymentDuplicateException($e->getMessage());
            } else {
                throw new LocalizedException(__($e->getMessage()));
            }
        }
    }
}
