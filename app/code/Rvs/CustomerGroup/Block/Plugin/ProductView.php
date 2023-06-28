<?php

namespace Rvs\CustomerGroup\Block\Plugin;

use Magento\CatalogInventory\Api\StockRegistryInterface;

class ProductView extends \Magento\CatalogInventory\Block\Plugin\ProductView
{
    /**
     * @var StockRegistryInterface
     */
    private $stockRegistry;

    protected $_coreSession;

    protected $_customerRepositoryInterface;

    protected $customerGroupCollection;

    /**
     * @param StockRegistryInterface $stockRegistry
     */
    public function __construct(
        StockRegistryInterface $stockRegistry,
        \Magento\Framework\Session\SessionManagerInterface $coreSession,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepositoryInterface,
        \Magento\Customer\Model\ResourceModel\Group\CollectionFactory $customerGroupCollection
    ) {
        $this->stockRegistry = $stockRegistry;
        $this->_coreSession = $coreSession;
        $this->_customerRepositoryInterface = $customerRepositoryInterface;
        $this->customerGroupCollection = $customerGroupCollection;
    }

    /**
     * @param \Magento\Catalog\Block\Product\View $block
     * @param array $validators
     * @return array
     */
    public function afterGetQuantityValidators(
        \Magento\Catalog\Block\Product\View $block,
        array $validators
    ) {
        $stockItem = $this->stockRegistry->getStockItem(
            $block->getProduct()->getId(),
            $block->getProduct()->getStore()->getWebsiteId()
        );

        $params = [];

        $this->_coreSession->start();
        $customerId = $this->_coreSession->getLoggedInCustomerId();

        if(isset($customerId))
        {
            $customer = $this->_customerRepositoryInterface->getById($customerId);

            $groupData = $this->customerGroupCollection->create()->addFieldToFilter('customer_group_id', $customer->getGroupId())->getFirstItem();

            $minQtyIgnore = $groupData->getIgnoreMinQty();

            if(!isset($minQtyIgnore) || $minQtyIgnore == 0)
            {
                $params['minAllowed']  = (float)$stockItem->getMinSaleQty();
            }
        }
        else
        {
            $params['minAllowed']  = (float)$stockItem->getMinSaleQty();
        }

        if ($stockItem->getQtyMaxAllowed()) {
            $params['maxAllowed'] = $stockItem->getQtyMaxAllowed();
        }
        if ($stockItem->getQtyIncrements() > 0) {
            $params['qtyIncrements'] = (float)$stockItem->getQtyIncrements();
        }
        $validators['validate-item-quantity'] = $params;

        return $validators;
    }
}
