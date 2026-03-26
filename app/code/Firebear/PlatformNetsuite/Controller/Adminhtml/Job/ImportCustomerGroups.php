<?php
/**
 * @copyright: Copyright © 2021 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\PlatformNetsuite\Controller\Adminhtml\Job;

use Firebear\ImportExport\Controller\Adminhtml\Context;
use Firebear\ImportExport\Controller\Adminhtml\Job as JobController;
use Magento\Customer\Api\Data\GroupInterface;
use Magento\Customer\Api\GroupRepositoryInterface;
use Magento\Customer\Model\ResourceModel\Group\Collection as GroupCollection;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\ResultInterface;
use NetSuite\Classes\PriceLevelSearchAdvanced;
use NetSuite\Classes\SearchRequest;
use NetSuite\NetSuiteService;

/**
 * Class ImportCustomerGroups
 *
 * @package Firebear\PlatformNetsuite\Controller\Adminhtml\Job
 */
class ImportCustomerGroups extends JobController
{
    /**
     * @var GroupInterface
     */
    protected $customerGroup;

    /**
     * @var GroupRepositoryInterface
     */
    protected $customerGroupRepository;

    /**
     * @var NetSuiteService
     */
    protected $service;

    /**
     * @var GroupCollection
     */
    protected $customerGroupCollection;

    /**
     * ImportCustomerGroups constructor.
     *
     * @param Context $context
     * @param GroupInterface $group
     * @param GroupRepositoryInterface $groupRepository
     * @param GroupCollection $customerGroup
     */
    public function __construct(
        Context $context,
        GroupInterface $group,
        GroupRepositoryInterface $groupRepository,
        GroupCollection $customerGroup
    ) {
        parent::__construct($context);
        $this->customerGroup = $group;
        $this->customerGroupRepository = $groupRepository;
        $this->customerGroupCollection = $customerGroup;
    }

    /**
     * @return ResultInterface
     */
    public function execute()
    {
        /** @var Json $resultJson */
        $resultJson = $this->resultFactory->create($this->resultFactory::TYPE_JSON);
        $messages = [];
        if ($this->getRequest()->isAjax()) {
            $formData = $this->getRequest()->getParam('form_data');
            $sourceType = $this->getRequest()->getParam('source_type');
            $importData = [];
            foreach ($formData as $data) {
                $index = strstr($data, '+', true);
                $index = str_replace($sourceType . '[', '', $index);
                $index = str_replace(']', '', $index);
                $importData[$index] = substr($data, strpos($data, '+') + 1);
            }
            $priceLevelSavedSearchId = $importData['price_level_saved_search_id'];
            if ($priceLevelSavedSearchId) {
                $this->initService($importData);
                $priceLevelSavedSearch = new PriceLevelSearchAdvanced();
                $priceLevelSavedSearch->savedSearchId = $importData['price_level_saved_search_id'];
                $priceLevelSearchRequest = new SearchRequest();
                $priceLevelSearchRequest->searchRecord = $priceLevelSavedSearch;
                $priceLevelSearchResponse = $this->service->search($priceLevelSearchRequest);
                if ($priceLevelSearchResponse->searchResult->status->isSuccess) {
                    $priceLevels = $priceLevelSearchResponse->searchResult->searchRowList->searchRow;
                    foreach ($priceLevels as $priceLevel) {
                        $priceLevelName = $priceLevel->basic->name[0]->searchValue;
                        $customerGroup = $this->customerGroupCollection
                            ->getItemByColumnValue('customer_group_code', $priceLevelName);
                        if (!$customerGroup) {
                            $customerGroup = $this->customerGroup->setCode($priceLevelName);
                            try {
                                $this->customerGroupRepository->save($customerGroup);
                            } catch (\Exception $e) {
                                return $resultJson->setData(['error' => [$e->getMessage()]]);
                            }
                        }
                    }
                } else {
                    return $resultJson->setData(
                        ['error' => [$priceLevelSearchResponse->searchResult->status->statusDetail[0]->message]]
                    );
                }
            } else {
                return $resultJson->setData(['error' => [__('The Price Level Saved Search Id isn\'t set')]]);
            }
        }
        $data = count($messages) ? ['error' => $messages] : [];
        return $resultJson->setData($data);
    }

    /**
     * @param $config
     */
    protected function initService($config)
    {
        $config = [
            "endpoint" => $config['endpoint'],
            "host"     => $config['host'],
            "account"  => $config['account'],
            "consumerKey" => $config['consumerKey'],
            "consumerSecret" => $config['consumerSecret'],
            "token" => $config['token'],
            "tokenSecret" => $config['tokenSecret'],
            "use_old_http_protocol_version" => $config['use_old_http_protocol_version']
        ];

        $options = [
            'connection_timeout' => 6000,
            'keep_alive' => true
        ];

        if (!empty($config['use_old_http_protocol_version'])) {
            $options['stream_context'] = stream_context_create(
                ['http' => ['protocol_version' => 1.0]]
            );
        }
        $this->service = new NetSuiteService($config, $options);
    }
}
