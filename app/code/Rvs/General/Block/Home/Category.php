<?php

namespace Rvs\General\Block\Home;

use Magento\Catalog\Api\CategoryRepositoryInterface;

class Category extends \Magento\Catalog\Block\Product\ListProduct
{
	const XML_PATH_HOME_CATEGORY = 'rvs/general/category';

	protected $scopeConfig;

	protected $categoryModel;

    public function __construct(
        \Magento\Catalog\Block\Product\Context $context,
        \Magento\Framework\Data\Helper\PostHelper $postDataHelper,
        \Magento\Catalog\Model\Layer\Resolver $layerResolver,
        CategoryRepositoryInterface $categoryRepository,
        \Magento\Framework\Url\Helper\Data $urlHelper,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Catalog\Model\Category $categoryModel,
        array $data = []
    ) {
        $this->scopeConfig = $scopeConfig;
    	$this->categoryModel = $categoryModel;
        parent::__construct($context,$postDataHelper,$layerResolver,$categoryRepository,$urlHelper);
    }

    public function getHomeCategory()
    {
     	$storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
     	return $this->scopeConfig->getValue(self::XML_PATH_HOME_CATEGORY, $storeScope);
    }

    public function getCategoryProducts()
    {
    	$category = $this->getHomeCategory();
    	if($category)
    	{
    		$categoryObj = $this->categoryModel->load($category);
    		return $categoryObj->getProductCollection()->addAttributeToSelect('*')->addAttributeToFilter('inchoo_featured_product', true)->setPageSize(16);
    	}
    	return false;
    }

    public function getCategoryName()
    {
    	$category = $this->getHomeCategory();
    	if($category)
    		return $this->categoryModel->load($category)->getName();
    	return false;
    }
}