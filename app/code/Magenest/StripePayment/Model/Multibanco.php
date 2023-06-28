<?php

namespace Magenest\StripePayment\Model;

use Magento\Framework\Exception\LocalizedException;
use Stripe;

class Multibanco extends AbstractPayment
{
    const CODE = 'magenest_stripe_multibanco';
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
    protected $_canOrder = false;
    protected $_infoBlockType = \Magenest\StripePayment\Block\Info\Multibanco::class;

    /**
     * @param string $paymentAction
     * @param object $stateObject
     * @return Multibanco
     * @throws LocalizedException
     * @throws \Magenest\StripePayment\Exception\StripePaymentException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function initialize($paymentAction, $stateObject)
    {
        /**
         * @var \Magento\Sales\Model\Order $order
         */
        try {
            if (!class_exists(\Stripe\Stripe::class)) {
                throw new StripePaymentException(
                    __("Stripe PHP library was not installed")
                );
            }
            $this->stripeHelper->initStripeApi();
            $payment = $this->getInfoInstance();
            $order = $payment->getOrder();
            $returnUrl = $this->storeManager->getStore()->getBaseUrl() . "stripe/checkout_multibanco/response";
            $request = $this->stripeHelper->getPaymentSource($order, "multibanco");
            $request = array_merge(
                $request,
                [
                    "redirect" => [
                        "return_url" => $returnUrl
                    ],
                ]
            );
            $source = Stripe\Source::create($request);
            $this->_debug($source->getLastResponse()->json);
            $redirectUrl = $source->redirect->url;
            $sourceId = $source->id;
            $clientSecret = $source->client_secret;
            $reference = $source->multibanco->reference;
            $entity = $source->multibanco->entity;
            $sourceAdditionalInformation[] = [
                'label' => "Payment Method",
                'value' => "Multibanco"
            ];
            $order->setCanSendNewEmailFlag(false);
            $payment->setAdditionalInformation("stripe_source_additional_information", json_encode($sourceAdditionalInformation));
            $payment->setAdditionalInformation("stripe_multibanco_reference", $reference);
            $payment->setAdditionalInformation("stripe_multibanco_entity", $entity);
            $payment->setAdditionalInformation("stripe_client_secret", $clientSecret);
            $payment->setAdditionalInformation("stripe_source_id", $sourceId);
            $payment->setAdditionalInformation("stripe_redirect_url", $redirectUrl);
            return parent::initialize($paymentAction, $stateObject);
        } catch (\Stripe\Exception\ApiErrorException $e) {
            throw new LocalizedException(__($e->getMessage()));
        } catch (\Magenest\StripePayment\Exception\StripePaymentException $e) {
            throw new LocalizedException(__($e->getMessage()));
        }
    }

    /**
     * @return string[]
     */
    protected function getAcceptedCurrencyCodes()
    {
        return ['eur'];
    }
}
