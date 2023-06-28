<?php

namespace Magenest\StripePayment\Model;

use Magenest\StripePayment\Helper\Data as DataHelper;
use Magento\Framework\Api\AttributeValueFactory;
use Magento\Framework\Api\ExtensionAttributesFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\Context;
use Magento\Framework\Module\ModuleListInterface;
use Magento\Framework\Registry;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Payment\Helper\Data;
use Magento\Payment\Model\Method\Logger;

class StripeDirectApi extends \Magento\Payment\Model\Method\Cc
{
    const CODE = 'magenest_stripe';
    protected $_code = self::CODE;
    protected $_isGateway = true;
    protected $_canCapture = true;
    protected $_canCapturePartial = true;
    protected $_canRefund = true;
    protected $_canRefundInvoicePartial = true;
    protected $_canVoid = true;
    protected $_canAuthorize = true;

    protected $_helper;
    protected $stripeLogger;
    protected $stripeCard;
    protected $stripeConfig;

    /**
     * StripeDirectApi constructor.
     * @param Context $context
     * @param Registry $registry
     * @param ExtensionAttributesFactory $extensionFactory
     * @param AttributeValueFactory $customAttributeFactory
     * @param Data $paymentData
     * @param ScopeConfigInterface $scopeConfig
     * @param Logger $logger
     * @param ModuleListInterface $moduleList
     * @param TimezoneInterface $localeDate
     * @param DataHelper $dataHelper
     * @param \Magenest\StripePayment\Helper\Logger $stripeLogger
     * @param StripePaymentMethod $stripePaymentMethod
     * @param \Magenest\StripePayment\Helper\Config $stripeConfig
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        ExtensionAttributesFactory $extensionFactory,
        AttributeValueFactory $customAttributeFactory,
        Data $paymentData,
        ScopeConfigInterface $scopeConfig,
        Logger $logger,
        ModuleListInterface $moduleList,
        TimezoneInterface $localeDate,
        DataHelper $dataHelper,
        \Magenest\StripePayment\Helper\Logger $stripeLogger,
        \Magenest\StripePayment\Model\StripePaymentMethod $stripePaymentMethod,
        \Magenest\StripePayment\Helper\Config $stripeConfig,
        $data = []
    ) {
        $this->_helper = $dataHelper;
        $this->stripeLogger = $stripeLogger;
        $this->stripeCard = $stripePaymentMethod;
        $this->stripeConfig = $stripeConfig;
        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $paymentData,
            $scopeConfig,
            $logger,
            $moduleList,
            $localeDate,
            null,
            null,
            $data
        );
    }

    /**
     * @param \Magento\Framework\DataObject $data
     * @return $this|StripeDirectApi
     * @throws LocalizedException
     */
    public function assignData(\Magento\Framework\DataObject $data)
    {
        $infoInstance = $this->getInfoInstance();
        $additionalData = $data->getData('additional_data');
        parent::assignData($data);
        if ($this->_appState->getAreaCode() == 'adminhtml') {
            $stripeResponse = isset($additionalData['stripe_response']) ? $additionalData['stripe_response'] : "";
            $response = json_decode($stripeResponse, true);
            $customerId = isset($additionalData['customer_id']) ? $additionalData['customer_id'] : "";
            $cardId = isset($additionalData['cardId']) ? $additionalData['cardId'] : "";
            if ($response) {
                $sourceId = isset($response['id']) ? $response['id'] : false;
            } else {
                $sourceId = $this->stripeCard->addPaymentInfoData($this->getInfoInstance(), $cardId, $customerId);
            }
            $customerId = isset($additionalData['customer_id']) ? $additionalData['customer_id'] : "";
            $infoInstance->setAdditionalInformation('source_id', $sourceId);
            $infoInstance->setAdditionalInformation('customer_id', $customerId);
            if ($sourceId) {
                $infoInstance->setAdditionalInformation('db_source', true);
            }
            return $this;
        }

        return $this;
    }

    /**
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param float $amount
     * @return StripeDirectApi|void
     * @throws LocalizedException
     * @throws \Magenest\StripePayment\Exception\StripePaymentException
     */
    public function authorize(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        try {
            $this->_helper->initStripeApi();
            $this->_debug("authorize action");
            /** @var \Magento\Sales\Model\Order $order */
            $order = $payment->getOrder();
            $paymentToken = $this->_helper->getDirectSource($order);
            $dbSource = $payment->getAdditionalInformation('db_source');
            $request = $this->_helper->createChargeRequest(
                $order,
                $amount,
                $paymentToken,
                false,
                $dbSource,
                null
            );
            $charge = \Stripe\Charge::create($request);
            $stripeAmount = $charge->amount;
            $this->_helper->checkTransaction($payment, $stripeAmount);
            $this->_debug($charge->getLastResponse()->json);
            $payment->setTransactionId($charge->id)
                ->setIsTransactionClosed(false)
                ->setShouldCloseParentTransaction(false)
                ->setCcTransId($charge->id);
            $payment->setAdditionalInformation("stripe_charge_id", $charge->id);
        } catch (\Stripe\Exception\ApiErrorException $e) {
            throw new LocalizedException(__($e->getMessage()));
        }
    }

    /**
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param float $amount
     * @return StripeDirectApi|void
     * @throws LocalizedException
     * @throws \Magenest\StripePayment\Exception\StripePaymentException
     */
    public function capture(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        try {
            $this->_helper->initStripeApi();
            $this->_debug("capture action");
            /** @var \Magento\Sales\Model\Order $order */
            $order = $payment->getOrder();
            $chargeId = $payment->getAdditionalInformation("stripe_charge_id");
            if ($chargeId) {
                $charge = \Stripe\Charge::retrieve($chargeId);
                $request = $this->_helper->createCaptureRequest($order, $amount);
                $charge->capture($request);
                $transactionId = $charge->balance_transaction;
                $payment->setStatus(\Magento\Payment\Model\Method\AbstractMethod::STATUS_SUCCESS)
                    ->setShouldCloseParentTransaction(true)
                    ->setIsTransactionClosed(true)
                    ->setTransactionId($transactionId);
            } else {
                $paymentToken = $this->_helper->getDirectSource($order);
                $dbSource = $payment->getAdditionalInformation('db_source');
                $request = $this->_helper->createChargeRequest(
                    $order,
                    $amount,
                    $paymentToken,
                    true,
                    $dbSource,
                    null
                );
                $charge = \Stripe\Charge::create($request);
                $stripeAmount = $charge->amount;
                $this->_helper->checkTransaction($payment, $stripeAmount);
                $payment->setTransactionId($charge->balance_transaction)
                    ->setIsTransactionClosed(false)
                    ->setShouldCloseParentTransaction(false)
                    ->setCcTransId($charge->id);
                $this->_debug($charge->getLastResponse()->json);
                $payment->setAdditionalInformation("stripe_charge_id", $charge->id);
            }
        } catch (\Stripe\Exception\ApiErrorException $e) {
            throw new LocalizedException(__($e->getMessage()));
        }
    }

    /**
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param float $amount
     * @return StripeDirectApi|StripePaymentMethod
     * @throws LocalizedException
     */
    public function refund(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        return $this->stripeCard->refund($payment, $amount);
    }

    /**
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @return StripeDirectApi|StripePaymentMethod
     * @throws LocalizedException
     */
    public function void(\Magento\Payment\Model\InfoInterface $payment)
    {
        return $this->stripeCard->void($payment);
    }

    /**
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @return StripeDirectApi|StripePaymentMethod
     * @throws LocalizedException
     */
    public function cancel(\Magento\Payment\Model\InfoInterface $payment)
    {
        return $this->stripeCard->cancel($payment);
    }

    /**
     * @param array|string $debugData
     */
    protected function _debug($debugData)
    {
        $this->stripeLogger->debug(var_export($debugData, true));
    }

    /**
     * @return StripeDirectApi|\Magento\Payment\Model\Method\AbstractMethod
     * @throws LocalizedException
     */
    public function validate()
    {
        return \Magento\Payment\Model\Method\AbstractMethod::validate();
    }

    /**
     * @return bool|mixed
     */
    public function canUseInternal()
    {
        return $this->getConfigData('active_moto');
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
     * @return bool
     */
    public function hasVerification()
    {
        return true;
    }
}
