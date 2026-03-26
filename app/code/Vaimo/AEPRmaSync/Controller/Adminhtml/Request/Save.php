<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Vaimo\AEPRmaSync\Controller\Adminhtml\Request;

use Amasty\Rma\Api\Data\MessageInterface;
use Amasty\Rma\Api\Data\RequestInterface;
use Amasty\Rma\Api\Data\RequestItemInterface;
use Amasty\Rma\Api\RequestRepositoryInterface;
use Amasty\Rma\Api\StatusRepositoryInterface;
use Amasty\Rma\Controller\Adminhtml\Request\Save as SaveRma;
use Amasty\Rma\Controller\Adminhtml\RegistryConstants;
use Amasty\Rma\Model\Chat\ResourceModel\CollectionFactory as MessageCollectionFactory;
use Amasty\Rma\Model\ConfigProvider;
use Amasty\Rma\Model\OptionSource\Grid;
use Amasty\Rma\Model\Request\Email\EmailRequest;
use Amasty\Rma\Observer\RmaEventNames;
use Amasty\Rma\Utils\Email;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use OnitsukaTiger\Rma\Helper\Data;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Store\Model\StoreManagerInterface;
use Amasty\Rma\Api\Data\ReasonInterfaceFactory;
use Vaimo\AepEventStreaming\Api\ConfigInterface;

class Save extends SaveRma
{
    private const REQUEST_NAME = 'aep.rma.sync';
    private const CONTENT_TYPE = 'application/vnd.adobe.xed-full+json;version=1.0';
    private const XPATH_RMA_ENDPOINT = 'aep_event_streaming/rma_sync/endpoint';
    private const XPATH_RMA_SCHEMA_ID = 'aep_event_streaming/rma_sync/schema_id';
    private const XPATH_RMA_DATASET_ID = 'aep_event_streaming/rma_sync/dataset_id';
    private const XPATH_RMA_FLOW_ID = 'aep_event_streaming/rma_sync/flow_id';
    public const AEP_DATE_FORMAT = 'Y-m-d';
    public const AEP_DATETIME_FORMAT = 'Y-m-d\TH:i:s\Z';

    /**
     * @var RequestRepositoryInterface
     */
    protected RequestRepositoryInterface $repository;

    /**
     * @var DataObject
     */
    protected DataObject $dataObject;

    /**
     * @var Data
     */
    protected Data $helperRma;

      /**
     * @var Magento\Sales\Api\OrderRepositoryInterface
     */
    protected OrderRepositoryInterface $orderRepository;

    /**
     * @var Magento\Store\Model\StoreManagerInterface
     */
    protected StoreManagerInterface $storeManager;

    /**
     * @var \Magento\Framework\Event\ManagerInterface
     */
    private $eventManager;

    /**
     * @var \Magento\Directory\Model\CountryFactory
     */
    protected $countryFactory;
    
    /**
     * @var ReasonInterfaceFactory
     */
    protected $rmaReason;

    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var \Magento\Catalog\Api\pricingHelper
     */
    protected $pricingHelper;

    /**
     * @var \Vaimo\AepEventStreaming\Api\ConfigInterface
     */
    private ConfigInterface $config;

    public function __construct(
        Context $context,
        RequestRepositoryInterface $repository,
        MessageCollectionFactory $messageCollectionFactory,
        DataPersistorInterface $dataPersistor,
        EmailRequest $emailRequest,
        ConfigProvider $configProvider,
        DataObject $dataObject,
        ScopeConfigInterface $scopeConfig,
        StatusRepositoryInterface $statusRepository,
        Email $email,
        Grid $grid,
        Data $helperRma,
        OrderRepositoryInterface $orderRepository,
        StoreManagerInterface $storeManager,
        \Magento\Directory\Model\CountryFactory $countryFactory,
        ReasonInterfaceFactory $rmaReason,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Magento\Framework\Pricing\Helper\Data $pricingHelper,
        ConfigInterface $config
        
    ) {
        parent::__construct(
            $context,
            $repository,
            $messageCollectionFactory,
            $dataPersistor,
            $emailRequest,
            $configProvider,
            $dataObject,
            $scopeConfig,
            $statusRepository,
            $email,
            $grid
        );
        $this->dataObject = $dataObject;
        $this->repository = $repository;
        $this->helperRma = $helperRma;
        $this->orderRepository = $orderRepository;
        $this->storeManager = $storeManager;
        $this->eventManager = $context->getEventManager() ?: \Magento\Framework\App\ObjectManager::getInstance()
        ->get(\Magento\Framework\Event\ManagerInterface::class);
        $this->countryFactory = $countryFactory;
        $this->rmaReason = $rmaReason;
        $this->productRepository = $productRepository;
        $this->pricingHelper = $pricingHelper;
        $this->configProvider = $configProvider;
        $this->messageCollectionFactory = $messageCollectionFactory;
        $this->statusRepository = $statusRepository;
        $this->config = $config;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        if ($this->getRequest()->getParams()) {
            try {
                if (!($requestId = (int)$this->getRequest()->getParam(RegistryConstants::REQUEST_ID))) {
                    return $this->resultRedirectFactory->create()->setPath('*/*/pending');
                }

                $model = $this->repository->getById($requestId);
                $this->processItems($model, $this->getRequest()->getParam('return_items'));
                $originalStatus = $model->getStatus();

                if ($status = $this->getRequest()->getParam(RequestInterface::STATUS)) {
                    $model->setStatus($status);
                }

                $model->setManagerId($this->getRequest()->getParam(RequestInterface::MANAGER_ID));

                if ($note = $this->getRequest()->getParam(RequestInterface::NOTE)) {
                    $model->setNote($note);
                }

                $origStatus = (int)$model->getOrigData(RequestInterface::STATUS);
                $this->repository->save($model);
              
                // prepare mapping rma Data to AEP start
                $this->mapData($model, $this->getRequest()->getParam('return_items'));
                // prepare mapping rma Data to AEP end

                $this->eventManager->dispatch(
                    RmaEventNames::RMA_SAVED_BY_MANAGER,
                    ['request' => $model]
                );
                if ($origStatus === $model->getStatus()
                    && $this->configProvider->isNotifyCustomerAboutNewMessage($model->getStoreId())
                ) {
                    $messageCollection = $this->messageCollectionFactory->create();
                    $messagesCount = $messageCollection
                        ->addFieldToFilter(MessageInterface::REQUEST_ID, $model->getRequestId())
                        ->addFieldToFilter(
                            MessageInterface::MESSAGE_ID,
                            ['gt' => $this->getRequest()->getParam('last_message_id', 0)]
                        )->addFieldToFilter(MessageInterface::IS_MANAGER, 1)
                        ->addFieldToFilter(MessageInterface::IS_READ, 0)
                        ->getSize();

                    if ($messagesCount) {
                        $emailRequest = $this->emailRequest->parseRequest($model);
                        $storeId = $model->getStoreId();
                        $this->email->sendEmail(
                            $emailRequest->getCustomerEmail(),
                            $storeId,
                            $this->scopeConfig->getValue(
                                ConfigProvider::XPATH_NEW_MESSAGE_TEMPLATE,
                                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                                $storeId
                            ),
                            ['email_request' => $emailRequest],
                            \Magento\Framework\App\Area::AREA_FRONTEND,
                            $this->configProvider->getChatSender($storeId)
                        );
                    }
                }

                $this->messageManager->addSuccessMessage(__('You saved the return request.'));

                if ($this->getRequest()->getParam('back')) {
                    $this->getOriginalGrid($status, $originalStatus);

                    return $this->resultRedirectFactory->create()
                        ->setPath('*/*/view', [RegistryConstants::REQUEST_ID => $model->getId()]);
                }
            } catch (LocalizedException $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
                //TODO $this->dataPersistor->set(RegistryConstants::REQ, $data);

                return $this->resultRedirectFactory->create()
                    ->setPath('*/*/view', [RegistryConstants::REQUEST_ID => $requestId]);
            }
        }

        $returnGrid = $this->getOriginalGrid($status, $originalStatus);

        return $this->resultRedirectFactory->create()->setPath("*/*/$returnGrid");
    }

    /**
     * @param int $status
     * @param int $originalStatus
     *
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function getOriginalGrid($status, $originalStatus)
    {
        $newGridId = $this->statusRepository->getById($status)->getGrid();
        $originalGridId = $this->statusRepository->getById($originalStatus)->getGrid();

        if (!$returnGrid = $this->_session->getAmRmaOriginalGrid()) {
            switch ($originalGridId) {
                case Grid::MANAGE:
                    $returnGrid = 'manage';
                    break;
                case Grid::PENDING:
                    $returnGrid = 'pending';
                    break;
                case Grid::ARCHIVED:
                    $returnGrid = 'archive';
                    break;
            }

            $this->_session->setAmRmaOriginalGrid($returnGrid);
        }

        if ($newGridId !== $originalGridId) {
            $newGrid = $this->grid->toArray()[$newGridId];
            $this->messageManager->addNoticeMessage(
                __('The return request has been moved to %1 grid.', $newGrid)
            );
        }

        return $returnGrid;
    }

    /**
     * @throws LocalizedException
     */
    public function processItems(\Amasty\Rma\Api\Data\RequestInterface $model, $items): void
    {
        $resultItems = [];

        $currentRequestItems = [];

        foreach ($model->getRequestItems() as $requestItem) {
            if (empty($currentRequestItems[$requestItem->getOrderItemId()])) {
                $currentRequestItems[$requestItem->getOrderItemId()] = [];
            }

            $currentRequestItems[$requestItem->getOrderItemId()][$requestItem->getRequestItemId()] = $requestItem;
        }

        foreach ($currentRequestItems as $currentRequestItem) {
            $currentItems = false;
            $requestQty = 0;

            foreach ($items as $item) {
                if (!empty($item[0]) && !empty($item[0][RequestItemInterface::REQUEST_ITEM_ID])
                    && !empty($currentRequestItem[(int)$item[0][RequestItemInterface::REQUEST_ITEM_ID]])
                ) {
                    $currentItems = $item;
                    $requestQty = $currentRequestItem[(int)$item[0][RequestItemInterface::REQUEST_ITEM_ID]]
                        ->getRequestQty();
                    break;
                }
            }

            if ($currentItems) {
                $rowItems = [];

                foreach ($currentItems as $currentItem) {
                    $currentItem = $this->dataObject->unsetData()->setData($currentItem);

                    if (!empty($currentItem->getData(RequestItemInterface::REQUEST_ITEM_ID))
                        && ($requestItem = $currentRequestItem[
                        $currentItem->getData(RequestItemInterface::REQUEST_ITEM_ID)
                        ])
                    ) {
                        $requestItem->setQty($currentItem->getData(RequestItemInterface::QTY))
                            ->setItemStatus($currentItem->getData('status'))
                            ->setResolutionId($currentItem->getData(RequestItemInterface::RESOLUTION_ID))
                            ->setConditionId($currentItem->getData(RequestItemInterface::CONDITION_ID))
                            ->setReasonId($currentItem->getData(RequestItemInterface::REASON_ID));
                        $rowItems[] = $requestItem;
                    } else {
                        $splitItem = $this->repository->getEmptyRequestItemModel();
                        $splitItem->setRequestId($requestItem->getRequestId())
                            ->setOrderItemId($requestItem->getOrderItemId())
                            ->setQty($currentItem->getData(RequestItemInterface::QTY))
                            ->setItemStatus($currentItem->getData('status'))
                            ->setResolutionId($currentItem->getData(RequestItemInterface::RESOLUTION_ID))
                            ->setConditionId($currentItem->getData(RequestItemInterface::CONDITION_ID))
                            ->setReasonId($currentItem->getData(RequestItemInterface::REASON_ID));
                        $rowItems[] = $splitItem;
                    }
                }

                $newQty = 0;

                foreach ($rowItems as $rowItem) {
                    $newQty += $rowItem->getQty();
                    $resultItems[] = $rowItem;
                }

                if (!$this->getRequest()->getParam('is_sync')) {
                    if ($newQty != $requestQty) {
                        throw new LocalizedException(__('Wrong Request Qty'));
                    }
                } else {
                    $requestItem->setRequestQty($newQty);
                }
            } elseif (!empty($currentRequestItem[0])) {
                $resultItems[] = $currentRequestItem[0];
            }
        }
        $model->setRequestItems($resultItems);
    }

    /**
     * @param  $request
     * @return array
     */
    public function mapData($rma, $retrunItems): array
    {
        $order = $this->getOrder($rma);
                
        $country = $this->countryFactory->create()->loadByCode($order->getShippingAddress()->getCountryId())->getName();
        $rmaData = [
            'orderId' => $rma->getOrderId(),
            'ReturnOrderStatus' =>$rma->getStatus(),
            'returnTrackingStatus' => '',
            'carrier' => $order->getShippingMethod(),
            'carrierServiceMethod' => '',
            'giftRecipient' => '',
            'giftRecipientEmailAddress' => '',
            'refundAmt' => ($rma->getEstimatedAmount() ? $rma->getEstimatedAmount() : ''),
            'refundMethod' => ($rma->getEstimatedAmount() ? $rma->getRefundAmount() : ''),
            'restockingAmt' => '',
            'returnInitiatedLocale' => '',
            'returnMethod' => '',
            'returnOrderCreatedDate' => $rma->getCreatedAt(),
            'returnOrderItems' => $this->getItems($retrunItems, $order),
            'orderCreatedDate' => $this->convertDateTimeFormat($order->getCreatedAt()),
            'returnOrderStatusChangeDate' => $this->convertDateTimeFormat($rma->getModifiedAt()),
            'returnOrigin' => [
                'city' => $order->getShippingAddress()->getCity(),
                'country' => $country,
                'postCode' => $order->getShippingAddress()->getPostcode(),
            ],
            'rmaNumber' => $rma->getRequestId(),
            'store' => [
                'storeId' => $order->getStoreId(),
            ],
            'trackingNumber' => '',
            'trackingUrl' => '',
        ];

        $schemaRefId = $this->config->getSchemaRefId($this->getRmaSchemaId());
        $currentDatetime  = date('Y-m-d H:i:s');

        $returnData = [
            'header' => [
                'schemaRef' => [
                    'id' => $schemaRefId,
                    'contentType' => self::CONTENT_TYPE,
                ],
                'imsOrgId' => $this->config->getOrganisationId(),
                'datasetId' => $this->getRmaDatasetId(),
                'flowId' => $this->getRmaFlowId(),
                'source' => [
                    'name' => 'Return Order DataFlow',
                ],
            ],
            'body' => [
                'xdmMeta' => [
                    'schemaRef' => [
                        'id' => $schemaRefId,
                        'contentType' => self::CONTENT_TYPE,
                    ],
                ],
                'xdmEntity' => [
                    '_id' => '/uri-reference',
                    '_onitsukatiger' => [
                        'returnOrders' => $rmaData,
                        'identity' => [
                            'customerId' => $rma->getCustomerId(),
                        ],
                    ],
                    'extSourceSystemAudit' => [
                        'lastUpdatedDate' => $this->convertDateTimeFormat($currentDatetime),
                    ],
                    'timestamp' => $this->convertDateTimeFormat($currentDatetime),
                ],
            ],
        ];

        return $returnData;
    }

    /**
     * @param  $rma
     * @return object
     */
    private function getOrder($rma)
    {
        return $this->orderRepository->get($rma->getOrderId());
    }

    /**
     * @param  $retrunItems $order
     * @return array 
     */
    private function getItems($retrunItems, $order): array
    {       
        $result = [];
        $storeCode = $this->getStoreCode($order);
        $storeCodeSuffix = '';

        $totalItemPrice = 0;
        foreach ($retrunItems as $item) {
            foreach ($item as $key => $retrunItems) {   
                $orderItem = $this->findOrderItemBySku($order, $retrunItems["sku"]);
                $totalItemPrice  = $totalItemPrice+$this->getProductBySku($retrunItems["sku"])->getPrice();               

                $result[] = [
                    'itemPrice' =>  $this->getFormatedPrice($this->getProductBySku($retrunItems["sku"])->getPrice(),true,false),
                    'orderItemId' => $this->getProductBySku($retrunItems["sku"])->getId(),
                    'returnComment' => (isset($retrunItems["comment"]) ? $retrunItems["comment"] : ''),
                    'returnOrderItemId' => $orderItem ? $orderItem->getId() : null,
                    'returnReason' => $this->rmaReason->create()->load($retrunItems["reason_id"])->getTitle(),
                    'returnReasonCode' => $retrunItems["reason_id"],
                    'returnTransactionType' =>(isset($retrunItems["transaction_type"]) ? $retrunItems["transaction_type"] : ''),
                    'returnedQuantity' => $retrunItems["qty"],
                    'skuStoreViewCode' => $retrunItems["sku"],
                    'totalItemPrice' => $this->getFormatedPrice($totalItemPrice),
                    'approvedQuantity' => (isset($retrunItems["request_qty"]) ? $retrunItems["request_qty"] :''),
                ];
            }
        }

        return $result;
    }

    /**
     * @param  $sku
     * @return object 
     */
    private function getProductBySku($sku){
        return $this->productRepository->get($sku);
    }

    /**
     * @param  $price
     * @return object 
     */
    private function getFormatedPrice($price) {
       return $this->pricingHelper->currency($price);
    }

    /**
     * @param  $order $sku
     * @return array 
     */
    private function findOrderItemBySku($order, string $sku)
    {
        $candidates = [];

        foreach ($order->getItems() as $item) {
            if ($item->getSku() != $sku || isset($candidates[$item->getId()])) {
                continue;
            }

            $candidates[$item->getId()] = $item;

            // phpcs:ignore SlevomatCodingStandard.ControlStructures.EarlyExit.EarlyExitNotUsed
            if ($item->getParentItemId()) {
                $candidates[$item->getParentItemId()] = $item;
            }
        }

        return reset($candidates) ?: null;
    }

    /**
     * @param  $order
     * @return string 
     */
    private function getStoreCode($order)
    {
        try {
            $store = $this->storeManager->getStore($order->getStoreId());
        } catch (NoSuchEntityException $e) {
            return null;
        }

        return $store->getCode();
    }

    /**
     * get rma schema id
     * @return string 
     */
    public function getRmaSchemaId(): string
    {
        return $this->scopeConfig->getValue(self::XPATH_RMA_SCHEMA_ID);
    }

    /**
     * get rma dataset id
     * @return string 
     */
    public function getRmaDatasetId(): string
    {
        return $this->scopeConfig->getValue(self::XPATH_RMA_DATASET_ID);
    }

    /**
     * get rma flow id
     * @return string 
     */
    public function getRmaFlowId(): string
    {
        return $this->scopeConfig->getValue(self::XPATH_RMA_FLOW_ID);
    }

    private function convertDateTimeFormat(?string $dateTimeString): ?string
    {
        if ($dateTimeString === null) {
            return null;
        }

        return \DateTime::createFromFormat('Y-m-d H:i:s', $dateTimeString)
            ->format(self::AEP_DATETIME_FORMAT);
    }
}
