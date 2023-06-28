<?php
/**
 * Copyright © 2019 Magenest. All rights reserved.
 * See COPYING.txt for license details.
 *
 * Stripe extension
 * NOTICE OF LICENSE
 *
 * @category Magenest
 * @package Magenest_Stripe
 */

namespace Magenest\StripePayment\Controller\Checkout\Checkout;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\Exception\LocalizedException;

/**
 * Class Cancel
 * @package Magenest\StripePayment\Controller\Checkout\Checkout
 */
class Cancel extends Action
{
    /**
     * @var CheckoutSession
     */
    protected $_checkoutSession;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $_customerSession;

    /**
     * Cancel constructor.
     * @param Context $context
     * @param CheckoutSession $session
     * @param \Magento\Customer\Model\Session $customerSession
     */
    public function __construct(
        Context $context,
        CheckoutSession $session,
        \Magento\Customer\Model\Session $customerSession
    ) {
        parent::__construct($context);
        $this->_checkoutSession = $session;
        $this->_customerSession = $customerSession;
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     * @throws LocalizedException
     */
    public function execute()
    {
        try {
            $order = $this->_checkoutSession->getLastRealOrder();
            if ($this->_customerSession->getCustomerId()) {
                return $this->_redirect('sales/order/view/order_id/' . $order->getId());
            }
            $this->messageManager->addNoticeMessage('Please login to review your order');
            return $this->_redirect('checkout/cart');
        } catch (\Stripe\Exception\ApiErrorException $e) {
            throw new LocalizedException(__($e->getMessage()));
        }
    }
}
