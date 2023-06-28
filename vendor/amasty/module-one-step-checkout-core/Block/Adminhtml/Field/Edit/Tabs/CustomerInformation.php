<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2023 Amasty (https://www.amasty.com)
 * @package One Step Checkout Core for Magento 2
 */

namespace Amasty\CheckoutCore\Block\Adminhtml\Field\Edit\Tabs;

use Magento\Store\Model\ScopeInterface;
use Amasty\CheckoutCore\Model\Field;
use Amasty\CheckoutCore\Api\Data\ManageCheckoutTabsInterface;
use Amasty\CheckoutCore\Block\Adminhtml\Field\Edit\Tabs\AbstractTab;

class CustomerInformation extends AbstractTab
{
    /**
     * @inheritdoc
     */
    public function getTabLabel()
    {
        return __('Customer Information');
    }

    /**
     * @inheritdoc
     */
    protected function _prepareForm()
    {
        $storeId = $this->_request->getParam(ScopeInterface::SCOPE_STORE, Field::DEFAULT_STORE_ID);
        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->formManagement->prepareForm(ManageCheckoutTabsInterface::CUSTOMER_INFO_TAB, $storeId);

        $this->setForm($form);

        return parent::_prepareForm();
    }
}
