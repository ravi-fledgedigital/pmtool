<?php
namespace Cpss\Crm\Helper;

use Cpss\Crm\Helper\AbstractHelper;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Encryption\Encryptor;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Cpss\Crm\Logger\Logger;
use League\ISO3166\ISO3166 as CountryList;

class Data extends AbstractHelper
{
    const SITE_URL_PATH = 'crm/general/url';
    protected $customerSession;
    protected $countryList;

    public function __construct(
        TimezoneInterface $timezoneInterface,
        Logger $logger,
        Session $customerSession,
        ScopeConfigInterface $scopeConfig,
        Encryptor $encryptor,
        CountryList $countryList
    ) {
        $this->customerSession = $customerSession;
        $this->countryList = $countryList;
        parent::__construct($scopeConfig, $timezoneInterface, $logger, $encryptor);
    }

    public function getCpssApiBaseUrl()
    {
        return $this->getConfigValue(self::SITE_URL_PATH);
    }

    public function getEnv()
    {
        return 'STG';
    }

    public function getAuthBearer()
    {
        $token = 'b3RzdGc6c3Rnb3Q=';

        return 'Authorization: Bearer ' . $token;
    }

    public function getCountryCode()
    {
        if ($address = $this->customerSession->getCustomer()->getDefaultBillingAddress()) {
                $isoData = $this->countryList->alpha2($address->getCountry());
                return $isoData["numeric"];
        } else {
            return "392"; // Japan
        }
    }
}
