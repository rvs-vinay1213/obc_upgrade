<?php

namespace Rvs\General\Plugin;

class FinalPricePlugin
{
    protected $request;

    public function __construct(
        \Magento\Framework\App\Request\Http $request
    ){
        $this->request = $request;
    }

    public function beforeSetTemplate(\Magento\Catalog\Pricing\Render\FinalPriceBox $subject, $template)
    {
        $fullAction = $this->request->getFullActionName();
        if ($fullAction == "catalog_product_view")
            return ['Rvs_General::product/price/final_price.phtml'];
        else 
            return[$template];
    }
}