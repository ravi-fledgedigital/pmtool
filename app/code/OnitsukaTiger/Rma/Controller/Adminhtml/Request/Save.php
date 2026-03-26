<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace OnitsukaTiger\Rma\Controller\Adminhtml\Request;

use Amasty\Rma\Api\Data\RequestItemInterface;
use Amasty\Rma\Api\RequestRepositoryInterface;
use Amasty\Rma\Api\StatusRepositoryInterface;
use Amasty\Rma\Controller\Adminhtml\Request\Save as SaveRma;
use Amasty\Rma\Model\Chat\ResourceModel\CollectionFactory as MessageCollectionFactory;
use Amasty\Rma\Model\ConfigProvider;
use Amasty\Rma\Model\OptionSource\Grid;
use Amasty\Rma\Model\Request\Email\EmailRequest;
use Amasty\Rma\Utils\Email;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use OnitsukaTiger\Rma\Helper\Data;

class Save extends SaveRma
{
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
        Data $helperRma
        
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
        $this->helperRma = $helperRma;
        $this->dataObject = $dataObject;
        $this->repository = $repository;
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
}
