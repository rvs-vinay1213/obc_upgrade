<?php
/**
 * Created by Magenest JSC.
 * Author: Jacob
 * Date: 10/01/2019
 * Time: 9:41
 */

namespace Magenest\StripePayment\Model;

use Magenest\StripePayment\Model\ResourceModel\Charge as Resource;
use Magenest\StripePayment\Model\ResourceModel\Charge\Collection as Collection;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\Context;
use Magento\Framework\Registry;

class Charge extends AbstractModel
{
    /**
     * @var string
     */
    protected $_eventPrefix = 'charge_';

    /**
     * Charge constructor.
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
