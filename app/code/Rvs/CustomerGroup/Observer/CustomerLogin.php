<?php

namespace Rvs\CustomerGroup\Observer;

use Magento\Framework\Event\ObserverInterface;

class CustomerLogin implements ObserverInterface
{
	protected $_coreSession;

	protected $customerGroupCollection;

	public function __construct(
	    \Magento\Framework\Session\SessionManagerInterface $coreSession,
	    \Magento\Customer\Model\ResourceModel\Group\CollectionFactory $customerGroupCollection

	){
	    $this->_coreSession = $coreSession;
	    $this->customerGroupCollection = $customerGroupCollection;
	}

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $customer = $observer->getEvent()->getCustomer();
        if($customer->getId())
        {
        	$this->_coreSession->start();
        	if(!empty($this->_coreSession->getLoggedInCustomerId())) {
        		$this->_coreSession->unsLoggedInCustomerId();
        	}
        	$this->_coreSession->setLoggedInCustomerId($customer->getId());
        }
    }
}