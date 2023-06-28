<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2023 Amasty (https://www.amasty.com)
 * @package One Step Checkout Core for Magento 2
 */

namespace Amasty\CheckoutCore\Plugin\Sales\Admin\Order\Address;

use Amasty\CheckoutCore\Api\Data\CustomFieldsConfigInterface;
use Amasty\CheckoutCore\Api\Data\OrderCustomFieldsInterface;
use Amasty\CheckoutCore\Model\ResourceModel\OrderCustomFields\CollectionFactory;

class Form
{
    /**
     * @var CollectionFactory
     */
    private $orderCustomFieldsCollection;

    public function __construct(
        CollectionFactory $orderCustomFieldsCollection
    ) {
        $this->orderCustomFieldsCollection = $orderCustomFieldsCollection;
    }

    /**
     * @param \Magento\Sales\Block\Adminhtml\Order\Address\Form $subject
     * @param array $formValues
     *
     * @return array
     */
    public function afterGetFormValues(\Magento\Sales\Block\Adminhtml\Order\Address\Form $subject, $formValues)
    {
        $countOfCustomFields = CustomFieldsConfigInterface::COUNT_OF_CUSTOM_FIELDS;
        $index = CustomFieldsConfigInterface::CUSTOM_FIELD_INDEX;

        for ($index; $index <= $countOfCustomFields; $index++) {
            /** @var \Amasty\CheckoutCore\Model\ResourceModel\OrderCustomFields\Collection $orderCustomFieldsCollection */
            $orderCustomFieldsCollection = $this->orderCustomFieldsCollection->create();
            $orderCustomFieldsCollection->addFieldByOrderIdAndCustomField(
                $formValues['parent_id'],
                'custom_field_' . $index
            );
            $orderCustomFieldsData = $orderCustomFieldsCollection->getFirstItem()->getData();

            if ($orderCustomFieldsData) {
                $formValues[$orderCustomFieldsData[OrderCustomFieldsInterface::NAME]] =
                    $orderCustomFieldsData[$formValues['address_type'] . '_value'];
            }
        }

        return $formValues;
    }
}
