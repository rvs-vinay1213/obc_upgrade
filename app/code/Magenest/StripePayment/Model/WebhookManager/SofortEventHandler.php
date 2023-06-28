<?php

namespace Magenest\StripePayment\Model\WebhookManager;

use Magenest\StripePayment\Helper\Logger;
use Magenest\StripePayment\Model\ChargeFactory;
use Magento\Framework\DB\Transaction;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\OrderManagementInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Email\Sender\InvoiceSender;
use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\Service\InvoiceService;

class SofortEventHandler
{
    /**
     * @var string
     */
    protected $fieldModel = 'charge_id';
    /**
     * @var string
     */
    protected $status = 'succeeded';
    /**
     * @var ChargeFactory
     */
    protected $chargeFactory;
    /**
     * @var Logger
     */
    protected $stripeLogger;
    /**
     * @var OrderRepositoryInterface
     */
    protected $orderRepository;
    /**
     * @var OrderManagementInterface
     */
    protected $orderManagement;
    /**
     * @var InvoiceSender
     */
    protected $invoiceSender;
    /**
     * @var InvoiceService
     */
    protected $invoiceService;
    /**
     * @var Transaction
     */
    protected $transaction;

    /**
     * SofortEventHandler constructor.
     *
     * @param OrderRepositoryInterface $orderRepository
     * @param Logger $stripeLogger
     * @param OrderManagementInterface $orderManagement
     * @param InvoiceSender $invoiceSender
     * @param InvoiceService $invoiceService
     * @param Transaction $transaction
     * @param ChargeFactory $chargeFactory
     */
    public function __construct(
        OrderRepositoryInterface $orderRepository,
        Logger $stripeLogger,
        OrderManagementInterface $orderManagement,
        InvoiceSender $invoiceSender,
        InvoiceService $invoiceService,
        Transaction $transaction,
        ChargeFactory $chargeFactory
    ) {
        $this->orderRepository = $orderRepository;
        $this->stripeLogger = $stripeLogger;
        $this->orderManagement = $orderManagement;
        $this->invoiceSender = $invoiceSender;
        $this->invoiceService = $invoiceService;
        $this->transaction = $transaction;
        $this->chargeFactory = $chargeFactory;
    }

    /**
     * @param $charge
     * @throws LocalizedException
     */
    public function handleResponse($charge)
    {
        $this->handle($charge, $this->chargeFactory);
    }

    /**
     * @param $handle
     * @param $model
     * @return bool
     * @throws LocalizedException
     */
    public function handle($handle, $model)
    {
        /** @var Order $order */
        $status = $handle->status;
        $id = $handle->id;
        $handleModel = $model->create()->load($id, $this->fieldModel);
        if ($handleModel->getId()) {
            $orderId = $handleModel->getData('order_id');
            $order = $this->orderRepository->get($orderId);
            if ($status == $this->status) {
                if ($order->canInvoice()) {
                    $invoice = $this->invoiceService->prepareInvoice($order);
                    if (!$invoice->getTotalQty()) {
                        throw new LocalizedException(
                            __('You can\'t create an invoice without products.')
                        );
                    }
                    $invoice->setRequestedCaptureCase(Invoice::CAPTURE_ONLINE);
                    $invoice->register();
                    $invoice->getOrder()->setCustomerNoteNotify(!empty($data['send_email']));
                    $invoice->getOrder()->setIsInProcess(true);
                    $transaction = $this->transaction->addObject($invoice)->addObject($invoice->getOrder());
                    $transaction->save();
                    $this->invoiceSender->send($invoice);
                }
            }
            if (($status == 'failed') || ($status == 'canceled')) {
                $this->orderManagement->cancel($orderId);
            }
        } else {
            return false;
        }
        return true;
    }
}
