<?php

namespace Rvs\General\Model\Config\Source;

use Magento\Framework\App\ObjectManager;

class Category implements \Magento\Framework\Option\ArrayInterface
{
	protected $collectionFactory;

	public function __construct(
	    \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory $collectionFactory
	) {
	    $this->collectionFactory = $collectionFactory;
	}

	/**
     * @return array
     */
    public function toOptionArray()
    {
        $categories = $this->collectionFactory->create()->addAttributeToSelect('*');

        $options = [];
        foreach($categories as $category)
        	$options[] = ['value' => $category->getId(), 'label' => $category->getName()];

        return $options;
    }
}