<?php

namespace Rvs\General\Plugin\Checkout\Model;

class DefaultConfigProvider
{
	protected $_product;

	public function __construct(
		\Magento\Catalog\Api\ProductRepositoryInterface $product
	)
	{
		$this->_product = $product;
	}

    public function afterGetConfig(\Magento\Checkout\Model\DefaultConfigProvider $subject, array $result)
    {
        $items = $result['totalsData']['items'];

        for($i = 0; $i < count($items); $i++) {
        	$productId = $result['quoteItemData'][$i]['product']['entity_id'];
        	$product = $this->_product->getById($productId);
            $result['quoteItemData'][$i]['sku'] = $product->getSku();
            if($product->getPackType())
        	    $result['quoteItemData'][$i]['qty'] = $result['quoteItemData'][$i]['qty'].' '.$product->getResource()->getAttribute('pack_type')->getFrontend()->getValue($product);
        }

        return $result;
    }


}