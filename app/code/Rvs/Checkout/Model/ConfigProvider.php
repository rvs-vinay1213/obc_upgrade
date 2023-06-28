<?php

namespace Rvs\Checkout\Model;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\View\LayoutInterface;

class ConfigProvider implements ConfigProviderInterface
{
    /** @var LayoutInterface  */
    protected $_layout;

    public function __construct(LayoutInterface $layout)
    {
        $this->_layout = $layout;
    }

    public function getConfig()
    {
        // $myBlockId = 20;

        return [
            'shipping_content' => $this->_layout->createBlock('Magento\Cms\Block\Block')->setBlockId(20)->toHtml(),
            'billing_content' => $this->_layout->createBlock('Magento\Cms\Block\Block')->setBlockId('header-follow-us')->toHtml()
        ];
    }
}