<?php


namespace Rvs\HideShippingMethod\Model\Plugin\Shipping\Rate\Result;

use Magento\Quote\Model\Quote\Address\RateRequest;
use Magento\Shipping\Model\Rate\Result;

class Remove
{


    private $helper;

    private $requestRate;


    public function __construct(
        \Rvs\HideShippingMethod\Helper\Data $helper,
        RateRequest $requestRate
    )
    {
        $this->helper = $helper;
        $this->requestRate = $requestRate;
    }



    public function afterGetAllRates($subject, $result)
    {
        if ($this->helper->isAvailable()) {
            $hideMethods = $this->helper->getShippingMethod();
            foreach ($result as $key => $rate) {
                $code = $rate->getCarrier() . '_' . $rate->getMethod();
                if (in_array($code, $hideMethods)) {
                    unset($result[$key]);
                }
            }
        }
        return $result;
    }
}
