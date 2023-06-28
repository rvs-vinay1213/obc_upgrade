<?php
/**
 * Copyright © 2019 Magenest. All rights reserved.
 * See COPYING.txt for license details.
 *
 * Stripe extension
 * NOTICE OF LICENSE
 *
 * @category Magenest
 * @package Magenest_Stripe
 */

namespace Magenest\StripePayment\Controller\Checkout\Intents;

use Magenest\StripePayment\Helper\Data;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magenest\StripePayment\Helper\Logger;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;

/**
 * Class Response
 * @package Magenest\StripePayment\Controller\Checkout\Intents
 */
class Response extends Action
{
    /**
     *
     */
    const CARD_DECLINED = 'declined';

    /**
     * @var CheckoutSession
     */
    protected $_checkoutSession;
    /**
     * @var JsonFactory
     */
    protected $jsonFactory;
    /**
     * @var Data
     */
    protected $stripeHelper;
    /**
     * @var \Magento\Sales\Api\OrderRepositoryInterface
     */
    protected $orderRepository;
    /**
     * @var \Magenest\StripePayment\Controller\Checkout\Webhooks
     */
    protected $_webhooks;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var OrderSender
     */
    protected $orderSender;

    /**
     * Response constructor.
     * @param \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
     * @param CheckoutSession $chekoutSession
     * @param JsonFactory $jsonFactory
     * @param Data $stripeHelper
     * @param Logger $logger
     * @param OrderSender $orderSender
     * @param \Magenest\StripePayment\Controller\Checkout\Webhooks $webhooks
     * @param Context $context
     */
    public function __construct(
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        CheckoutSession $chekoutSession,
        JsonFactory $jsonFactory,
        Data $stripeHelper,
        Logger $logger,
        OrderSender $orderSender,
        \Magenest\StripePayment\Controller\Checkout\Webhooks $webhooks,
        Context $context
    ) {
        $this->jsonFactory      = $jsonFactory;
        $this->_checkoutSession = $chekoutSession;
        $this->stripeHelper     = $stripeHelper;
        $this->orderRepository  = $orderRepository;
        $this->_webhooks        = $webhooks;
        $this->logger = $logger;
        $this->orderSender = $orderSender;
        parent::__construct($context);
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\Result\Json|\Magento\Framework\Controller\ResultInterface
     * @throws \Magenest\StripePayment\Exception\StripePaymentException
     */
    public function execute()
    {
        try {
            $result = $this->jsonFactory->create();
            $order  = $this->_checkoutSession->getLastRealOrder();
            $data   = [] ;
            if ($this->_request->getParam(self::CARD_DECLINED)) {
                $this->cancelOrder($order, __('The order has been canceled because the card is declined.'));

                $data = [
                    'success' => false,
                    'error'   => [
                        'message' => __('Your card was declined')
                    ]
                ];
            } else {
                $this->stripeHelper->initStripeApi();
                if ($this->stripeHelper->continueProcessOrder($order->getId())) {
                    $data = [
                        'success' => true,
                        'error'   => false
                    ];
                    if ($order->getCanSendNewEmailFlag() && !$order->getEmailSent()) {
                        try {
                            $this->logger->debug("Email sent for order ".$order->getId());
                            $this->orderSender->send($order);
                        } catch (\Exception $e) {
                            $this->logger->critical($e->getMessage());
                        }
                    }
                }
            }

            return $result->setData($data);
        } catch (\Stripe\Exception\ApiErrorException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
            $this->_redirect('checkout/cart');
        }
    }

    /**
     * @var \Magento\Sales\Model\Order $order
     */
    public function cancelOrder($order, $comment = null)
    {
        if ($order) {
            $order->cancel();
            $order->setState(\Magento\Sales\Model\Order::STATE_CANCELED);
            $order->setStatus(\Magento\Sales\Model\Order::STATE_CANCELED);
            $payment = $order->getPayment();
            $payment
                ->setShouldCloseParentTransaction(true)
                ->setIsTransactionClosed(true);
            if ($comment) {
                $order->addStatusHistoryComment($comment)->setIsCustomerNotified(true);
            }
            $this->orderRepository->save($order);
            $this->_checkoutSession->restoreQuote();
        }
    }
}
