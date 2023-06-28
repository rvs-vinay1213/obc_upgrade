<?php

namespace Rvs\CustomerGroup\Model\Data;

class Group extends \Magento\Customer\Model\Data\Group implements
    \Rvs\CustomerGroup\Api\Data\GroupInterface
{
    public function getIgnoreMinQty()
    {
        return $this->_get('ignore_min_qty');
    }

    public function setIgnoreMinQty($min)
    {
        return $this->setData('ignore_min_qty', $min);
    }
}
