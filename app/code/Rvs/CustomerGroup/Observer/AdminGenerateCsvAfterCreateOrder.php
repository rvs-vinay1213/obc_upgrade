<?php
namespace Rvs\CustomerGroup\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class AdminGenerateCsvAfterCreateOrder implements ObserverInterface
{
	protected $csvHelper;

	public function __construct(
	    \Rvs\CustomerGroup\Helper\Data $csvHelper
	){
	    $this->csvHelper = $csvHelper;
	}

    public function execute(Observer $observer)
    {
    	$orderId = $observer->getOrder()->getId();
    	return $this->csvHelper->generateCSV($orderId);
    }
}