<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2023 Amasty (https://www.amasty.com)
 * @package Magento 2 Base Package
 */

namespace Amasty\Base\Model\LicenceService\Schedule\Checker;

interface SenderCheckerInterface
{
    /**
     * @param string $flag
     * @return bool
     */
    public function isNeedToSend(string $flag): bool;
}
