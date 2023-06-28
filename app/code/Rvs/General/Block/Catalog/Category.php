<?php

namespace Rvs\General\Block\Catalog;

class Category extends \Magento\Framework\View\Element\Template
{
	protected $_registry;

	protected $_categoryFactory;

	public function __construct(
		\Magento\Framework\View\Element\Template\Context $context,
		\Magento\Framework\Registry $registry,
		\Magento\Catalog\Model\CategoryFactory $categoryFactory,
		array $data = []
	)
	{
		$this->_registry = $registry;
		$this->_categoryFactory = $categoryFactory;
		parent::__construct($context, $data);
	}

	public function getCurrentCategory()
	{
		$categories = $this->_registry->registry('current_category')->getChildren();
		if($categories)
		{
			$categoryArray = [];
			foreach(explode(',',$categories) as $category)
			{
				$catObj = $this->_categoryFactory->create()->load($category);
				$categoryArray[] = ['link' => $catObj->getUrl(), 'title' => $catObj->getName(), 'product_count' => count($catObj->getProductCollection())];
			}
			return $categoryArray;
		}
	}
}