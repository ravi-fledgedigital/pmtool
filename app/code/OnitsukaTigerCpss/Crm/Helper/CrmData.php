<?php
namespace OnitsukaTigerCpss\Crm\Helper;

use Cpss\Crm\Logger\Logger;
use League\ISO3166\ISO3166 as CountryList;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Encryption\Encryptor;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Store\Model\ScopeInterface;

class CrmData extends \Cpss\Crm\Helper\Data
{
    /**
     * Get country path
     */
    const COUNTRY_CODE_PATH = 'general/country/default';
    protected $customerSession;
    protected $countryList;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;
    public function __construct(
        TimezoneInterface $timezoneInterface,
        Logger $logger,
        Session $customerSession,
        ScopeConfigInterface $scopeConfig,
        Encryptor $encryptor,
        CountryList $countryList)
    {
        $this->scopeConfig = $scopeConfig;
        $this->customerSession = $customerSession;
        $this->countryList = $countryList;
        parent::__construct($timezoneInterface, $logger, $customerSession, $scopeConfig, $encryptor, $countryList);
    }

    /**
     * @return mixed|string
     */
    public function getCountryCode()
    {
        if ($address = $this->customerSession->getCustomer()->getDefaultBillingAddress()) {
                $isoData = $this->countryList->alpha2($address->getCountry());
                return $isoData["numeric"];
        }
        $isoData = $this->countryList->alpha2($this->scopeConfig->getValue(
            self::COUNTRY_CODE_PATH,
            ScopeInterface::SCOPE_WEBSITES)
        );
        return $isoData["numeric"];
    }

}
