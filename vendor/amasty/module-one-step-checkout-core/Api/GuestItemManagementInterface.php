<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2023 Amasty (https://www.amasty.com)
 * @package One Step Checkout Core for Magento 2
 */

namespace Amasty\CheckoutCore\Api;

use Magento\Quote\Api\Data\AddressInterface;

interface GuestItemManagementInterface
{
    /**
     * @param string $cartId
     * @param int    $itemId
     * @param AddressInterface $address
     *
     * @return \Amasty\CheckoutCore\Api\Data\TotalsInterface|boolean
     */
    public function remove($cartId, $itemId, AddressInterface $address);

    /**
     * @param string $cartId
     * @param int    $itemId
     * @param string $formData
     *
     * @return \Amasty\CheckoutCore\Api\Data\TotalsInterface|boolean
     */
    public function update($cartId, $itemId, $formData);
}
