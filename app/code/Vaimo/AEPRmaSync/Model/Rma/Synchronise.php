<?php

/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */

declare(strict_types=1);

namespace Vaimo\AEPRmaSync\Model\Rma;

use Psr\Http\Message\ResponseInterface;
use Vaimo\AepEventStreaming\Api\RestClientInterface;
use Vaimo\AepEventStreaming\Model\AuthToken;
use Vaimo\AEPRmaSync\Exception\RmaSyncException;
use Vaimo\AEPRmaSync\Model\Request\SendRmaFactory as RequestFactory;
use Amasty\Rma\Api\Data\RequestInterface;
use Vaimo\AepEventStreaming\Helper\Data;
use Magento\Sales\Api\OrderRepositoryInterface;

class Synchronise
{
    private RestClientInterface $restClient;
    private RequestFactory $requestFactory;
    private AuthToken $authToken;
    /**
     * @var Vaimo\AepEventStreaming\Helper\Data
     */
    protected $helper;

    /**
     * @var \Magento\Sales\Api\OrderRepositoryInterface
     */
    protected OrderRepositoryInterface $orderRepository;

    /**
     * @param Vaimo\AepEventStreaming\Helper\Data $helper
     * @param Magento\Sales\Api\OrderRepositoryInterface $orderRepository
     */
    public function __construct(
        RestClientInterface $restClient,
        RequestFactory $requestFactory,
        AuthToken $authToken,
        Data $helper,
        OrderRepositoryInterface $orderRepository
    ) {
        $this->restClient = $restClient;
        $this->requestFactory = $requestFactory;
        $this->helper = $helper;
        $this->orderRepository = $orderRepository;
    }

    /**
     * @throws RmaSyncException
     */
    public function syncWithAep(RequestInterface $rma): void
    {
        $writer = new \Zend_Log_Writer_Stream(BP . "/var/log/aep/rma_response_data.log");
        $logger = new \Zend_Log();
        $logger->addWriter($writer);

        $order = $this->getOrder($rma);
        
        $logger->info("==== syncWithAep order store Id ------ ====");
        $logger->info($order->getStoreId());

        $orderStoreCode =  $this->helper->getStoreCodeById($order->getStoreId());
        $storeCodes = $this->helper->getExcludeStoreStreaming();

        $logger->info("==== syncWithAep order StoreCode ====");
        $logger->info(print_r($orderStoreCode, true));

        $logger->info("==== syncWithAep config storeCodes ====");
        $logger->info(print_r($storeCodes, true));

        $logger->info("=============");

        $storeCodesArray = [];
        if (!empty($storeCodes)) {
            $storeCodesArray = explode(',', $storeCodes);
        }

        $logger->info("==== syncWithAep exploded StoreCode====");
        $logger->info(print_r($storeCodesArray, true));

        $logger->info("==== syncWithAep check condition if not in_array ====");
        if (!in_array($orderStoreCode, $storeCodesArray)) {
             $logger->info("==== called if  ====");
        }else{
            $logger->info("==== called else ====");
        }

        if (!in_array($orderStoreCode, $storeCodesArray)) {
            $response = $this->sendRequest($rma);
            $logger->info("------------ APi Rma response ------------");
            $logger->info(print_r($response, true));
            $logger->info("------------ APi Rma response end ------------");

            if ($response->getStatusCode() == RestClientInterface::HTTP_CODE_UNAUTHORISED) {
                // could be that token is expired so let's try again with a new one
                $this->authToken->flushCache();
                $response = $this->sendRequest($rma);
            }

            if ($response->getStatusCode() !== RestClientInterface::HTTP_CODE_OK) {
                throw new RmaSyncException(\sprintf(
                    '[AEP] Failed to sync order id: %s. AEP response code %s. Response body: %s',
                    $rma->getId(),
                    $response->getStatusCode(),
                    (string) $response->getBody()
                ));
            }
        }

        // @todo update RMA with last action id. Requires implementing extension attributes in Narvar RMA module
    }

    public function sendRequest(RequestInterface $rma)
    {
        $writer = new \Zend_Log_Writer_Stream(BP . "/var/log/aep/rma_response_data.log");
        $logger = new \Zend_Log();
        $logger->addWriter($writer);

        $logger->info('----rma send request data start---');
        $logger->info(print_r(json_decode(json_encode($rma->getData())), true));
        $logger->info('----rma  send request data end---');

        $request = $this->requestFactory->create(['rma' => $rma]);

        $logger->info('----rma request start---');
        $logger->info(print_r(json_decode(json_encode($request)), true));
        $logger->info('----rma  request end---');

        return $this->restClient->sendRequest($request->buildRequest());
    }

    /**
     * load order data by rma id
     * @param $rma
     * @return obj
     */
    private function getOrder(RequestInterface $rma)
    {
        return $this->orderRepository->get($rma->getOrderId());
    }
}