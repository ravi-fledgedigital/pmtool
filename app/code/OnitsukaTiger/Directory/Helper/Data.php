<?php

namespace OnitsukaTiger\Directory\Helper;


/**
 * Class Data
 */
class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * @var \OnitsukaTiger\Fixture\Helper\Data
     */
    private $helperConfig;
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $_storeManager;

    /**
     * Data constructor.
     * @param \OnitsukaTiger\Fixture\Helper\Data $helperConfig
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\App\Helper\Context $context
     */
    public function __construct(
        \OnitsukaTiger\Fixture\Helper\Data $helperConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\App\Helper\Context $context
    ) {
        $this->helperConfig = $helperConfig;
        $this->_storeManager = $storeManager;
        parent::__construct($context);
    }

    /**
     * @return mixed|null
     */
    public function getTelephoneCountryCode(){
        if ($this->helperConfig->getConfig('general/telephone_prefix/enable')) {
            return null;
        }
        return  $this->helperConfig->getConfig('general/telephone_prefix/number');
    }

    /**
     * @return mixed
     */
    public function isShowTelephonePrefix(){
        return $this->helperConfig->getConfig('general/telephone_prefix/enable');
    }

    /**
     * Get store identifier
     *
     * @return  int
     */
    public function getStoreId()
    {
        return $this->_storeManager->getStore()->getId();
    }

    /**
     * @param $storeId
     * @return mixed
     */
    public function getLocaleByStoreId($storeId)
    {
        return $this->scopeConfig->getValue('general/locale/code', ScopeInterface::SCOPE_STORE, $storeId);
    }
}
