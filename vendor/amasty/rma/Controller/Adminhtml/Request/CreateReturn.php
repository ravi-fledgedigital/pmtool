<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package RMA Base for Magento 2
 */

namespace Amasty\Rma\Controller\Adminhtml\Request;

use Amasty\Rma\Api\ChatRepositoryInterface;
use Amasty\Rma\Api\CreateReturnProcessorInterface;
use Amasty\Rma\Api\Data\MessageInterface;
use Amasty\Rma\Api\Data\RequestInterface;
use Amasty\Rma\Api\Data\RequestItemInterface;
use Amasty\Rma\Api\Data\ReturnOrderItemInterface;
use Amasty\Rma\Api\RequestRepositoryInterface;
use Amasty\Rma\Controller\Adminhtml\RegistryConstants;
use Amasty\Rma\Model\ConfigProvider;
use Amasty\Rma\Model\OptionSource\ItemStatus;
use Amasty\Rma\Observer\RmaEventNames;
use Amasty\Rma\Utils\FileUpload;
use Magento\Backend\App\Action;
use Magento\Backend\Model\Auth\Session;
use Amasty\Rma\Api\Data\RequestCustomFieldInterfaceFactory;
use Psr\Log\LoggerInterface;

class CreateReturn extends Action
{
    public const ADMIN_RESOURCE = 'Amasty_Rma::rma_create';

    /**
     * @var RequestRepositoryInterface
     */
    private $requestRepository;

    /**
     * @var CreateReturnProcessorInterface
     */
    private $createReturnProcessor;

    /**
     * @var ConfigProvider
     */
    private $configProvider;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var \Magento\Framework\Event\ManagerInterface
     */
    private $eventManager;

    /**
     * @var ChatRepositoryInterface
     */
    private $chatRepository;

    /**
     * @var Session
     */
    private $adminSession;

    /**
     * @var RequestCustomFieldInterfaceFactory
     */
    private $customFieldFactory;

    public function __construct(
        RequestRepositoryInterface $requestRepository,
        ConfigProvider $configProvider,
        CreateReturnProcessorInterface $createReturnProcessor,
        LoggerInterface $logger,
        ChatRepositoryInterface $chatRepository,
        Session $adminSession,
        Action\Context $context,
        RequestCustomFieldInterfaceFactory $customFieldFactory
    ) {
        parent::__construct($context);
        $this->requestRepository = $requestRepository;
        $this->createReturnProcessor = $createReturnProcessor;
        $this->configProvider = $configProvider;
        $this->logger = $logger;
        $this->eventManager = $context->getEventManager() ?: \Magento\Framework\App\ObjectManager::getInstance()
            ->get(\Magento\Framework\Event\ManagerInterface::class);
        $this->chatRepository = $chatRepository;
        $this->adminSession = $adminSession;
        $this->customFieldFactory = $customFieldFactory;
    }

    public function execute()
    {
        $orderId = $this->getRequest()->getParam(RequestInterface::ORDER_ID);
        $items = $this->getRequest()->getParam('return_items');
        $jsonFiles = $this->getRequest()->getParam('files');

        if ($this->getRequest()->getParams() && $orderId && $items) {
            if ($returnOrder = $this->createReturnProcessor->process($orderId, true)) {
                $request = $this->requestRepository->getEmptyRequestModel();
                $request->setNote($this->getRequest()->getParam(RequestInterface::NOTE, ''))
                    ->setStatus($this->getRequest()->getParam(RequestInterface::STATUS))
                    ->setCustomerId($returnOrder->getOrder()->getCustomerId())
                    ->setManagerId($this->getRequest()->getParam(RequestInterface::MANAGER_ID))
                    ->setOrderId($orderId)
                    ->setStoreId($returnOrder->getOrder()->getStoreId())
                    ->setCustomerName(
                        $returnOrder->getOrder()->getBillingAddress()->getFirstname()
                        . ' ' . $returnOrder->getOrder()->getBillingAddress()->getLastname()
                    );

                if ($customFields = $this->configProvider->getCustomFields($request->getStoreId())) {
                    $customFieldsData = [];
                    $formCustomFields = $this->getRequest()->getParam(RequestInterface::CUSTOM_FIELDS, []);

                    foreach ($customFields as $code => $label) {
                        if (!empty($formCustomFields[$code])) {
                            $customFieldsData[] = $this->customFieldFactory->create([
                                'key' => $code,
                                'value' => $formCustomFields[$code]
                            ]);
                        }
                    }

                    $request->setCustomFields($customFieldsData);
                }

                $items = $this->processItems($returnOrder->getItems(), $items);

                if ($items) {
                    $request->setRequestItems($items);

                    try {
                        $this->eventManager->dispatch(
                            RmaEventNames::BEFORE_CREATE_RMA_BY_MANAGER,
                            ['request' => $request]
                        );
                        $this->requestRepository->save($request);
                        $message = $this->getRequest()->getParam(MessageInterface::MESSAGE);

                        if (!empty($message) || $jsonFiles) {
                            $this->sendReturnMessage($request, $message, $jsonFiles);
                        }

                        $this->eventManager->dispatch(
                            RmaEventNames::RMA_CREATED_BY_MANAGER,
                            ['request' => $request]
                        );

                        return $this->resultRedirectFactory->create()->setPath(
                            'amrma/request/view',
                            [RegistryConstants::REQUEST_ID => $request->getRequestId()]
                        );
                    } catch (\Exception $e) {
                        $this->logger->critical($e);
                    }
                }
            }
        }
        // phpcs:ignore Magento2.Legacy.ObsoleteResponse.RedirectResponseMethodFound
        return $this->resultRedirectFactory->create()->setPath($this->_redirect->getRefererUrl());
    }

    /**
     * @param \Amasty\Rma\Api\Data\RequestInterface $request
     * @param string $comment
     */
    private function sendReturnMessage($request, $comment, $jsonFiles)
    {
        $message = $this->chatRepository->getEmptyMessageModel();
        $message->setIsRead(0)
            ->setMessage($comment)
            ->setCustomerId(0)
            ->setName($this->adminSession->getName())
            ->setRequestId($request->getRequestId())
            ->setIsManager(true);

        if ($jsonFiles) {
            $files = json_decode($jsonFiles, true);
            $messageFiles = [];
            foreach ($files as $file) {
                $messageFile = $this->chatRepository->getEmptyMessageFileModel();
                $messageFile->setFilepath($file[FileUpload::FILEHASH])
                    ->setFilename($file[FileUpload::FILENAME]);
                $messageFiles[] = $messageFile;
            }
            $message->setMessageFiles($messageFiles);
        }

        try {
            $this->chatRepository->save($message, false);
        } catch (\Exception $e) {
            null;
        }
    }

    /**
     * @param ReturnOrderItemInterface[] $orderItems
     * @param array $items
     *
     * @return RequestItemInterface[]
     */
    public function processItems($orderItems, $items)
    {
        $result = [];

        foreach ($items as $itemGroup) {
            if ($item = $this->processItemGroup($orderItems, $itemGroup)) {
                $result[] = $item;
            }
        }

        return $result;
    }

    /**
     * @param ReturnOrderItemInterface[] $orderItems
     * @param array $itemGroup
     *
     * @return RequestItemInterface|bool
     */
    public function processItemGroup($orderItems, $itemGroup)
    {
        foreach ($itemGroup as $item) {
            if (!empty($item[RequestItemInterface::REQUEST_ITEM_ID])
                && !empty($item[RequestItemInterface::QTY])
                && !empty($item[RequestItemInterface::CONDITION_ID])
                && !empty($item[RequestItemInterface::REASON_ID])
                && !empty($item[RequestItemInterface::RESOLUTION_ID])
                && $orderItem = $this->getOrderItemByOrderItemId($orderItems, (int)$item['order_item_id'])
            ) {
                if ($orderItem->getAvailableQty() > 0.0001
                    && $orderItem->getAvailableQty() >= (double)$item[RequestItemInterface::QTY]
                ) {
                    if (!empty($item[RequestItemInterface::ITEM_STATUS])
                        && $item[RequestItemInterface::ITEM_STATUS] == 'true'
                    ) {
                        $itemStatus = ItemStatus::AUTHORIZED;
                    } else {
                        $itemStatus = 0;
                    }

                    $requestItem = $this->requestRepository->getEmptyRequestItemModel();
                    $requestItem->setItemStatus($itemStatus)
                        ->setOrderItemId($orderItem->getItem()->getItemId())
                        ->setConditionId($item[RequestItemInterface::CONDITION_ID])
                        ->setReasonId($item[RequestItemInterface::REASON_ID])
                        ->setResolutionId($item[RequestItemInterface::RESOLUTION_ID])
                        ->setRequestQty($item[RequestItemInterface::QTY])
                        ->setQty($item[RequestItemInterface::QTY]);

                    return $requestItem;
                }
            }
        }

        return false;
    }

    /**
     * @param ReturnOrderItemInterface[] $orderItems
     * @param int $orderItemId
     *
     * @return ReturnOrderItemInterface|bool
     */
    public function getOrderItemByOrderItemId($orderItems, $orderItemId)
    {
        foreach ($orderItems as $orderItem) {
            if ((int)$orderItem->getItem()->getItemId() === $orderItemId) {
                return $orderItem;
            }
        }

        return false;
    }
}
