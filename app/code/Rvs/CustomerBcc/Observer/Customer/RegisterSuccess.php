<?php


namespace Rvs\CustomerBcc\Observer\Customer;

class RegisterSuccess implements \Magento\Framework\Event\ObserverInterface
{

    /**
     * Execute observer
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
	 
	 const XML_PATH_EMAIL_RECIPIENT = 'trans_email/ident_general/email';
protected $_transportBuilder;
protected $inlineTranslation;
protected $scopeConfig;
protected $storeManager;
protected $_escaper;

protected $_customerFactory;
public function __construct(
    \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder,
    \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation,
    \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
    \Magento\Store\Model\StoreManagerInterface $storeManager,
    \Magento\Framework\Escaper $escaper,	
	\Magento\Customer\Model\CustomerFactory $customerFactory
) {
    $this->_transportBuilder = $transportBuilder;
    $this->inlineTranslation = $inlineTranslation;
    $this->scopeConfig = $scopeConfig;
    $this->storeManager = $storeManager;
    $this->_escaper = $escaper;	
	$this->_customerFactory = $customerFactory;
}


    public function execute(
        \Magento\Framework\Event\Observer $observer
    ) {
		
		$customer = $observer->getData('customer');
		$customerFirstName = $customer->getFirstname();
		$customerLastName = $customer->getLastname();
		$customerEmail = $customer->getEmail();
		
		$customerName = $customerFirstName.' '. $customerLastName;
		
		
		
    $this->inlineTranslation->suspend();
    try 
    {
        $error = false;

        $sender = [
            'name' => $this->_escaper->escapeHtml($customerName),
            'email' => $this->_escaper->escapeHtml($customer->getEmail()),			
        ];
		
		
        $postObject = new \Magento\Framework\DataObject();
        $postObject->setData($sender);
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE; 
        $transport = 
            $this->_transportBuilder
            ->setTemplateIdentifier('15') // Send the ID of Email template which is created in Admin panel
            ->setTemplateOptions(
                ['area' => \Magento\Framework\App\Area::AREA_FRONTEND, // using frontend area to get the template file
                'store' => \Magento\Store\Model\Store::DEFAULT_STORE_ID,]
            )
            ->setTemplateVars(
					[
						 'store' => $this->storeManager->getStore(),
						'customer_name' => $this->_escaper->escapeHtml($customerName),
                        'customer_email'    => $this->_escaper->escapeHtml($customer->getEmail())
					]
					)->setFrom($sender)
            ->addTo($this->scopeConfig->getValue(self::XML_PATH_EMAIL_RECIPIENT, $storeScope))
			->addBcc('sitetesting747@gmail.com', '')
			->addcc('john@owenbrotherscatering.co.uk', '')
            ->getTransport();
        $transport->sendMessage();
        $this->inlineTranslation->resume();


    } 
    catch (\Exception $e) 
    {
        \Magento\Framework\App\ObjectManager::getInstance()->get('Psr\Log\LoggerInterface')->debug($e->getMessage());
    }
    } 
}
