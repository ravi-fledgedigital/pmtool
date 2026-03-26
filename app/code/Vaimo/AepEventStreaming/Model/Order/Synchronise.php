<?php

/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */

declare(strict_types=1);

namespace Vaimo\AepEventStreaming\Model\Order;

use Magento\Sales\Api\Data\OrderInterface;
use Psr\Http\Message\ResponseInterface;
use Vaimo\AepEventStreaming\Api\Data\IngestRecordInterfaceFactory;
use Vaimo\AepEventStreaming\Api\RestClientInterface;
use Vaimo\AepEventStreaming\Exception\GuestOrderException;
use Vaimo\AepEventStreaming\Exception\MissingActionIdException;
use Vaimo\AepEventStreaming\Exception\OrderSyncException;
use Vaimo\AepEventStreaming\Model\AuthToken;
use Vaimo\AepEventStreaming\Model\Request\SendOrderFactory as RequestFactory;
use Vaimo\AepEventStreaming\Model\ResourceModel\Order as ResourceModel;
use Vaimo\AepEventStreaming\Helper\Data;

class Synchronise
{
    private RestClientInterface $restClient;
    private RequestFactory $requestFactory;
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
        AuthToken $authToken,
        IngestRecordInterfaceFactory $ingestRecordFactory,
        ResourceModel $resourceModel,
        Data $helper
    ) {
        $this->restClient = $restClient;
        $this->requestFactory = $requestFactory;
        $this->authToken = $authToken;
        $this->ingestRecordFactory = $ingestRecordFactory;
        $this->resourceModel = $resourceModel;
        $this->helper = $helper;
    }

    /**
     * @throws OrderSyncException
     * @throws MissingActionIdException
     * @throws GuestOrderException
     */
    public function syncWithAep(OrderInterface $order): void
    {
        /*$writer = new \Zend_Log_Writer_Stream(BP . "/var/log/aep/order_sync_data.log");
        $logger = new \Zend_Log();
        $logger->addWriter($writer);

        $logger->info("==== syncWithAep order store Id ------ ====");
        $logger->info($order->getStoreId());*/

        $orderStoreCode =  $this->helper->getStoreCodeById($order->getStoreId());
        $storeCodes = $this->helper->getExcludeStoreStreaming();

        /*$logger->info("==== syncWithAep order StoreCode ====");
        $logger->info(print_r($orderStoreCode, true));

        $logger->info("==== syncWithAep config storeCodes ====");
        $logger->info(print_r($storeCodes, true));*/

        /*$logger->info("=============");*/

        $storeCodesArray = [];
        if (!empty($storeCodes)) {
            $storeCodesArray = explode(',', $storeCodes);
        }

        /*$logger->info("==== syncWithAep exploded StoreCode====");
        $logger->info(print_r($storeCodesArray, true));

        $logger->info("==== syncWithAep check condition if not in_array ====");
        if (!in_array($orderStoreCode, $storeCodesArray)) {
             $logger->info("==== called if  ====");
        }else{
            $logger->info("==== called else ====");
        }*/

        if (!in_array($orderStoreCode, $storeCodesArray)) {
            if ($order->getCustomerIsGuest()) {
                throw new GuestOrderException(\sprintf(
                    "[AEP] Guest orders can't be sent to AEP: Order id: %s",
                    $order->getEntityId()
                ));
            }
            $response = $this->sendRequest($order);

            /*$logger->info("-----Api response Order----");
            $logger->info(print_r($response, true));*/

            if ($response->getStatusCode() == RestClientInterface::HTTP_CODE_UNAUTHORISED) {
                // could be that token is expired so let's try again with a new one
                $this->authToken->flushCache();
                $response = $this->sendRequest($order);
            }

            if ($response->getStatusCode() !== RestClientInterface::HTTP_CODE_OK) {
                throw new OrderSyncException(\sprintf(
                    '[AEP] Failed to sync order id: %s. AEP response code %s. Response body: %s',
                    $order->getId(),
                    $response->getStatusCode(),
                    (string) $response->getBody()
                ));
            }

            try {
                $ingestRecord =  $this->ingestRecordFactory->create(['response' => $response]);
            } catch (\InvalidArgumentException $e) {
                throw new OrderSyncException(\sprintf(
                    '[AEP] Invalid response while syncing order. Id: %s. %s',
                    $order->getId(),
                    $e->getMessage()
                ), null, $e);
            } catch (MissingActionIdException $e) {
                throw new OrderSyncException(\sprintf(
                    '[AEP] Missing action id while syncing order. Id: %s.',
                    $order->getId()
                ), null, $e);
            }

            $this->resourceModel->updateLastActionId(
                (int) $order->getId(),
                $ingestRecord->getActionId()
            );
        }
    }

    private function sendRequest(OrderInterface $order): ResponseInterface
    {
        $request = $this->requestFactory->create(['order' => $order]);

        return $this->restClient->sendRequest($request->buildRequest());
    }
}
