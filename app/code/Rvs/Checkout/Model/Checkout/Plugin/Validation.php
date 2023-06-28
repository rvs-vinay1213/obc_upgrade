<?php 
namespace Rvs\Checkout\Model\Checkout\Plugin;

class Validation {
	public function __construct(
        // \Amasty\Checkout\Model\Delivery $delivery
        \Amasty\CheckoutDeliveryDate\Model\DeliveryDateProvider $delivery,
    ) {
		$this->delivery = $delivery;
    }

	public function beforeSavePaymentInformationAndPlaceOrder(
        \Magento\Checkout\Api\PaymentInformationManagementInterface $subject,
        $cartId,
        \Magento\Quote\Api\Data\PaymentInterface $paymentMethod,
        \Magento\Quote\Api\Data\AddressInterface $billingAddress = null
    ) {
        $delivery = $this->delivery->findByQuoteId($cartId);
        $time = $delivery->getTime();
        $date = $delivery->getDate();
        
        if($time == null || $time <= 0 || $date == null) {
            $this->validateTimeDate();
        }
    }

    protected function validateTimeDate()
    {
        throw new \Magento\Framework\Exception\CouldNotSaveException(
            __( "There is some issue in delivery date. Please select date and time again." )
        );
    }
}