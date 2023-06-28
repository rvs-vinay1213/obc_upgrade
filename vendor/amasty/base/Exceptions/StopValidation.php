<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2023 Amasty (https://www.amasty.com)
 * @package Magento 2 Base Package
 */

namespace Amasty\Base\Exceptions;

class StopValidation extends \Exception
{
    /**
     * @var array|bool
     */
    protected $validateResult;

    /**
     * @param array|bool $validateResult
     */
    public function __construct($validateResult)
    {
        $this->validateResult = $validateResult;
    }

    /**
     * @return array|bool
     */
    public function getValidateResult()
    {
        return $this->validateResult;
    }
}
