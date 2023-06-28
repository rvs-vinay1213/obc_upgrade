<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2023 Amasty (https://www.amasty.com)
 * @package One Step Checkout Core for Magento 2
 */

namespace Amasty\CheckoutCore\Observer\Admin\Order;

use Amasty\CheckoutCore\Api\Data\CustomFieldsConfigInterface;
use Amasty\CheckoutCore\Api\Data\OrderCustomFieldsInterface;
use Amasty\CheckoutCore\Model\ResourceModel\OrderCustomFields;
use Amasty\CheckoutCore\Model\ResourceModel\OrderCustomFields\Collection;
use Amasty\CheckoutCore\Model\ResourceModel\OrderCustomFields\CollectionFactory;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Api\OrderAddressRepositoryInterface;

class AddressSave implements ObserverInterface
{
    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var CollectionFactory
     */
    private $orderCustomFieldsCollection;

    /**
     * @var OrderCustomFields
     */
    private $orderCustomFieldsResource;

    /**
     * @var OrderAddressRepositoryInterface
     */
    private $orderAddressRepository;

    public function __construct(
        RequestInterface $request,
        CollectionFactory $orderCustomFieldsCollection,
        OrderCustomFields $orderCustomFieldsResource,
        OrderAddressRepositoryInterface $orderAddressRepository
    ) {
        $this->request = $request;
        $this->orderCustomFieldsCollection = $orderCustomFieldsCollection;
        $this->orderCustomFieldsResource = $orderCustomFieldsResource;
        $this->orderAddressRepository = $orderAddressRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function execute(Observer $observer)
    {
        $addressData = $this->request->getParams();
        $countOfCustomFields = CustomFieldsConfigInterface::COUNT_OF_CUSTOM_FIELDS;
        $index = CustomFieldsConfigInterface::CUSTOM_FIELD_INDEX;
        $data = [];

        for ($index; $index <= $countOfCustomFields; $index++) {
            if (isset($addressData['custom_field_' . $index])) {
                $customFieldIndex = 'custom_field_' . $index;
                /** @var Collection $orderCustomFieldsCollection */
                $orderCustomFieldsCollection = $this->orderCustomFieldsCollection->create();
                $orderCustomFieldsCollection->addFieldByOrderIdAndCustomField(
                    $observer->getOrderId(),
                    $customFieldIndex
                );

                if ($orderCustomFieldsCollection->getSize() === 0) {
                    continue;
                }

                $orderCustomField = $orderCustomFieldsCollection->getFirstItem();

                $orderAddress = $this->orderAddressRepository->get($addressData['address_id']);

                if ($orderAddress->getAddressType() === 'billing') {
                    $data[OrderCustomFieldsInterface::BILLING_VALUE] = $addressData[$customFieldIndex];
                } elseif ($orderAddress->getAddressType() === 'shipping') {
                    $data[OrderCustomFieldsInterface::SHIPPING_VALUE] = $addressData[$customFieldIndex];
                }

                $orderCustomField->addData($data);
                $this->orderCustomFieldsResource->save($orderCustomField);
            }
        }
    }
}
