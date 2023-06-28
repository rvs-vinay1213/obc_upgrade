<?php

namespace Rvs\CustomerGroup\Observer;

use Magento\Framework\Event\ObserverInterface;

class CustomerLogout implements ObserverInterface
{
	protected $_coreSession;

	public function __construct(
	    \Magento\Framework\Session\SessionManagerInterface $coreSession
	){
	    $this->_coreSession = $coreSession;
	}

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $customer = $observer->getEvent()->getCustomer();
        if($customer->getId())
        {
            $customerId = $this->_coreSession->getLoggedInCustomerId();
            if(!empty($customerId))
            {
                $this->_coreSession->start();
                $this->_coreSession->unsLoggedInCustomerId();
            }
        }
    }
}