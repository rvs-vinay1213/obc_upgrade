<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2023 Amasty (https://www.amasty.com)
 * @package One Step Checkout Core for Magento 2
 */

namespace Amasty\CheckoutCore\Plugin\Payment\Model;

class Info
{
    /**
     * @param \Magento\Payment\Model\Info $subject
     * @param callable $proceed
     * @param $key
     * @param null $value
     *
     * @return \Magento\Payment\Model\Info
     */
    public function aroundSetAdditionalInformation(
        \Magento\Payment\Model\Info $subject,
        callable $proceed,
        $key,
        $value = null
    ) {
        if ($key === \Magento\Framework\Api\ExtensibleDataInterface::EXTENSION_ATTRIBUTES_KEY) {
            return $subject;
        }

        return $proceed($key, $value);
    }
}
