<?php

namespace Rvs\CustomerGroup\Api\Data;

interface GroupInterface extends \Magento\Customer\Api\Data\GroupInterface
{
    const IGNORE_MIN_QTY = 'ignore_min_qty';

    /**
     * Get ignore min qty
     *
     * @return string
     */
    public function getIgnoreMinQty();

    /**
     * Set ignore min qty
     *
     * @param string $min
     * @return $this
     */
    public function setIgnoreMinQty($min);
}
