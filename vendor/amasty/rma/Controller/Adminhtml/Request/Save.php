<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package RMA Base for Magento 2
 */

namespace Amasty\Rma\Controller\Adminhtml\Request;

use Amasty\Rma\Api\Data\MessageInterface;
use Amasty\Rma\Api\Data\RequestInterface;
use Amasty\Rma\Api\Data\RequestItemInterface;
use Amasty\Rma\Api\RequestRepositoryInterface;
use Amasty\Rma\Controller\Adminhtml\RegistryConstants;
use Amasty\Rma\Model\Chat\ResourceModel\CollectionFactory as MessageCollectionFactory;
use Amasty\Rma\Api\StatusRepositoryInterface;
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

class Save extends Action
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    public const ADMIN_RESOURCE = 'Amasty_Rma::request_save';

    /**
     * @var DataPersistorInterface
     */
    private $dataPersistor;

    /**
     * @var RequestRepositoryInterface
     */
    private $repository;

    /**
     * @var StatusRepositoryInterface
     */
    private $statusRepository;

    /**
     * @var Grid
     */
    private $grid;

    /**
     * @var MessageCollectionFactory
     */
    private $messageCollectionFactory;

    /**
     * @var Email
     */
    private $email;

    /**
     * @var EmailRequest
     */
    private $emailRequest;

    /**
     * @var ConfigProvider
     */
    private $configProvider;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var DataObject
     */
    private $dataObject;

    /**
     * @var \Magento\Framework\Event\ManagerInterface
     */
    private $eventManager;

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
        Grid $grid
    ) {
        parent::__construct($context);
        $this->dataPersistor = $dataPersistor;
        $this->repository = $repository;
        $this->statusRepository = $statusRepository;
        $this->grid = $grid;
        $this->messageCollectionFactory = $messageCollectionFactory;
        $this->email = $email;
        $this->emailRequest = $emailRequest;
        $this->configProvider = $configProvider;
        $this->scopeConfig = $scopeConfig;
        $this->dataObject = $dataObject;
        $this->eventManager = $context->getEventManager() ?: \Magento\Framework\App\ObjectManager::getInstance()
            ->get(\Magento\Framework\Event\ManagerInterface::class);
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
                            [
                                'email_request' => $emailRequest,
                                'order' => $emailRequest->getOrder()
                            ],
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

    public function processItems(\Amasty\Rma\Api\Data\RequestInterface $model, $items)
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

                if ($newQty != $requestQty) {
                    throw new LocalizedException(__('Wrong Request Qty'));
                }
            } elseif (!empty($currentRequestItem[0])) {
                $resultItems[] = $currentRequestItem[0];
            }
        }

        $model->setRequestItems($resultItems);
    }
}
