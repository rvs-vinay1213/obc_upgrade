<?php

namespace Magenest\StripePayment\Model;

use Magenest\StripePayment\Model\ResourceModel\Attribute as Resource;
use Magenest\StripePayment\Model\ResourceModel\Attribute\Collection as Collection;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\Context;
use Magento\Framework\Registry;

class Attribute extends AbstractModel
{
    /**
     * @var string
     */
    protected $_eventPrefix = 'attribute_';

    /**
     * Attribute constructor.
     * @param Context $context
     * @param Registry $registry
     * @param Resource $resource
     * @param Collection $resourceCollection
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        Resource $resource,
        Collection $resourceCollection,
        $data = []
    ) {
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }
}
