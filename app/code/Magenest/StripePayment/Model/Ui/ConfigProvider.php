<?php
/**
 * Created by Magenest JSC.
 * Author: Jacob
 * Date: 10/01/2019
 * Time: 9:41
 */

namespace Magenest\StripePayment\Model\Ui;

use Magenest\StripePayment\Model\Alipay;
use Magenest\StripePayment\Model\ApplePay;
use Magenest\StripePayment\Model\Bancontact;
use Magenest\StripePayment\Model\Eps;
use Magenest\StripePayment\Model\GiroPay;
use Magenest\StripePayment\Model\Ideal;
use Magenest\StripePayment\Model\Intents;
use Magenest\StripePayment\Model\PaymentIntents;
use Magenest\StripePayment\Model\Multibanco;
use Magenest\StripePayment\Model\Przelewy;
use Magenest\StripePayment\Model\Sepa;
use Magenest\StripePayment\Model\Sofort;
use Magenest\StripePayment\Model\StripePaymentIframe;
use Magenest\StripePayment\Model\StripePaymentMethod;
use Magenest\StripePayment\Model\WeChatPay;
use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\Asset\Repository;
use Magento\Payment\Helper\Data as PaymentHelper;
use Magento\Payment\Model\Config as PaymentConfig;
use Psr\Log\LoggerInterface;

class ConfigProvider implements ConfigProviderInterface
{
    /**
     * @var \Magenest\StripePayment\Helper\Data
     */
    protected $_helper;
    /**
     * @var \Magenest\StripePayment\Model\CardFactory
     */
    protected $_cardFactory;
    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $_customerSession;
    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $_checkoutSession;
    /**
     * @var \Magenest\StripePayment\Helper\Config
     */
    protected $stripeConfigHelper;
    /**
     * @var \Magento\Framework\UrlInterface
     */
    protected $_urlBuilder;
    /**
     * @var \Magenest\StripePayment\Model\Source\IdealBank
     */
    protected $idealBank;
    /**
     * @var \Magenest\StripePayment\Model\Source\BancontactLanguage
     */
    protected $bancontactLanguage;
    /**
     * @var \Magenest\StripePayment\Model\Source\SofortLanguage
     */
    protected $sofortLanguage;
    /**
     * @var \Magenest\StripePayment\Model\Source\SofortCountry
     */
    protected $sofortBank;
    /**
     * @var Repository
     */
    protected $assetRepo;
    /**
     * @var RequestInterface
     */
    protected $request;
    /**
     * @var LoggerInterface
     */
    protected $logger;
    const CODE = 'magenest_stripe';

    /**
     * ConfigProvider constructor.
     * @param PaymentConfig $paymentConfig
     * @param Repository $assetRepo
     * @param RequestInterface $request
     * @param LoggerInterface $logger
     * @param PaymentHelper $paymentHelper
     * @param \Magenest\StripePayment\Model\CardFactory $cardFactory
     * @param \Magenest\StripePayment\Helper\Data $dataHelper
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magenest\StripePayment\Helper\Config $stripeConfigHelper
     * @param \Magento\Framework\UrlInterface $urlBuilder
     * @param \Magenest\StripePayment\Model\Source\IdealBank $idealBank
     * @param \Magenest\StripePayment\Model\Source\BancontactLanguage $bancontactLanguage
     * @param \Magenest\StripePayment\Model\Source\SofortLanguage $sofortLanguage
     * @param \Magenest\StripePayment\Model\Source\SofortCountry $sofortBank
     */
    public function __construct(
        PaymentConfig $paymentConfig,
        Repository $assetRepo,
        RequestInterface $request,
        LoggerInterface $logger,
        PaymentHelper $paymentHelper,
        \Magenest\StripePayment\Model\CardFactory $cardFactory,
        \Magenest\StripePayment\Helper\Data $dataHelper,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magenest\StripePayment\Helper\Config $stripeConfigHelper,
        \Magento\Framework\UrlInterface $urlBuilder,
        \Magenest\StripePayment\Model\Source\IdealBank $idealBank,
        \Magenest\StripePayment\Model\Source\BancontactLanguage $bancontactLanguage,
        \Magenest\StripePayment\Model\Source\SofortLanguage $sofortLanguage,
        \Magenest\StripePayment\Model\Source\SofortCountry $sofortBank
    ) {
        $this->_customerSession = $customerSession;
        $this->_checkoutSession = $checkoutSession;
        $this->_helper = $dataHelper;
        $this->_cardFactory = $cardFactory;
        $this->stripeConfigHelper = $stripeConfigHelper;
        $this->_urlBuilder = $urlBuilder;
        $this->idealBank = $idealBank;
        $this->bancontactLanguage = $bancontactLanguage;
        $this->sofortBank = $sofortBank;
        $this->sofortLanguage = $sofortLanguage;
        $this->config = $paymentConfig;
        $this->assetRepo = $assetRepo;
        $this->request = $request;
        $this->logger = $logger;
    }

    /**
     * @return \array[][]
     */
    public function getConfig()
    {
        return [
            'payment' => [
                "magenest_stripe_config" => [
                    'publishableKey' => $this->stripeConfigHelper->getPublishableKey(),
                    'isLogin' => $this->_customerSession->isLoggedIn(),
                    'isZeroDecimal' => $this->checkIsZeroDecimal(),
                    'icon' => $this->getIconMethod(),
                    'country_code' => $this->stripeConfigHelper->getStripeCountrySpecified(),
                    'https_check' => $this->stripeConfigHelper->getScopeConfig()->getValue('payment/magenest_stripe/https_check') ? true : false,
                ],
                StripePaymentMethod::CODE => $this->getStripeConfig(),
                StripePaymentIframe::CODE => $this->getStripeCheckoutConfigOption(),
                ApplePay::CODE => $this->getStripeApplePayConfig(),
                Sofort::CODE => $this->getSofortConfig(),
                Ideal::CODE => $this->getIdealConfig(),
                Bancontact::CODE => $this->getBancontactConfig(),
                Sepa::CODE => $this->getSepaConfig(),
                Intents::CODE => $this->getIntentsConfig(),
            ]
        ];
    }

    /**
     * @return array
     */
    public function getIconMethod()
    {
        return [
            StripePaymentMethod::CODE => $this->getViewFileUrl("Magenest_StripePayment::images/card.png"),
            GiroPay::CODE => $this->getViewFileUrl("Magenest_StripePayment::images/giropay.png"),
            Alipay::CODE => $this->getViewFileUrl("Magenest_StripePayment::images/alipay.png"),
            Eps::CODE => $this->getViewFileUrl("Magenest_StripePayment::images/eps.png"),
            Bancontact::CODE => $this->getViewFileUrl("Magenest_StripePayment::images/bancontact.png"),
            Ideal::CODE => $this->getViewFileUrl("Magenest_StripePayment::images/ideal.png"),
            Multibanco::CODE => $this->getViewFileUrl("Magenest_StripePayment::images/multibanco.png"),
            Przelewy::CODE => $this->getViewFileUrl("Magenest_StripePayment::images/p24.png"),
            Sofort::CODE => $this->getViewFileUrl("Magenest_StripePayment::images/sofort.png"),
            WeChatPay::CODE => $this->getViewFileUrl("Magenest_StripePayment::images/wechatpay.png"),
            Sepa::CODE => $this->getViewFileUrl("Magenest_StripePayment::images/sepa.png"),
        ];
    }

    /**
     * @return bool
     * @throws LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function checkIsZeroDecimal()
    {
        $currency = $this->_checkoutSession->getQuote()->getBaseCurrencyCode();
        return $this->_helper->isZeroDecimal($currency) ? true : false;
    }

    /**
     * @return array
     */
    public function getStripeConfig()
    {
        $cardData = $this->_helper->getCardWithCardType(Intents::CODE);
        return [
            'isSave' => $this->stripeConfigHelper->isSave() ? true : false,
            'saveCards' => json_encode($cardData),
            'hasCard' => count($cardData)>0 ? true : false,
            'instructions' => $this->stripeConfigHelper->getInstructions(),
            'api' => $this->stripeConfigHelper->getApiVersion(),
            'display_payment_button' => $this->stripeConfigHelper->getDisplayPaymentButton() ? true : false,
            'card_type_allowed' => $this->stripeConfigHelper->getAllowedCreditCard(StripePaymentMethod::CODE)
        ];
    }

    /**
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getStripeCheckoutConfigOption()
    {
        $canCollectShipping = $this->stripeConfigHelper->getCheckoutCanCollectShipping();
        $canCollectBilling = $this->stripeConfigHelper->getCheckoutCanCollectBilling();
        $canCollectZipCode = $this->stripeConfigHelper->getCheckoutCanCollectZip();
        $displayName = $this->stripeConfigHelper->getDisplayName();
        $imageUrl = $this->stripeConfigHelper->getCheckoutImageUrl();
        return [
            'can_collect_billing' => $canCollectBilling,
            'can_collect_shipping' => $canCollectShipping,
            'can_collect_zip' => $canCollectZipCode,
            'display_name' => $displayName,
            'button_label' => $this->stripeConfigHelper->getButtonLabel(),
            'allow_remember' => $this->stripeConfigHelper->getAllowRemember(),
            'accept_bitcoin' => $this->stripeConfigHelper->getCanAcceptBitcoin(),
            'accept_alipay' => $this->stripeConfigHelper->getCanAcceptAlipay(),
            'image_url' => $imageUrl,
            'locale' => $this->stripeConfigHelper->getLocale(),
            'instructions' => $this->stripeConfigHelper->getInstructions('iframe'),
        ];
    }

    /**
     * @return array
     */
    public function getStripeApplePayConfig()
    {
        return [
            'replace_placeorder' => $this->stripeConfigHelper->getReplacePlaceOrder(),
            //'button_label' => $this->stripeConfigHelper->getApplepayButtonLabel()?$this->stripeConfigHelper->getApplepayButtonLabel():"Total",
            'button_type' => $this->stripeConfigHelper->getButtonType(),
            'button_theme' => $this->stripeConfigHelper->getButtonTheme(),
            'instructions' => $this->stripeConfigHelper->getInstructions('applepay'),
            'active_on_checkout' => $this->stripeConfigHelper->getActiveOnCheckout() ? true : false
        ];
    }

    /**
     * @return array
     */
    public function getSofortConfig()
    {
        return [
            'allow_select_bank_country' => ($this->stripeConfigHelper->isSofortAllowSelectBankCountry()=="1") ? true : false,
            'allow_select_language' => ($this->stripeConfigHelper->isSofortAllowSelectLanguage()=="1") ? true : false,
            'default_language' => $this->stripeConfigHelper->sofortDefaultLanguage(),
            'default_bank_country' => $this->stripeConfigHelper->sofortDefaultBankCountry(),
            'language_list' => json_encode($this->sofortLanguage->toOptionArray()),
            'bank_list' => json_encode($this->sofortBank->toOptionArray()),
            'instructions' => $this->stripeConfigHelper->getInstructions('sofort'),
        ];
    }

    /**
     * @return array
     */
    public function getIdealConfig()
    {
        return [
            'is_use_element_interface' => ($this->stripeConfigHelper->isUseElementInterface()=="1") ? true : false,
            'is_allow_select_bank' => ($this->stripeConfigHelper->isIdealAllowSelectBank()=="1") ? true : false,
            'default_bank' => $this->stripeConfigHelper->getIdealDefaultBank(),
            'bank_list' => json_encode($this->idealBank->toOptionArray()),
            'instructions' => $this->stripeConfigHelper->getInstructions('ideal'),
        ];
    }

    /**
     * @return array
     */
    public function getBancontactConfig()
    {
        return [
            'allow_select_language' => ($this->stripeConfigHelper->isBancontactAllowSelectLanguage()=="1") ? true : false,
            'default_language' => $this->stripeConfigHelper->bancontactDefaultLanguage(),
            'language_list' => json_encode($this->bancontactLanguage->toOptionArray()),
            'instructions' => $this->stripeConfigHelper->getInstructions('bancontact'),
        ];
    }

    /**
     * @return array
     */
    private function getSepaConfig()
    {
        return [
            'instructions' => $this->stripeConfigHelper->getInstructions('sepa'),
        ];
    }

    /**
     * @param $fileId
     * @param array $params
     * @return string
     */
    public function getViewFileUrl($fileId, array $params = [])
    {
        try {
            $params = array_merge(['_secure' => $this->request->isSecure()], $params);
            return $this->assetRepo->getUrlWithParams($fileId, $params);
        } catch (LocalizedException $e) {
            $this->logger->critical($e);
            return $this->urlBuilder->getUrl('', ['_direct' => 'core/index/notFound']);
        }
    }

    /**
     * @return array
     */
    private function getIntentsConfig()
    {
        $cardData = $this->_helper->getCardWithCardType(Intents::CODE);
        return [
            'isSave' => $this->stripeConfigHelper->isSaveIntents() ? true : false,
            'saveCards' => json_encode($cardData),
            'hasCard' => count($cardData)>0,
            'card_type_allowed' => $this->stripeConfigHelper->getAllowedCreditCard(Intents::CODE)
        ];
    }
}
