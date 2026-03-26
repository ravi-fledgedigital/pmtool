<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\PlatformNetsuite\UI\Component\Listing\Column\PaymentMethods\NetsuiteAccounts;

use Magento\Framework\Data\OptionSourceInterface;
use NetSuite\Classes\BaseRef;
use NetSuite\Classes\RecordRef;
use NetSuite\Classes\RecordType;

/**
 * Class Options
 */
class Options implements OptionSourceInterface
{
    protected $paymentsData;

    /**
     * @var array
     */
    protected $options;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var array
     */
    protected $config = [];

    /**
     * @var \NetSuite\NetSuiteService
     */
    protected $service;

    /**
     * Options constructor.
     * @param \Magento\Payment\Helper\Data $paymentsData
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        \Magento\Payment\Helper\Data $paymentsData,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig

    ) {
        $this->paymentsData = $paymentsData;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Get options
     *
     * @return array
     */
    public function toOptionArray()
    {
        $this->options = [];
        $accountInternalIds = [];
        $accountSavedSearchId = $this->scopeConfig->getValue(
            'firebear_importexport/netsuite/account_saved_search_id'
        );
        $numberOfSavedSearchRecords = $this->scopeConfig->getValue(
            'firebear_importexport/netsuite/number_of_records_in_account_ss'
        );
        if (!$numberOfSavedSearchRecords) {
            $numberOfSavedSearchRecords = 20;
        }
        if ($accountSavedSearchId) {
            $this->initService();
            $this->service->setSearchPreferences(false, $numberOfSavedSearchRecords * 2);
            $search = new \NetSuite\Classes\AccountSearchAdvanced();
            $search->savedSearchId = $accountSavedSearchId;
            $request = new \NetSuite\Classes\SearchRequest();
            $request->searchRecord = $search;
            $searchResponse = $this->service->search($request);
            if ($searchResponse->searchResult->status && $searchResponse->searchResult->searchRowList) {
                $accountsList = $searchResponse->searchResult->searchRowList->searchRow;
                foreach ($accountsList as $account) {
                    $internalId = $account->basic->internalId[0]->searchValue->internalId;
                    $accountName = $account->basic->name[0]->searchValue;
                    if ($internalId && $accountName && !array_contains($accountInternalIds, $internalId)) {
                        $this->options[] = [
                            'label' => $accountName,
                            'value' => $internalId
                        ];
                        $accountInternalIds[] = $internalId;
                    }
                }
            }
        }
        return $this->options;
    }

    /**
     * @param $config
     */
    protected function initService()
    {
        $options = [
            'connection_timeout' => 6000,
            'keep_alive' => true
        ];

        $this->service = new \NetSuite\NetSuiteService($this->getConfig(), $options);
    }

    /**
     * @return array
     */
    private function getConfig()
    {
        if (empty($this->config)) {
            $this->config = [
                "endpoint" => \trim($this->scopeConfig->getValue('firebear_importexport/netsuite/endpoint')),
                "host"     => \trim($this->scopeConfig->getValue('firebear_importexport/netsuite/host')),
                "account"  => \trim($this->scopeConfig->getValue('firebear_importexport/netsuite/account')),
                "consumerKey" => \trim($this->scopeConfig->getValue('firebear_importexport/netsuite/consumerKey')),
                "consumerSecret" => \trim(
                    $this->scopeConfig->getValue('firebear_importexport/netsuite/consumerSecret')
                ),
                "token" => \trim($this->scopeConfig->getValue('firebear_importexport/netsuite/token')),
                "tokenSecret" => \trim($this->scopeConfig->getValue('firebear_importexport/netsuite/tokenSecret')),
            ];
        }
        return $this->config;
    }
}
