<?php

namespace Rvs\CustomerGroup\Model\Category\Attribute\Source;

use Magento\Customer\Api\GroupRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;

class CustomerGroupSelect extends \Magento\Eav\Model\Entity\Attribute\Source\AbstractSource
{

    /**
     * @var Magento\Customer\Model\ResourceModel\Group\CollectionFactory
     */
    protected $groupCollection;

    /**
     * @param Magento\Customer\Model\ResourceModel\Group\CollectionFactory $groupCollection
     */
    public function __construct(
        \Magento\Customer\Model\ResourceModel\Group\CollectionFactory $groupCollection
    ) {
        $this->groupCollection = $groupCollection;
    }

    /**
     * Return array of customer groups
     *
     * @return array
     */
    public function getAllOptions()
    {
        $customerGroups = [];

        $customerGroups[] = [
            'label' => __("-- SELECT GROUP --"),
            'value' => '',
        ];

        /** @var GroupSearchResultsInterface $groups */
        $groups = $this->groupCollection->create();
        foreach ($groups as $group) {
            $customerGroups[] = [
                'label' => $group->getCustomerGroupCode(),
                'value' => $group->getCustomerGroupId(),
            ];
        }

        return $customerGroups;
    }
}