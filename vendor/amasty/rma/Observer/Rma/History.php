<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package RMA Base for Magento 2
 */

namespace Amasty\Rma\Observer\Rma;

use Amasty\Rma\Api\ConditionRepositoryInterface;
use Amasty\Rma\Api\Data\RequestInterface;
use Amasty\Rma\Api\Data\RequestItemInterface;
use Amasty\Rma\Api\ReasonRepositoryInterface;
use Amasty\Rma\Api\ResolutionRepositoryInterface;
use Amasty\Rma\Api\StatusRepositoryInterface;
use Amasty\Rma\Model\ConfigProvider;
use Amasty\Rma\Model\History\CreateEvent;
use Amasty\Rma\Model\OptionSource\EventInitiator;
use Amasty\Rma\Model\OptionSource\EventType;
use Amasty\Rma\Model\OptionSource\ItemStatus;
use Amasty\Rma\Model\OptionSource\Manager;
use Amasty\Rma\Observer\RmaEventNames;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\OrderItemRepositoryInterface;

class History implements ObserverInterface
{
    /**
     * @var CreateEvent
     */
    private $createEvent;

    /**
     * @var ConfigProvider
     */
    private $configProvider;

    /**
     * @var StatusRepositoryInterface
     */
    private $statusRepository;

    /**
     * @var Manager
     */
    private $managers;

    /**
     * @var ResolutionRepositoryInterface
     */
    private $resolutionRepository;

    /**
     * @var ReasonRepositoryInterface
     */
    private $reasonRepository;

    /**
     * @var ConditionRepositoryInterface
     */
    private $conditionRepository;

    /**
     * @var OrderItemRepositoryInterface
     */
    private $orderItemRepository;

    /**
     * @var ItemStatus
     */
    private $itemStatus;

    public function __construct(
        CreateEvent $createEvent,
        ConfigProvider $configProvider,
        StatusRepositoryInterface $statusRepository,
        ResolutionRepositoryInterface $resolutionRepository,
        ReasonRepositoryInterface $reasonRepository,
        ConditionRepositoryInterface $conditionRepository,
        OrderItemRepositoryInterface $orderItemRepository,
        ItemStatus $itemStatus,
        Manager $managers
    ) {
        $this->createEvent = $createEvent;
        $this->configProvider = $configProvider;
        $this->statusRepository = $statusRepository;
        $this->managers = $managers;
        $this->resolutionRepository = $resolutionRepository;
        $this->reasonRepository = $reasonRepository;
        $this->conditionRepository = $conditionRepository;
        $this->orderItemRepository = $orderItemRepository;
        $this->itemStatus = $itemStatus;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var \Amasty\Rma\Api\Data\RequestInterface $request */
        $request = $observer->getData('request');
        switch ($observer->getEvent()->getName()) {
            case RmaEventNames::RMA_CREATED_BY_CUSTOMER:
                $this->createEvent->execute(EventType::RMA_CREATED, $request, EventInitiator::CUSTOMER);
                break;
            case RmaEventNames::RMA_CREATED_BY_MANAGER:
                $this->createEvent->execute(EventType::RMA_CREATED, $request, EventInitiator::MANAGER);
                break;
            case RmaEventNames::TRACKING_NUMBER_ADDED_BY_CUSTOMER:
                $tracking = $observer->getData('tracking');
                $this->createEvent->execute(
                    EventType::TRACKING_NUMBER_ADDED,
                    $request,
                    EventInitiator::CUSTOMER,
                    [
                        $this->getCarrier($tracking->getTrackingCode()),
                        $tracking->getTrackingNumber()
                    ]
                );
                break;
            case RmaEventNames::TRACKING_NUMBER_ADDED_BY_MANAGER:
                $tracking = $observer->getData('tracking');
                $this->createEvent->execute(
                    EventType::TRACKING_NUMBER_ADDED,
                    $request,
                    EventInitiator::MANAGER,
                    [
                        $this->getCarrier($tracking->getTrackingCode()),
                        $tracking->getTrackingNumber()
                    ]
                );
                break;
            case RmaEventNames::TRACKING_NUMBER_DELETED_BY_CUSTOMER:
                $tracking = $observer->getData('tracking');
                $this->createEvent->execute(
                    EventType::TRACKING_NUMBER_DELETED,
                    $request,
                    EventInitiator::CUSTOMER,
                    [
                        $this->getCarrier($tracking->getTrackingCode()),
                        $tracking->getTrackingNumber()
                    ]
                );
                break;
            case RmaEventNames::TRACKING_NUMBER_DELETED_BY_MANAGER:
                $tracking = $observer->getData('tracking');
                $this->createEvent->execute(
                    EventType::TRACKING_NUMBER_DELETED,
                    $request,
                    EventInitiator::MANAGER,
                    [
                        $this->getCarrier($tracking->getTrackingCode()),
                        $tracking->getTrackingNumber()
                    ]
                );
                break;
            case RmaEventNames::RMA_CANCELED:
                $this->createEvent->execute(
                    EventType::CUSTOMER_CLOSED_RMA,
                    $request,
                    EventInitiator::CUSTOMER
                );
                break;
            case RmaEventNames::SHIPPING_LABEL_ADDED_BY_MANAGER:
                $this->createEvent->execute(
                    EventType::MANAGER_ADDED_SHIPPING_LABEL,
                    $request,
                    EventInitiator::MANAGER
                );
                break;
            case RmaEventNames::SHIPPING_LABEL_DELETED_BY_MANAGER:
                $this->createEvent->execute(
                    EventType::MANAGER_DELETED_SHIPPING_LABEL,
                    $request,
                    EventInitiator::MANAGER
                );
                break;
            case RmaEventNames::NEW_CHAT_MESSAGE_BY_CUSTOMER:
                $this->createEvent->execute(
                    EventType::NEW_MESSAGE,
                    $request,
                    EventInitiator::CUSTOMER
                );
                break;
            case RmaEventNames::NEW_CHAT_MESSAGE_BY_MANAGER:
                $this->createEvent->execute(
                    EventType::NEW_MESSAGE,
                    $request,
                    EventInitiator::MANAGER
                );
                break;
            case RmaEventNames::CHAT_MESSAGE_DELETED_BY_CUSTOMER:
                $this->createEvent->execute(
                    EventType::DELETED_MESSAGE,
                    $request,
                    EventInitiator::CUSTOMER,
                    [
                        $observer->getData('message')->getMessage()
                    ]
                );
                break;
            case RmaEventNames::CHAT_MESSAGE_DELETED_BY_MANAGER:
                $this->createEvent->execute(
                    EventType::DELETED_MESSAGE,
                    $request,
                    EventInitiator::MANAGER,
                    [
                        $observer->getData('message')->getMessage()
                    ]
                );
                break;
            case RmaEventNames::STATUS_AUTOMATICALLY_CHANGED:
                $this->createEvent->execute(
                    EventType::STATUS_AUTOMATICALLY_CHANGED,
                    $request,
                    EventInitiator::SYSTEM,
                    [
                        $this->getStatusTitle($observer->getData('from')),
                        $this->getStatusTitle($observer->getData('to')),
                    ]
                );
                break;
            case RmaEventNames::RMA_SAVED_BY_MANAGER:
                $before = $after = [];
                $itemStatuses = $this->itemStatus->toArray();
                if ($request->getStatus() != $request->getOrigData(RequestInterface::STATUS)) {
                    $before['status'] = $this->getStatusTitle(
                        $request->getOrigData(RequestInterface::STATUS)
                    );
                    $after['status'] = $this->getStatusTitle($request->getStatus());
                }

                if ($request->getManagerId() != $request->getOrigData(RequestInterface::MANAGER_ID)) {
                    $managers = $this->managers->toArray();
                    $defaultManager = $managers[0] ?? __('Unassigned')->getText();
                    $before['manager'] = $managers[$request->getOrigData(RequestInterface::MANAGER_ID)]
                        ?? $defaultManager;
                    $after['manager'] = $managers[$request->getManagerId()] ?? $defaultManager;
                }

                if ($request->getNote() != $request->getOrigData(RequestInterface::NOTE)) {
                    $before['note'] = $request->getOrigData(RequestInterface::NOTE) ?: '';
                    $after['note'] = $request->getNote();
                }

                $splitedItems = $items = [];
                foreach ($request->getRequestItems() as $item) {
                    if ($item->getOrigData(RequestItemInterface::REQUEST_ITEM_ID)) {
                        $changes = [];

                        if ($item->getItemStatus() != $item->getOrigData(RequestItemInterface::ITEM_STATUS)) {
                            $changes['before']['state'] = !empty(
                                $itemStatuses[$item->getOrigData(RequestItemInterface::ITEM_STATUS)]
                            ) ? $itemStatuses[$item->getOrigData(RequestItemInterface::ITEM_STATUS)] : '';
                            $changes['after']['state'] = !empty($itemStatuses[$item->getItemStatus()])
                                ? $itemStatuses[$item->getItemStatus()]
                                : '';
                        }

                        if ((double)$item->getQty() != (double)$item->getOrigData(RequestItemInterface::QTY)) {
                            $changes['before']['qty'] = (double)$item->getOrigData(RequestItemInterface::QTY);
                            $changes['after']['qty'] = (double)$item->getQty();
                        }

                        if ($item->getConditionId() != $item->getOrigData(RequestItemInterface::CONDITION_ID)) {
                            $changes['before']['condition'] = $this->conditionRepository->getById(
                                $item->getOrigData(RequestItemInterface::CONDITION_ID)
                            )->getTitle();
                            $changes['after']['condition'] = $this->conditionRepository->getById(
                                $item->getConditionId()
                            )->getTitle();
                        }

                        if ($item->getReasonId() != $item->getOrigData(RequestItemInterface::REASON_ID)) {
                            $changes['before']['reason'] = $this->reasonRepository->getById(
                                $item->getOrigData(RequestItemInterface::REASON_ID)
                            )->getTitle();
                            $changes['after']['reason'] = $this->reasonRepository->getById(
                                $item->getReasonId()
                            )->getTitle();
                        }

                        if ($item->getResolutionId() != $item->getOrigData(RequestItemInterface::RESOLUTION_ID)) {
                            $changes['before']['resolution'] = $this->resolutionRepository->getById(
                                $item->getOrigData(RequestItemInterface::RESOLUTION_ID)
                            )->getTitle();
                            $changes['after']['resolution'] = $this->resolutionRepository->getById(
                                $item->getResolutionId()
                            )->getTitle();
                        }

                        if (!empty($changes)) {
                            $orderItem = $this->orderItemRepository->get($item->getOrderItemId());
                            $changes['name'] = $orderItem->getName();
                            $changes['sku'] = $orderItem->getSku();
                            $items[] = $changes;
                        }
                    } else {
                        $orderItem = $this->orderItemRepository->get($item->getOrderItemId());
                        $splitedItems[] = [
                            'name' => $orderItem->getName(),
                            'sku' => $orderItem->getSku(),
                            'state' => !empty($itemStatuses[$item->getItemStatus()])
                                ? $itemStatuses[$item->getItemStatus()]
                                : '',
                            'qty' => (double)$item->getQty(),
                            'condition' => $this->conditionRepository->getById(
                                $item->getConditionId()
                            )->getTitle(),
                            'reason' => $this->reasonRepository->getById(
                                $item->getReasonId()
                            )->getTitle(),
                            'resolution' => $this->resolutionRepository->getById(
                                $item->getResolutionId()
                            )->getTitle()
                        ];
                    }
                }

                if (!empty($before) || !empty($after) || !empty($items) || !empty($splitedItems)) {
                    $this->createEvent->execute(
                        EventType::MANAGER_SAVED_RMA,
                        $request,
                        EventInitiator::MANAGER,
                        ['before' => $before, 'after' => $after, 'items' => $items, 'splited' => $splitedItems]
                    );
                }
                break;
            case RmaEventNames::STATUS_CHANGED_BY_SYSTEM:
                $this->createEvent->execute(
                    EventType::SYSTEM_CHANGED_STATUS,
                    $request,
                    EventInitiator::SYSTEM,
                    [
                        $this->getStatusTitle($observer->getData('from')),
                        $this->getStatusTitle($observer->getData('to'))
                    ]
                );
                break;
            case RmaEventNames::MANAGER_CHANGED_BY_SYSTEM:
                $this->createEvent->execute(
                    EventType::SYSTEM_CHANGED_MANAGER,
                    $request,
                    EventInitiator::SYSTEM,
                    [
                        $observer->getData('from'),
                        $observer->getData('to')
                    ]
                );
                break;
        }
    }

    public function getCarrier($code)
    {
        $carriers = $this->configProvider->getCarriers(null, true);

        return isset($carriers[$code]) ? $carriers[$code] : '';
    }

    public function getStatusTitle($statusId)
    {
        try {
            $title = $this->statusRepository->getById($statusId)->getTitle();
        } catch (LocalizedException $e) {
            $title = __('Unknown');
        }

        return $title;
    }
}
