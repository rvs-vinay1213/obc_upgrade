<?php
/**
 * Copyright © 2021 Magenest. All rights reserved.
 * See COPYING.txt for license details.
 *
 * Magenest_Stripe Solutions extension
 * NOTICE OF LICENSE
 *
 * @author SamNguyen
 * @category Magenest
 * @package Magenest_Stripe Solutions
 */

namespace Magenest\StripePayment\Controller\Checkout;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Sales\Api\OrderManagementInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;

class Webhooks extends Action implements CsrfAwareActionInterface
{
    protected $dataHelper;

    protected $stripeHelper;

    protected $sofortEventHandler;

    protected $multibancoEventHandler;

    protected $stripeLogger;

    protected $instanceHandler = [
        'alipay' => \Magenest\StripePayment\Controller\Checkout\Alipay\Response::class,
        'three_d_secure' => \Magenest\StripePayment\Controller\Checkout\Secure\Response::class,
        'bancontact' => \Magenest\StripePayment\Controller\Checkout\Bancontact\Response::class,
        'eps' => \Magenest\StripePayment\Controller\Checkout\Eps\Response::class,
        'giropay' => \Magenest\StripePayment\Controller\Checkout\Giropay\Response::class,
        'ideal' => \Magenest\StripePayment\Controller\Checkout\Ideal\Response::class,
        'multibanco' => \Magenest\StripePayment\Controller\Checkout\Multibanco\Response::class,
        'p24' => \Magenest\StripePayment\Controller\Checkout\Przelewy\Response::class,
        'sofort' => \Magenest\StripePayment\Controller\Checkout\Sofort\Response::class,
        'sepa_debit' => \Magenest\StripePayment\Controller\Checkout\Sepa\Response::class,
        'wechat' => \Magenest\StripePayment\Controller\Checkout\WeChatPay\Response::class,
        'default' => \Magenest\StripePayment\Controller\Checkout\Response::class
    ];

    protected $chargeFactory;

    protected $orderRepository;

    protected $orderManagement;
    protected $fileDriver;
    protected $httpRequest;

    public function __construct(
        Context $context,
        \Magenest\StripePayment\Helper\Data $dataHelper,
        \Magenest\StripePayment\Helper\Config $stripeHelper,
        \Magenest\StripePayment\Model\WebhookManager\SofortEventHandler $sofortEventHandler,
        \Magenest\StripePayment\Model\WebhookManager\MultibancoEventHandler $multibancoEventHandler,
        \Magenest\StripePayment\Helper\Logger $stripeLogger,
        \Magenest\StripePayment\Model\ChargeFactory $chargeFactory,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        OrderManagementInterface $orderManagement,
        File $fileDriver,
        \Magento\Framework\App\Request\Http $httpRequest
    ) {
        $this->dataHelper = $dataHelper;
        $this->stripeHelper = $stripeHelper;
        $this->sofortEventHandler = $sofortEventHandler;
        $this->multibancoEventHandler = $multibancoEventHandler;
        $this->stripeLogger = $stripeLogger;
        $this->chargeFactory = $chargeFactory;
        $this->orderRepository = $orderRepository;
        $this->orderManagement = $orderManagement;
        $this->fileDriver = $fileDriver;
        $this->httpRequest = $httpRequest;
        parent::__construct($context);
    }

    public function execute()
    {
        $this->dataHelper->initStripeApi();
        $endpoint_secret = $this->stripeHelper->getWebhooksSecret();
        $payload = $this->fileDriver->fileGetContents("php://input");
        $sig_header = $this->httpRequest->getServer('HTTP_STRIPE_SIGNATURE', '');
        $event = null;
        try {
            $event = \Stripe\Webhook::constructEvent(
                $payload, $sig_header, $endpoint_secret
            );
            $object = $event->object;

            if ($object == "event") {
                $response = $event->data->object;
                $this->stripeLogger->debug("Webhook_" . $response);
                $objectType = $response->object;
                $this->_eventManager->dispatch('stripe_webhooks_event', ['event' => $event]);
                if ($objectType == 'charge') {
                    $chargeStatus = $response->status;
                    $sourceType = isset($response->source->type) ? $response->source->type : "";
                    if ($chargeStatus == 'succeeded') {
                        if ($sourceType == 'sofort') {
                            $obj = $this->_objectManager->get($this->instanceHandler['sofort']);
                            if (!$obj->processCharge($response)) {
                                http_response_code(400);

                                return;
                            }
                        }
                        if ($sourceType == 'sepa_debit') {
                            $obj = $this->_objectManager->get($this->instanceHandler['sepa_debit']);
                            if (!$obj->processCharge($response)) {
                                http_response_code(400);
                                return;
                            }
                        }
                    }
                    if ($chargeStatus == 'failed') {
                        if ($sourceType == 'sofort') {
                            $sourceId = $response->source->id;
                            $order = $this->dataHelper->getOrderBySource($sourceId);
                            $obj = $this->_objectManager->get($this->instanceHandler['sofort']);
                            $obj->cancelOrder($order, "Payment authentication fail");
                        }
                        if ($sourceType == 'sepa_debit') {
                            $sourceId = $response->source->id;
                            $order = $this->dataHelper->getOrderBySource($sourceId);
                            $obj = $this->_objectManager->get($this->instanceHandler['sepa_debit']);
                            $obj->cancelOrder($order, "Payment authentication fail");
                        }
                    }
                }
                if ($objectType == 'source') {
                    $sourceStatus = isset($response->status) ? $response->status : "";
                    $sourceType = isset($response->type) ? $response->type : "";
                    if (array_key_exists($sourceType, $this->instanceHandler)) {
                        if ($sourceType == 'three_d_secure') {
                            return;
                        }
                        $class = $this->instanceHandler[$sourceType];
                    } else {
                        $class = $this->instanceHandler['default'];
                    }
                    $obj = $this->_objectManager->get($class);
                    $order = $this->dataHelper->getOrderBySource($response->id);
                    if ($sourceStatus == 'canceled') {
                        $obj->cancelOrder($order, "Payment was expired");
                    }
                    if ($sourceStatus == 'failed') {
                        $obj->cancelOrder($order, "Payment authentication fail");
                    }
                    if ($sourceStatus == 'chargeable' && !$obj->processOrder($response)) {
                        http_response_code(400);
                        return;
                    }
                }
                if (($event->type == 'checkout.session.completed') && ($objectType == "checkout.session")) {
                    $chargeId = $response->payment_intent;
                    if ($chargeId) {
                        $chargeModel = $this->chargeFactory->create()->load($chargeId, "charge_id");
                        if ($chargeModel->getId()) {
                            $orderId = $chargeModel->getData('order_id');
                            $this->dataHelper->continueProcessOrder($orderId);
                            $this->dataHelper->sendEmailOrderConfirm($orderId);
                        }
                    }
                }
            }
        } catch (\UnexpectedValueException $e) {
            $this->stripeLogger->info('UnexpectedValueException ' . $e->getMessage());
            // Invalid payload
            http_response_code(400);
            return;
        } catch (\Stripe\Error\SignatureVerification $e) {
            $this->stripeLogger->info('SignatureVerification ' . $e->getMessage());
            // Invalid signature
            http_response_code(400);
            return;
        } catch (\Stripe\Exception\ApiErrorException $e) {
            $this->stripeLogger->info('Base ' . $e->getMessage());
            http_response_code(400);
            return;
        } catch (\Exception $e) {
            $this->stripeLogger->info('Exception ' . $e->getMessage());
            $this->dataHelper->debugException($e);
            http_response_code(400);
            return;
        }

        http_response_code(200);
    }

    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }

    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }
}
