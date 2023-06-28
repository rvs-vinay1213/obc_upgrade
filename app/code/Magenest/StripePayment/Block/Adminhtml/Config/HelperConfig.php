<?php

namespace Magenest\StripePayment\Block\Adminhtml\Config;

use Magenest\StripePayment\Helper\Config;
use Magento\Backend\Block\Widget\Context;
use Magento\Framework\Pricing\Helper\Data;

class HelperConfig extends \Magento\Framework\View\Element\Template
{
    /**
     * @var Config
     */
    protected $_config;

    /**
     * @var Data
     */
    protected $_helperData;

    /**
     * HelperConfig constructor.
     * @param Context $context
     * @param Config $config
     * @param Data $_helperData
     * @param array $data
     */
    public function __construct(
        Context $context,
        Config $config,
        Data $_helperData,
        $data = []
    ) {
        $this->_config = $config;
        $this->_helperData = $_helperData;
        parent::__construct($context, $data);
    }

    /**
     * @return string
     */
    public function getPublishableKey()
    {
        return $this->_config->getPublishableKey();
    }

    /**
     * @return false|string[]
     */
    public function getAllowedCreditCard()
    {
        return $this->_config->getAllowedCreditCard();
    }
}
