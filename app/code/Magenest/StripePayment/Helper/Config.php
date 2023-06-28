<?php
/**
 * Created by Magenest JSC.
 * Author: Jacob
 * Date: 10/01/2019
 * Time: 9:41
 */

namespace Magenest\StripePayment\Helper;

use Magento\Framework\App\Helper\Context;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Store\Model\StoreManagerInterface;

class Config extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * @var EncryptorInterface
     */
    protected $_encryptor;
    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * Config constructor.
     * @param Context $context
     * @param EncryptorInterface $encryptor
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        Context $context,
        EncryptorInterface $encryptor,
        StoreManagerInterface $storeManager
    ) {
        parent::__construct($context);
        $this->_encryptor = $encryptor;
        $this->storeManager = $storeManager;
    }

    /**
     * @return mixed
     */
    public function getIsSandboxMode()
    {
        return $this->scopeConfig->getValue(
            'payment/magenest_stripe/test',
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE
        );
    }

    /**
     * @return mixed
     */
    public function isSave()
    {
        return $this->scopeConfig->getValue(
            'payment/magenest_stripe/save',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * @return mixed
     */
    public function isSaveIntents()
    {
        return $this->scopeConfig->getValue(
            'payment/magenest_stripe_intents/save_card',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * @param null $method
     * @return false|string[]/**
     *
     */
    public function getAllowedCreditCard($method = null)
    {
        if (!$method) {
            $method = 'magenest_stripe';
        }
        $path = 'payment/' . $method . '/cctypes';
        $cardTypes = $this->scopeConfig->getValue(
            $path,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
        //rename card type
        $cardTypes = str_replace(
            ["VI", "MC", "DI", "AE"],
            ["visa", "mastercard", "discover", "amex"],
            $cardTypes ?: ''
        );

        return explode(",", $cardTypes);
    }

    /**
     * @return mixed
     */
    public function getPaymentAction()
    {
        return $this->scopeConfig->getValue(
            'payment/magenest_stripe/payment_action',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * @return mixed
     */
    public function getPaymentActionIframe()
    {
        return $this->scopeConfig->getValue(
            'payment/magenest_stripe_iframe/payment_action',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * @param $value
     * @return string
     */
    public function getConfigValue($value)
    {
        $configValue = $this->scopeConfig->getValue(
            'payment/magenest_stripe/' . $value,
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE
        );

        return $this->_encryptor->decrypt($configValue);
    }

//    BEGIN IFRAME CONFIG

    /**
     * @return mixed
     */
    public function getCheckoutCanCollectBilling()
    {
        return $this->scopeConfig->getValue(
            'payment/magenest_stripe_iframe/collect_billing',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * @return mixed
     */
    public function getCheckoutCanCollectShipping()
    {
        return $this->scopeConfig->getValue(
            'payment/magenest_stripe_iframe/collect_shipping',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * @return mixed
     */
    public function getCheckoutCanCollectZip()
    {
        return $this->scopeConfig->getValue(
            'payment/magenest_stripe_iframe/collect_zip',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * @return mixed
     */
    public function getDisplayName()
    {
        return $this->scopeConfig->getValue(
            'payment/magenest_stripe_iframe/display_name',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * @return mixed
     */
    public function getButtonLabel()
    {
        return $this->scopeConfig->getValue(
            'payment/magenest_stripe_iframe/button_label',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * @return mixed
     */
    public function getAllowRemember()
    {
        return $this->scopeConfig->getValue(
            'payment/magenest_stripe_iframe/allow_remember',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * @return mixed
     */
    public function getCanAcceptBitcoin()
    {
        return $this->scopeConfig->getValue(
            'payment/magenest_stripe_iframe/allow_bitcoin',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * @return mixed
     */
    public function getCanAcceptAlipay()
    {
        return $this->scopeConfig->getValue(
            'payment/magenest_stripe_iframe/allow_alipay',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * @return string|null
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getCheckoutImageUrl()
    {
        $baseUrl = $this->storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA) . 'stripe/';
        $imageId = $this->scopeConfig->getValue(
            'payment/magenest_stripe_iframe/upload_image_id',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
        if (!!$imageId) {
            return $baseUrl . $imageId;
        } else {
            return null;
        }
    }

    /**
     * @return mixed
     */
    public function isIframeActive()
    {
        return $this->scopeConfig->getValue(
            'payment/magenest_stripe_iframe/active',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * @return mixed
     */
    public function getLocale()
    {
        return $this->scopeConfig->getValue(
            'payment/magenest_stripe_iframe/locale',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }
//    END IFRAME CONFIG

    /**
     * @return int
     */
    public function isDebugMode()
    {
        return 1;
    }

    /**
     * @return string
     */
    public function getPublishableKey()
    {
        $isTest = $this->getIsSandboxMode();
        if ($isTest) {
            return $this->getConfigValue('test_publishable');
        } else {
            return $this->getConfigValue('live_publishable');
        }
    }

    /**
     * @return string
     */
    public function getSecretKey()
    {
        $isTest = $this->getIsSandboxMode();
        if ($isTest) {
            return $this->getConfigValue('test_secret');
        } else {
            return $this->getConfigValue('live_secret');
        }
    }

    /**
     * @param string $payment
     * @return array|string|string[]|null
     */
    public function getInstructions($payment = "")
    {
        if ($payment) {
            $path = 'payment/magenest_stripe' . '_' . $payment . '/instructions';
        } else {
            $path = 'payment/magenest_stripe/instructions';
        }
        return preg_replace('/\s+|\n+|\r/', ' ', $this->scopeConfig->getValue(
            $path,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        ) ?: '');
    }

    /**
     * @return mixed
     */
    public function sendMailCustomer()
    {
        return $this->scopeConfig->getValue(
            'payment/magenest_stripe/email_customer',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * @return mixed
     */
    public function getNewOrderStatus()
    {
        return $this->scopeConfig->getValue(
            'payment/magenest_stripe/order_status',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * @return mixed
     */
    public function getApiVersion()
    {
        return $this->scopeConfig->getValue(
            'payment/magenest_stripe/api',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * @return mixed
     */
    public function getDisplayPaymentButton()
    {
        return $this->scopeConfig->getValue(
            'payment/magenest_stripe/display_payment_button',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * @return mixed
     */
    public function getThreeDSecure()
    {
        return $this->scopeConfig->getValue(
            'payment/magenest_stripe/three_d_secure'
        );
    }

    /**
     * @return mixed
     */
    public function getForceThreeDSecure()
    {
        return $this->scopeConfig->getValue(
            'payment/magenest_stripe/force_d_secure'
        );
    }

    /**
     * @return mixed
     */
    public function getThreeDSecureVerify()
    {
        return $this->scopeConfig->getValue(
            'payment/magenest_stripe/three_d_secure_verify'
        );
    }

    //apple pay config

    /**
     * @return mixed
     */
    public function isApplePayActive()
    {
        return $this->scopeConfig->getValue(
            'payment/magenest_stripe_applepay/active',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * @return mixed
     */
    public function getReplacePlaceOrder()
    {
        return $this->scopeConfig->getValue(
            'payment/magenest_stripe_applepay/replace_placeorder',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * @return mixed
     */
    public function getButtonTheme()
    {
        return $this->scopeConfig->getValue(
            'payment/magenest_stripe_applepay/paybutton_theme',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * @return mixed
     */
    public function getButtonType()
    {
        return $this->scopeConfig->getValue(
            'payment/magenest_stripe_applepay/paybutton_type',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * @return mixed
     */
    public function getApplepayButtonLabel()
    {
        return $this->scopeConfig->getValue(
            'payment/magenest_stripe_applepay/paybutton_label',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * @return mixed
     */
    public function getActiveOnCart()
    {
        return $this->scopeConfig->getValue(
            'payment/magenest_stripe_applepay/active_on_shopping_cart',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * @return mixed
     */
    public function getActiveOnProductDetail()
    {
        return $this->scopeConfig->getValue(
            'payment/magenest_stripe_applepay/active_on_product_details',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * @return mixed
     */
    public function getActiveOnCheckout()
    {
        return $this->scopeConfig->getValue(
            'payment/magenest_stripe_applepay/active_on_checkout',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }
    //apple pay config

    /////
    /// /////SOFORT CONFIG//////////////////
    ///
    ///
    /**
     * @return string
     */
    public function getWebhooksSecret()
    {
        return $this->getConfigValue('webhook_key');
    }

    /**
     * @return mixed
     */
    public function isSofortAllowSelectLanguage()
    {
        return $this->scopeConfig->getValue(
            'payment/magenest_stripe_sofort/allow_select_language',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * @return mixed
     */
    public function sofortDefaultLanguage()
    {
        return $this->scopeConfig->getValue(
            'payment/magenest_stripe_sofort/default_language',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * @return mixed
     */
    public function isSofortAllowSelectBankCountry()
    {
        return $this->scopeConfig->getValue(
            'payment/magenest_stripe_sofort/allow_select_bank_country',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * @return mixed
     */
    public function sofortDefaultBankCountry()
    {
        return $this->scopeConfig->getValue(
            'payment/magenest_stripe_sofort/default_bank_country',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }
    ///
    ///
    /// /////SOFORT CONFIG/////////////////

    ////////////iDEAL CONFIG//////////////////
    ///
    ///
    ///
    /**
     * @return mixed
     */
    public function isUseElementInterface()
    {
        return $this->scopeConfig->getValue(
            'payment/magenest_stripe_ideal/use_element',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * @return mixed
     */
    public function isIdealAllowSelectBank()
    {
        return $this->scopeConfig->getValue(
            'payment/magenest_stripe_ideal/allow_select_bank',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * @return mixed
     */
    public function getIdealDefaultBank()
    {
        return $this->scopeConfig->getValue(
            'payment/magenest_stripe_ideal/default_bank',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }
    ///
    ////////////iDEAL CONFIG//////////////////

    /////////Bancontact CONFIG///////////////
    ///
    ///
    ///
    ///

    /**
     * @return mixed
     */
    public function isBancontactAllowSelectLanguage()
    {
        return $this->scopeConfig->getValue(
            'payment/magenest_stripe_bancontact/allow_select_language',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * @return mixed
     */
    public function bancontactDefaultLanguage()
    {
        return $this->scopeConfig->getValue(
            'payment/magenest_stripe_bancontact/default_language',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    ///
    ///
    ///
    ///
    /// /////////Bancontact CONFIG///////////////
    ///

    /////////Stripe checkout CONFIG///////////////
    ///
    ///
    ///
    ///

    /**
     * @return mixed
     */
    public function isStripeCheckoutCollectBilling()
    {
        return $this->scopeConfig->getValue(
            'payment/magenest_stripe_checkout/collect_billing_address',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * @return mixed
     */
    public function getStripeCheckoutPaymentAction()
    {
        return $this->scopeConfig->getValue(
            'payment/magenest_stripe_checkout/payment_action',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * @return mixed
     */
    public function getStripeCheckoutTitle()
    {
        return $this->scopeConfig->getValue(
            'payment/magenest_stripe_checkout/checkout_title',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * @return mixed
     */
    public function getStripeCheckoutDescription()
    {
        return $this->scopeConfig->getValue(
            'payment/magenest_stripe_checkout/checkout_description',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * @return mixed
     */
    public function getStripeCheckoutImageUrl()
    {
        return $this->scopeConfig->getValue(
            'payment/magenest_stripe_checkout/checkout_image_url',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * @return mixed
     */
    public function getStripeCheckoutSubmitType()
    {
        return $this->scopeConfig->getValue(
            'payment/magenest_stripe_checkout/checkout_submit_type',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    ///
    ///
    ///
    ///
    /// /////////Stripe checkout CONFIG///////////////

    /**
     * @return \Magento\Framework\App\Config\ScopeConfigInterface
     */
    public function getScopeConfig()
    {
        return $this->scopeConfig;
    }

    /**
     * @return mixed
     */
    public function getStatementDescriptor()
    {
        return $this->scopeConfig->getValue(
            'payment/magenest_stripe/statement_descriptor',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * @return mixed
     */
    public function getStripeIntentPaymentAction()
    {
        return $this->scopeConfig->getValue(
            'payment/magenest_stripe_paymentintents/payment_action',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * @return mixed
     */
    public function getStripeCountrySpecified()
    {
        return $this->scopeConfig->getValue(
            'payment/magenest_stripe/country_specified',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    ///
    ///
    ///
    ///
    /// /////////Stripe Intents CONFIG///////////////

    /**
     * @return mixed
     */
    public function getPaymentActionIntents()
    {
        return $this->scopeConfig->getValue(
            'payment/magenest_stripe_intents/payment_action',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }
}
