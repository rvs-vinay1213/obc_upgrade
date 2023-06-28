<?php

namespace Rvs\General\Plugin\Catalog\Model\Layer\Filter;

class Category
{
	public function afterGetName(\Magento\Catalog\Model\Layer\Filter\Category $subject, $result)
    {
    	return __('Categories');
    }
}