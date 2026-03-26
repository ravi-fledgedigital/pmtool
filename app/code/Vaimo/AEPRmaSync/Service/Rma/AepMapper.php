<?php

/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */

declare(strict_types=1);

namespace Vaimo\AEPRmaSync\Service\Rma;

use Amasty\Rma\Api\Data\RequestInterface;
use Amasty\Rma\Api\ReasonRepositoryInterface;
use Amasty\Rma\Api\StatusRepositoryInterface;
use Amasty\Rma\Model\Request\RequestItemFactory;
use Amasty\Rma\Model\Request\ResourceModel\RequestItemCollectionFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Store\Model\StoreManagerInterface;
use Vaimo\AepBase\Api\ConfigInterface;
use Vaimo\AepEventStreaming\Api\AepMapperInterface;

class AepMapper implements AepMapperInterface
{
    /**
     * @var \Magento\Sales\Api\OrderRepositoryInterface
     */
    private OrderRepositoryInterface $orderRepository;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private StoreManagerInterface $storeManager;

    /**
     * @var \Magento\Directory\Model\CountryFactory
     */
    protected $countryFactory;

    /**
     * @var \Amasty\Rma\Api\StatusRepositoryInterface
     */
    protected $statusRepository;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var ReasonRepositoryInterface
     */
    private $rmaReason;

    /**
     * @var RequestItemCollection
     */
    private $rmaItemCollection;

    private $rmaItemFactory;

    /**
     * @var \Amasty\Rma\Model\Reason\ReasonFactory
     */
    private $reasonFactory;

    /**
     * @var \Magento\Sales\Api\OrderItemRepositoryInterface
     */
    private $orderItemRepository;
    private $requestFactory;

    private $refundAmount = 0;

    /**
     * @var Vaimo\AepEventStreaming\Helper\Data
     */
    protected $helper;

    protected $trackingCollectionFactory;

    /**
     * @param Vaimo\AepEventStreaming\Helper\Data $helper
     */
    public function __construct(
        OrderRepositoryInterface $orderRepository,
        StoreManagerInterface $storeManager,
        \Magento\Directory\Model\CountryFactory $countryFactory,
        StatusRepositoryInterface $statusRepository,
        \Amasty\Rma\Model\Request\ResourceModel\TrackingCollectionFactory $trackingCollectionFactory,
        \Amasty\Rma\Model\Request\Tracking $tracking,
        ScopeConfigInterface $scopeConfig,
        ReasonRepositoryInterface  $rmaReason,
        RequestItemCollectionFactory  $rmaItemCollection,
        RequestItemFactory $rmaItemFactory,
        \Amasty\Rma\Model\Reason\ReasonFactory $reasonFactory,
        \Magento\Sales\Api\OrderItemRepositoryInterface $orderItemRepository,
        \Amasty\Rma\Model\Request\RequestFactory $requestFactory,
        \Vaimo\AepEventStreaming\Helper\Data $helper
    ) {
        $this->orderRepository = $orderRepository;
        $this->storeManager = $storeManager;
        $this->countryFactory = $countryFactory;
        $this->statusRepository = $statusRepository;
        $this->trackingCollectionFactory = $trackingCollectionFactory;
        $this->scopeConfig = $scopeConfig;
        $this->rmaReason = $rmaReason;
        $this->rmaItemCollection = $rmaItemCollection;
        $this->rmaItemFactory = $rmaItemFactory;
        $this->reasonFactory = $reasonFactory;
        $this->orderItemRepository = $orderItemRepository;
        $this->requestFactory = $requestFactory;
        $this->helper = $helper;
    }

    // phpcs:ignore SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingTraversableTypeHintSpecification
    public function map(RequestInterface $rma): array
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

        $returnData = [];
        if (!in_array($orderStoreCode, $storeCodesArray)) {
            $rmaStatus =  $this->statusRepository->getById($rma->getStatus());

            $trackingCollection = $this->trackingCollectionFactory->create();
            $trackingCollection->addFieldToFilter(
                'request_id',
                $rma->getRequestId()
            );

            $rmaObj = $this->requestFactory->create()->load($rma->getId());
            $trackingData = [];
            foreach ($trackingCollection->getData() as $trackingData) {
                $trackingData = [
                    'tracking_code' => $trackingData['tracking_code'],
                    'tracking_number' => $trackingData['tracking_number'],
                ];
            }

            $storeLocale = $this->scopeConfig->getValue('general/locale/code', \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $rma->getStoreId());

            $returnItems = $this->getItems($rma, $order);

            $returnData = [
                'orderId' => $order->getIncrementId(),
                'ReturnOrderStatus' => $rmaStatus->getTitle(),
                'returnTrackingStatus' => 'N/A',
                'carrier' => (isset($trackingData['tracking_code'])) ? $trackingData['tracking_code'] : '',
                'carrierServiceMethod' => $order->getShippingMethod(),
                'giftRecipient' => false,
                'giftRecipientEmailAddress' => null,
                'refundAmt' => $this->refundAmount,
                'refundMethod' => null,
                'restockingAmt' => 0,
                'returnInitiatedLocale' => $storeLocale,
                'returnMethod' => 'mail',
                'returnOrderCreatedDate' =>$this->convertDateTimeFormat($rmaObj->getCreatedAt()), // already in ISO format, no need to convert
                'returnOrderItems' => $returnItems,
                'orderCreatedDate' => $this->convertDateTimeFormat($order->getCreatedAt()),
                'returnOrderStatusChangeDate' => $this->convertDateTimeFormat($rmaObj->getModifiedAt()),
                'returnOrigin' => [
                    'city' => $order->getShippingAddress()->getCity(),
                    'country' => $order->getShippingAddress()->getCountryId(),
                    'postCode' => $order->getShippingAddress()->getPostcode(),
                ],
                'rmaNumber' => $rma->getRequestId(),
                'store' => [
                    'storeId' => $this->getStoreCodeById($rma->getStoreId()),
                ],
                'trackingNumber' => (isset($trackingData['tracking_number'])) ? $trackingData['tracking_number'] : '',
                'trackingUrl' => null,
            ];

            $logger->info("-------RMA Aep Mapper Data---------");
            $logger->info(print_r($returnData, true));
        }

        return $returnData;
    }

    /**
     * load order data by rma id
     * @param $rma
     * @return obj
     */
    private function getOrder($rma)
    {
        return $this->orderRepository->get($rma->getOrderId());
    }

    /**
     * covert date in ISO format
     * @param $rma
     * @param $order
     * @return string
     */
    private function convertDateTimeFormat(?string $dateTimeString): ?string
    {
        if ($dateTimeString === null) {
            return null;
        }

        return \DateTime::createFromFormat('Y-m-d H:i:s', $dateTimeString)
            ->format(self::AEP_DATETIME_FORMAT);
    }

    /**
     * get rma items
     * @param $rma
     * @param $order
     * @return array
     */
    private function getItems(RequestInterface $rma, OrderInterface $order): array
    {
        $requestItemCollection = $this->rmaItemCollection->create();
        $requestItemCollection->addFieldToFilter('request_id', $rma->getRequestId());
        $result = [];
        $storeCode = $this->getStoreCode($order);
        $storeCodeSuffix = '';

        if ($storeCode) {
            $storeCodeSuffix = ConfigInterface::STORE_CODE_DELIMITER . $storeCode;
        }

        foreach ($rma->getRequestItems() as $item) {
            $orderItems = $this->findOrderItemBySku($order, $item->getOrderItemId());
            $reason = $this->reasonFactory->create()->load($item->getReasonId());
            $reasonName = '';
            if ($reason && $reason->getId()) {
                $reasonName = $reason->getTitle();
            }
            if (count($orderItems) > 0) {
                foreach ($orderItems as $orderItem) {
                    $result[] = [
                        'approvedQuantity' => ($rma->getStatus() == 4) ? $item->getRequestQty() : 0,
                        'itemPrice' => $orderItem->getPrice(),
                        'orderItemId' => $orderItem->getId(),
                        'requestedQuantity' => $item->getRequestQty(),
                        'returnComment' => $rma->getNote(),
                        'returnOrderItemId' => $item->getId(),
                        'returnReason' => $reasonName,
                        'returnReasonCode' => '',
                        'returnTransactionType' => '',
                        'returnedQuantity' => $item->getRequestQty(),
                        'skuStoreViewCode' => $orderItem->getSku() . $storeCodeSuffix,
                        'totalItemPrice' => $orderItem->getPrice() * $item->getRequestQty(),
                    ];
                    if($orderItem->getParentItemId()) {
                        $this->refundAmount = $orderItem->getPrice() * $item->getRequestQty();
                    }
                }
            }
        }

        return $result;
    }

    /**
     * get order Items by order sku
     * @param $sku
     * @param $order
     * @return array
     */
    private function findOrderItemBySku(OrderInterface $order, $orderItemId): ?array
    {
        $candidates = [];

        $orderItem = $this->orderItemRepository->get($orderItemId);
        if ($orderItem && $orderItem->getId()) {
            $candidates[$orderItem->getId()] = $orderItem;
            if ($orderItem->getParentItemId()) {
                $parentOrderItem = $this->orderItemRepository->get($orderItem->getParentItemId());
                if ($parentOrderItem && $parentOrderItem->getId()) {
                    $candidates[$parentOrderItem->getId()] = $parentOrderItem;
                }
            }
        }
        /*foreach ($order->getItems() as $item) {
            if ($item->getId() != $orderItemId || isset($candidates[$item->getId()])) {
                continue;
            }

            $candidates[$item->getId()] = $item;
            echo '<pre>';print_r(json_decode(json_encode($item->getData())));die;
            // phpcs:ignore SlevomatCodingStandard.ControlStructures.EarlyExit.EarlyExitNotUsed
            if ($item->getParentItemId()) {
                $candidates[$item->getParentItemId()] = $item;
            }
        }*/
        return $candidates;
        //return reset($candidates) ?: null;
    }

    /**
     * get store code
     * @param $order
     * @return string
     */
    private function getStoreCode(OrderInterface $order): ?string
    {
        try {
            $store = $this->storeManager->getStore($order->getStoreId());
        } catch (NoSuchEntityException $e) {
            return null;
        }

        return $store->getCode();
    }

    private function getStoreCodeById($storeId)
    {
        try {
            $store = $this->storeManager->getStore($storeId);
        } catch (NoSuchEntityException $e) {
            return null;
        }

        return $store->getCode();
    }
}
