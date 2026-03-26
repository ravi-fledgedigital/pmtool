<?php


namespace OnitsukaTiger\Ninja\Model;

class Config
{
    const PATH_SANDBOX = 'ninja/general/sandbox';
    const PATH_COUNTRY_CODE = 'ninja/general/country_code';
    const PATH_CLIENT_ID = 'ninja/general/client_id';
    const PATH_CLIENT_SECRET = 'ninja/general/client_secret';
    const PATH_DELIVERY_END_TIME = 'ninja/general/delivery_end_time';
    const PATH_PICKUP_END_TIME = 'ninja/general/pickup_end_time';

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {
        $this->scopeConfig = $scopeConfig;
    }

    public function get($path, $websiteId)
    {
        return $this->scopeConfig->getValue(
            $path,
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE,
            $websiteId
        );
    }
}
