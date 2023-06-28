<?php

namespace Rvs\DisableAddToCart\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\Locale\Resolver;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\StoreManagerInterface;

class Data extends AbstractHelper
{
	const XML_PATH_RVS_DISABLED = 'disabledaddtocart/';
	/**
	 * @var StoreManagerInterface
	 */
	protected $storeManager;
	protected $requestAction;
	protected $responseAction;
	/**
	 * Data constructor.
	 * @param Context $context
	 * @param StoreManagerInterface $storeManager
	 */
	public function __construct(
		Context $context,
		StoreManagerInterface $storeManager,
		\Magento\Framework\App\Request\Http $requestAction,
		\Magento\Framework\Controller\Result\RedirectFactory $resultRedirectFactory,
		\Magento\Framework\App\Response\Http $responseAction
	)
	{
		parent::__construct($context);
		$this->storeManager = $storeManager;
		$this->requestAction = $requestAction;
		$this->resultRedirectFactory = $resultRedirectFactory;
		$this->responseAction = $responseAction;
	}
	public function getConfigValue($field, $storeId = null)
	{
		return $this->scopeConfig->getValue(
			$field, ScopeInterface::SCOPE_STORE, $storeId
		);
	}

	public function getGeneralConfig($code, $storeId = null)
	{

		return $this->getConfigValue(self::XML_PATH_RVS_DISABLED .'disabledcart/'. $code, $storeId);
	}
	public function getRequestAction($storeId = null)
	{
		$actionName = $this->requestAction->getFullActionName();
		if($actionName=='checkout_index_index'){
			$storeUrl = $this->storeManager->getStore()->getBaseUrl();
			$this->responseAction->setRedirect($storeUrl.'closedfornow');			
			return $this->responseAction;
		}
	}
}