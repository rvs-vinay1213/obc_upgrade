<?php

namespace Rvs\HideShippingMethod\Helper;

use Magento\Customer\Helper\Session\CurrentCustomer;
use Magento\Checkout\Model\Session as CheckoutSession;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{

    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    private $timezone;

    /**
     * @var \Magento\Customer\Helper\Session\CurrentCustomer
     */
    private $session;

    /**
     * @var \Magento\Framework\App\Config
     */
    private $shippingConfig;

    /**
     * @var String
     */
    private $tab = 'checkout';

    /**
     * Data constructor.
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone
     * @param \Magento\Shipping\Model\Config $shippingConfig
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        CurrentCustomer $currentCustomer,
        \Amasty\CheckoutDeliveryDate\Model\DeliveryDateProvider $delivery,
        \Magento\Shipping\Model\Config $shippingConfig,
        CheckoutSession $checkoutSession
    )
    {
        parent::__construct($context);

        $this->timezone = $timezone;
        $this->session = $currentCustomer;
        $this->shippingConfig = $shippingConfig;
        $this->delivery = $delivery;
        $this->checkoutSession = $checkoutSession;
    }

    /**
     * Get module configuration values from core_config_data
     *
     * @param $setting
     * @return mixed
     */
    public function getConfig($setting)
    {
        return $this->scopeConfig->getValue(
            $this->tab . '/rvs_hideshippingmethod/' . $setting,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get different values from core_config_data and decide if custom shipping method is available.
     *
     * @return boolean
     */
    public function isAvailable()
    {

        /**
         * Check if shipping method is actually enabled
         */


        if (!$this->getConfig('enabled')) {
            return false;
        }

        /**
         * Check if shipping method should be available for logged in users only
         */
        if ($this->getConfig('customer') && !$this->isCustomerLoggedIn()) {
            return false;
        }

        return true;
    }

    /**
     * Return array of allowed carriers
     *
     * @return array
     */
    public function getActiveCarriers()
    {

        $carriers = $this->shippingConfig->getActiveCarriers();
        $methods = [];

        foreach ($carriers as $carrierCode => $carrierModel) {
            if (!$carrierMethods = $carrierModel->getAllowedMethods()) {
                continue;
            }

            $title = $carrierModel->getConfigData('title');

            foreach ($carrierMethods as $methodCode => $method) {
                $code = $carrierCode . '_' . $methodCode;

                $methods[] = [
                    'label' => sprintf('%s (%s)', $title, $code),
                    'value' => $code
                ];
            }
        }

        return $methods;
    }

    /**
     * Check if current user logged in
     *
     * @return bool
     */
    private function isCustomerLoggedIn()
    {
        return $this->session->getCustomerId();
    }

    public function getQouteId()
    {
        return (int)$this->checkoutSession->getQuote()->getId();
    }

    public function getShippingMethod()
    {
        $customerGroupid = $this->session->getCustomer()->getGroupId();

        $delivery = $this->delivery->findByQuoteId($this->getQouteId());
        $time = $delivery->getTime();
        $date = $delivery->getDate();
        $day = $this->timezone->date(strtotime($date))->format('D');

        if ($day == 'Sun' || $day == 'Sat') {

            switch ($customerGroupid) {
                case  16:
                    return $this->getHideShippingMethod('flatrateone_flatrate');
                case 23:
                case 21:
                case 17:
                case 20:
                case 18:
                case 26:
                case 24:
                    return $this->getHideShippingMethod('flatratesix_flatrate');
                default:
                    return $this->getHideShippingMethod('flatrateone_flatrate');

            }


        } else {
            if ($time <= 0) {
                return $this->getHideShippingMethod();
            }
            switch ($customerGroupid) {
                case  16:
                    if ($time >= 6 && $time < 14) {
                        return $this->getHideShippingMethod('freeshipping_freeshipping');
                    } else {
                        return $this->getHideShippingMethod('flatratethree_flatrate');
                    }
                case 24:
                    if ($time >= 6 && $time < 14) {
                        return $this->getHideShippingMethod('flatratefive_flatrate');
                    } else {
                        return $this->getHideShippingMethod('flatratethree_flatrate');
                    }
                case 28:
                    return $this->getHideShippingMethod('flatrateeight_flatrate');
                case 23:
                case 21:
                case 17:
                case 20:
                case 18:
                case 26:
                    if ($time >= 6 && $time < 14) {
                        return $this->getHideShippingMethod('flatratefour_flatrate');
                    } else {
                        return $this->getHideShippingMethod('flatratethree_flatrate');
                    }
                default:
                    if ($time >= 7 && $time < 14) {
                        return $this->getHideShippingMethod('flatrateone_flatrate');
                    } else {
                        return $this->getHideShippingMethod('flatrate_flatrate');
                    }


            }

        }
        return [];
    }

    public function getHideShippingMethod($unsetShippingMethodCode = '')
    {

        $shippingMethod = [
            'flatrate_flatrate',
            'freeshipping_freeshipping',
            'flatrateone_flatrate',
            'flatratetwo_flatrate',
            'flatratethree_flatrate',
            'flatratefour_flatrate',
            'flatratefive_flatrate',
            'flatratesix_flatrate',
            'flatrateseven_flatrate',
            'flatrateeight_flatrate'
        ];



        if (empty($unsetShippingMethodCode)) {
            return $shippingMethod;
        }
        unset($shippingMethod[array_search($unsetShippingMethodCode, $shippingMethod)]);
        return $shippingMethod;
    }
}
