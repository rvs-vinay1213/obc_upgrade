<?php
namespace Rvs\CustomerGroup\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class GenerateCsvAfterPlaceOrder implements ObserverInterface
{
	protected $csvHelper;

	public function __construct(
	    \Rvs\CustomerGroup\Helper\Data $csvHelper
	){
	    $this->csvHelper = $csvHelper;
	}

	public function execute(Observer $observer)
    {
    	$orderId = $observer->getEvent()->getOrderIds();
    	return $this->csvHelper->generateCSV($orderId[0]);
    }
}
