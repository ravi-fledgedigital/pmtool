<?php

namespace OnitsukaTigerIndo\GoogleTagManager\Helper;


use Magento\Framework\App\Helper\Context;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Registry;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\StoreManagerInterface;

class Data extends  \Magento\Framework\App\Helper\AbstractHelper
{

    const STORE_VIEW_INDO = ['web_id_id','web_id_en'];
    const GTM_BRAND = 'Onitsuka Tiger';
    const INDO_DIMENSION = "dimension23";
    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;
    /**
     * @var array
     */
    protected $_gtmOptions;

    protected $scopeConfig;

    /**
     * @param Context $context
     * @param ScopeConfigInterface $scopeConfig
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        Context $context,
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager
    )
    {
        $this->storeManager = $storeManager;
        $this->scopeConfig = $scopeConfig;
        $this->_gtmOptions = $this->scopeConfig->getValue('weltpixel_ga4', ScopeInterface::SCOPE_STORE);
        parent::__construct($context);
    }

    /**
     * @param $sku
     * @return mixed|string|null
     */
    public function principalSku($sku = null){

        if(empty($sku)){
            return $sku;
        }
        return substr($sku,0,8);
    }

    /**
     * @return string[]
     */
    public function storeViewCodeIndo(): array
    {
        return self::STORE_VIEW_INDO;
    }
    /**
     * Only for
     * @return bool
     */
    public function validStoreCode(){

        $storeCode = $this->storeManager->getStore()->getCode();
        if( in_array($storeCode,$this->storeViewCodeIndo()) ){
            return true;
        }
        return false;
    }

    /**
     * @return array
     */
    public function getGtmOptions($general,$value){
        return $this->_gtmOptions[$general][$value];
    }
}
