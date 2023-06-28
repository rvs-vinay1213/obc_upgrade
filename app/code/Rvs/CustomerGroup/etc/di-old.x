<?xml version="1.0"?>

<config xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="urn:magento:framework:ObjectManager/etc/config.xsd">
	<preference for="Magento\Customer\Model\Data\Group" type="Rvs\CustomerGroup\Model\Data\Group" />
    <preference for="Magento\Customer\Controller\Adminhtml\Group\Save" type="Rvs\CustomerGroup\Controller\Adminhtml\Group\Save" />
    <preference for="Magento\Customer\Block\Adminhtml\Group\Edit\Form" type="Rvs\CustomerGroup\Block\Adminhtml\Group\Edit\Form" />
    <preference for="Magento\Customer\Model\ResourceModel\GroupRepository" type="Rvs\CustomerGroup\Model\ResourceModel\GroupRepository" />
    <preference for="Magento\CatalogInventory\Model\StockStateProvider" type="Rvs\CustomerGroup\Model\StockStateProvider" />
    <preference for="Magento\Theme\Block\Html\Topmenu" type="Rvs\CustomerGroup\Block\Html\Topmenu" />
	<type name="Magento\Catalog\Block\Product\View">
	    <plugin name="quantityValidators" type="Rvs\CustomerGroup\Block\Plugin\ProductView" sortOrder="1" />
	</type>
	<type name="Magento\Theme\Block\Html\Topmenu">
        <plugin name="catalogTopmenu" type="Rvs\CustomerGroup\Plugin\Block\Topmenu" />
    </type>
</config>