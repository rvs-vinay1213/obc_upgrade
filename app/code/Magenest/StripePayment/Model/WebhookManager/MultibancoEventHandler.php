<?php
/**
 * Created by Magenest JSC.
 * Author: Jacob
 * Date: 10/01/2019
 * Time: 9:41
 */

namespace Magenest\StripePayment\Model\WebhookManager;

use Magenest\StripePayment\Helper\Logger;
use Magenest\StripePayment\Model\ChargeFactory;
use Magenest\StripePayment\Model\SourceFactory;
use Magento\Framework\DB\Transaction;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\OrderManagementInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order\Email\Sender\InvoiceSender;
use Magento\Sales\Model\Service\InvoiceService;

class MultibancoEventHandler extends SofortEventHandler
{
    /**
     * @var null
     */
    protected $fieldModel = null;
    /**
     * @var string
     */
    protected $status = 'chargeable';
    /**
     * @var SourceFactory
     */
    protected $sourceFactory;

    /**
     * MultibancoEventHandler constructor.
     * @param OrderRepositoryInterface $orderRepository
     * @param Logger $stripeLogger
     * @param OrderManagementInterface $orderManagement
     * @param InvoiceSender $invoiceSender
     * @param InvoiceService $invoiceService
     * @param Transaction $transaction
     * @param ChargeFactory $chargeFactory
     * @param SourceFactory $sourceFactory
     */
    public function __construct(
        OrderRepositoryInterface $orderRepository,
        Logger $stripeLogger,
        OrderManagementInterface $orderManagement,
        InvoiceSender $invoiceSender,
        InvoiceService $invoiceService,
        Transaction $transaction,
        ChargeFactory $chargeFactory,
        SourceFactory $sourceFactory
    ) {
        $this->sourceFactory = $sourceFactory;
        parent::__construct(
            $orderRepository,
            $stripeLogger,
            $orderManagement,
            $invoiceSender,
            $invoiceService,
            $transaction,
            $chargeFactory
        );
    }

    /**
     * @param $source
     * @throws LocalizedException
     */
    public function handleResponse($source)
    {
        $this->handle($source, $this->sourceFactory);
    }
}
