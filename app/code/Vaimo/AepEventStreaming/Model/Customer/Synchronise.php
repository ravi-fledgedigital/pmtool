<?php

/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */

declare(strict_types=1);

namespace Vaimo\AepEventStreaming\Model\Customer;

use Magento\Customer\Api\Data\CustomerInterface;
use Psr\Http\Message\ResponseInterface;
use Vaimo\AepEventStreaming\Api\Data\IngestRecordInterfaceFactory;
use Vaimo\AepEventStreaming\Api\RestClientInterface;
use Vaimo\AepEventStreaming\Exception\CustomerSyncException;
use Vaimo\AepEventStreaming\Exception\MissingActionIdException;
use Vaimo\AepEventStreaming\Model\AuthToken;
use Vaimo\AepEventStreaming\Model\Request\SendCustomerFactory as RequestFactory;
use Vaimo\AepEventStreaming\Model\ResourceModel\Customer as ResourceModel;
use Vaimo\AepEventStreaming\Service\Customer\IntegrationHash;
use Vaimo\AepEventStreaming\Helper\Data;

class Synchronise
{
    private RestClientInterface $restClient;
    private RequestFactory $requestFactory;
    private IntegrationHash $integrationHash;
    private AuthToken $authToken;
    private IngestRecordInterfaceFactory $ingestRecordFactory;
    private ResourceModel $resourceModel;
    /**
     * @var Vaimo\AepEventStreaming\Helper\Data
     */
    protected $helper;

    /**
     * @param Vaimo\AepEventStreaming\Helper\Data $helper
     */
    public function __construct(
        RestClientInterface $restClient,
        RequestFactory $requestFactory,
        IntegrationHash $integrationHash,
        AuthToken $authToken,
        IngestRecordInterfaceFactory $ingestRecordFactory,
        ResourceModel $resourceModel,
        Data $helper
    ) {
        $this->restClient = $restClient;
        $this->requestFactory = $requestFactory;
        $this->integrationHash = $integrationHash;
        $this->authToken = $authToken;
        $this->ingestRecordFactory = $ingestRecordFactory;
        $this->resourceModel = $resourceModel;
        $this->helper = $helper;
    }

    /**
     * @throws CustomerSyncException
     * @throws MissingActionIdException
     */
    public function syncWithAep(CustomerInterface $customer): void
    {

       // $writer = new \Zend_Log_Writer_Stream(BP . "/var/log/aep/customer_sync_data.log");
       // $logger = new \Zend_Log();
       // $logger->addWriter($writer);

        $configWebsiteIds = $this->helper->getExcludeWebsiteStreaming();

        //$logger->info("==== syncWithAep customer configWebsiteIds ====");
        //$logger->info(print_r($configWebsiteIds, true));

        $websiteIds = [];
        if(!empty($configWebsiteIds)) {
            $websiteIds = explode(',', $configWebsiteIds);
        }

       // $logger->info("====syncWithAep customer exploded websiteIds ====");
        //$logger->info(print_r($websiteIds, true));

        //$logger->info("==================================");
        //$logger->info("=== syncWithAep customer website===");
        //$logger->info($customer->getWebsiteId());

        //$logger->info("=== syncWithAep customer in not in_array ===");
        //if (!in_array($customer->getWebsiteId(), $websiteIds)) {
          //  $logger->info("=== if called ===");
        //}else{
            //$logger->info("=== else called ===");
        //}
        if (!in_array($customer->getWebsiteId(), $websiteIds)) {
            $response = $this->sendRequest($customer);

          //  $logger->info("-----Customer Api Response---");
            //$logger->info(print_r($response, true));

            if ($response->getStatusCode() == RestClientInterface::HTTP_CODE_UNAUTHORISED) {
                // could be that token is expired so let's try again with a new one
                $this->authToken->flushCache();
                $response = $this->sendRequest($customer);
            }

            if ($response->getStatusCode() !== RestClientInterface::HTTP_CODE_OK) {
                throw new CustomerSyncException(\sprintf(
                    '[AEP] Failed to sync customer id: %s. AEP response code %s. Response body: %s',
                    $customer->getId(),
                    $response->getStatusCode(),
                    (string) $response->getBody()
                ));
            }

            try {
                $ingestRecord =  $this->ingestRecordFactory->create(['response' => $response]);
            } catch (\InvalidArgumentException $e) {
                throw new CustomerSyncException(\sprintf(
                    '[AEP] Invalid response while syncing customer. Id: %s. %s',
                    $customer->getId(),
                    $e->getMessage()
                ), null, $e);
            } catch (MissingActionIdException $e) {
                throw new CustomerSyncException(\sprintf(
                    '[AEP] Missing action id while syncing customer. Id: %s.',
                    $customer->getId()
                ), null, $e);
            }

            $newHash = $this->integrationHash->calculateHash($customer);

            $this->resourceModel->updateAepAttributes(
                (int) $customer->getId(),
                $newHash,
                $ingestRecord->getActionId()
            );
        }
    }

    private function sendRequest(CustomerInterface $customer): ResponseInterface
    {
        $request = $this->requestFactory->create(['customer' => $customer]);

        return $this->restClient->sendRequest($request->buildRequest());
    }
}
