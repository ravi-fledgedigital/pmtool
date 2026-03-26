<?php
 
namespace OnitsukaTiger\PreOrders\Helper;
 
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;

/**
 * Class Create Data
 */
class Data extends AbstractHelper
{
    /**
     * @var ScopeConfigInterface
     */
        protected $scopeConfig;

    /**
     * @var array
     */
    public $productIdsArr;

    /**
     * @var \Magento\Framework\App\Http\Context
     */
    public $httpContext;

    /**
     * @param Context $context
     * @param ScopeConfigInterface $scopeConfig
     * @param Magento\Framework\App\Http\Context $httpContext
     */
    public function __construct(
        Context $context,
        ScopeConfigInterface $scopeConfig,
        \Magento\Framework\App\Http\Context $httpContext

    ) {
        parent::__construct($context);
        $this->scopeConfig = $scopeConfig;
        $this->httpContext = $httpContext;
    }

    /**
     * @return bool
     */
    public function isModuleEnabled($storeId = null)
    {
        $isEnabled = $this->getConfigValue("preorders/general/ispreorderenable",$storeId);
        return (bool) $isEnabled;
    }

    /**
     * @param $path
     * @return mixed
     */
    public function getConfigValue($path,$storeId = null)
    {
        return $this->scopeConfig->getValue(
            $path,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE 
            , $storeId
        );
    }

    /**
     * Get warehouse code
     *
     * @return array
     */
    public function getWarehouseCode()
    {
        $wareHouseCode = $this->scopeConfig->getValue('preorders/general/warehouse_code');

        if (!empty($wareHouseCode)) {
            return explode(',', $wareHouseCode);
        }
        return [];
    }

    /**
     * check customer is Logged in
     *
     * @return bool
     */
    public function isLoggedIn()
    {
        $isLoggedIn = $this->httpContext->getValue(\Magento\Customer\Model\Context::CONTEXT_AUTH);
        return $isLoggedIn;
    }
}
