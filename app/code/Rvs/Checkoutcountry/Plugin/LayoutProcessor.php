<?php

namespace Rvs\Checkoutcountry\Plugin;
use Magento\Directory\Helper\Data as DirectoryHelper;

class LayoutProcessor
{

    protected $countryOptions;

    public function __construct(
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Directory\Model\ResourceModel\Country\CollectionFactory $countryCollection,
        DirectoryHelper $directoryHelper
    ) {
        $this->_storeManager = $storeManager;
        $this->countryCollectionFactory = $countryCollection;
        $this->directoryHelper = $directoryHelper;
    }

   
    public function afterProcess(
        \Magento\Checkout\Block\Checkout\LayoutProcessor $subject,
        $result
    ) {

        $result['components']['checkout']['children']['steps']['children']['shipping-step']['children']
        ['shippingAddress']['children']['shipping-address-fieldset']['children']['country_id'] = [
            'component' => 'Magento_Ui/js/form/element/select',
            'config' => [
                'customScope' => 'shippingAddress',
                'template' => 'ui/form/field',
                'elementTmpl' => 'ui/form/element/select',
                'id' => 'drop-down',
            ],
            'dataScope' => 'shippingAddress.country_id',
            'label' => 'Country',
            'provider' => 'checkoutProvider',
            'visible' => true,
            'validation' => [],
            'sortOrder' => 70,
            'id' => 'drop-down',
            'options' => $this->getCountryOptions()
        ];
        return $result;
    }

    public function getStoreId()
    {
        return $this->_storeManager->getStore()->getId();
    }

    //We have refernce this function from Magento\Checkout\Block\Checkout\DirectoryDataProcessor
    public function getCountryOptions()
    {
            $countryIds = array("GB");
            //$countryIds = array("US","CA");
           
            $countryselection = $this->countryCollectionFactory->create()->loadByStore(
                $this->_storeManager->getStore()->getId());
            $countryselection->addFieldToFilter('country_id', ['in' => $countryIds]);
            $countryselection = $countryselection->toOptionArray();
            $countryarray = $this->orderCountryOptions($countryselection);

        return $countryarray;
    }


    public function orderCountryOptions(array $countryOptions)
    {
        $topCountryCodes = $this->directoryHelper->getTopCountryCodes();
        if (empty($topCountryCodes)) {
            return $countryOptions;
        }

        $headOptions = [];
        $tailOptions = [[
            'value' => 'delimiter',
            'label' => '──────────',
            'disabled' => true,
        ]];
        foreach ($countryOptions as $countryOption) {
            if (empty($countryOption['value']) || in_array($countryOption['value'], $topCountryCodes)) {
                $headOptions[] = $countryOption;
            } else {
                $tailOptions[] = $countryOption;
            }
        }
        return array_merge($headOptions, $tailOptions);
    }
}