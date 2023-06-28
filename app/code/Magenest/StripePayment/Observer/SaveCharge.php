<?php
/**
 * Created by Magenest JSC.
 * Author: Jacob
 * Date: 10/01/2019
 * Time: 9:41
 */

namespace Magenest\StripePayment\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Model\Order;

class SaveCharge implements ObserverInterface
{
    /**
     * @var \Magenest\StripePayment\Model\ChargeFactory
     */
    protected $chargeFactory;
    /**
     * @var \Magenest\StripePayment\Model\SourceFactory
     */
    protected $sourceFactory;
    /**
     * @var \Magenest\StripePayment\Helper\Data
     */
    protected $stripeHelper;
    /**
     * @var \Magento\Framework\Serialize\SerializerInterface
     */
    protected $serializer;

    public function __construct(
        \Magenest\StripePayment\Model\ChargeFactory $chargeFactory,
        \Magenest\StripePayment\Model\SourceFactory $sourceFactory,
        \Magenest\StripePayment\Helper\Data $stripeHelper,
        \Magento\Framework\Serialize\SerializerInterface $serializer
    ) {
        $this->sourceFactory = $sourceFactory;
        $this->chargeFactory = $chargeFactory;
        $this->stripeHelper = $stripeHelper;
        $this->serializer = $serializer;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /**
         * @var \Magento\Sales\Model\Order $order
         * @var \Magento\Sales\Model\Order\Payment $payment
         */
        $order = $observer->getOrder();
        $orderId = $order->getEntityId();
        $payment = $order->getPayment();
        $methodName = $payment->getMethod();

        if (strpos($methodName, "magenest_stripe") !== false) {

            $this->changeOrderStatus($order);

            $chargeId = $payment->getAdditionalInformation('stripe_charge_id');
            if ($chargeId) {
                $chargeModel = $this->chargeFactory->create()->load($chargeId, "charge_id");
                if (!$chargeModel->getId()) {
                    $chargeModel->setData("charge_id", $chargeId);
                    $chargeModel->setData("order_id", $orderId);
                    $chargeModel->setData("method", $methodName);
                    $chargeModel->save();
                }
            }

            $sourceId = $payment->getAdditionalInformation('stripe_source_id');
            if ($sourceId) {
                $sourceModel = $this->sourceFactory->create()->load($sourceId);
                if (!$sourceModel->getId()) {
                    $sourceModel->setData("source_id", $sourceId);
                    $sourceModel->isObjectNew(true);
                }
                $sourceModel->addData([
                    'order_id' => $orderId,
                    'method' => $methodName
                ]);
                $sourceModel->save();
            }

        }
    }

    protected function changeOrderStatus($order)
    {
        $this->stripeHelper->initStripeApi();
        $event = \Stripe\Event::all(['limit' => 1]);
        $event = $this->serializer->unserialize($this->serializer->serialize($event));
        if (isset($event['data'][0]['type'])) {
            $eventStatus = $event['data'][0]['type'];
            if ($eventStatus == 'payment_intent.succeeded' || $eventStatus == 'payment_intent.amount_capturable_updated') {
                $orderState = Order::STATE_PROCESSING;
                $order->setState($orderState)->setStatus(Order::STATE_PROCESSING);
                $order->save();
            }
        }
    }
}
