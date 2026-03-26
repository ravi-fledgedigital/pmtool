<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package RMA Base for Magento 2
 */

namespace Amasty\Rma\Observer\Rma;

use Amasty\Rma\Api\Data\RequestInterface;
use Amasty\Rma\Api\Data\StatusInterface;
use Amasty\Rma\Model\OptionSource\State;
use Amasty\Rma\Model\Status\OptionSource\AutoEvents;
use Amasty\Rma\Observer\RmaEventNames;
use Magento\Framework\Event\ObserverInterface;

class AutoStatusChange implements ObserverInterface
{
    /**
     * @var \Amasty\Rma\Api\StatusRepositoryInterface
     */
    private $statusRepository;

    /**
     * @var \Amasty\Rma\Api\RequestRepositoryInterface
     */
    private $requestRepository;

    /**
     * @var \Magento\Framework\Event\ManagerInterface
     */
    private $eventManager;

    public function __construct(
        \Amasty\Rma\Api\StatusRepositoryInterface $statusRepository,
        \Amasty\Rma\Api\RequestRepositoryInterface $requestRepository,
        \Magento\Framework\Event\ManagerInterface $eventManager
    ) {
        $this->statusRepository = $statusRepository;
        $this->requestRepository = $requestRepository;
        $this->eventManager = $eventManager;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $autoEvent = false;
        switch ($observer->getEvent()->getName()) {
            case RmaEventNames::NEW_CHAT_MESSAGE_BY_CUSTOMER:
                $autoEvent = AutoEvents::CUSTOMER_ADDED_COMMENT;
                break;
            case RmaEventNames::TRACKING_NUMBER_ADDED_BY_CUSTOMER:
                $autoEvent = AutoEvents::CUSTOMER_ADDED_TRACKING_NUMBER;
                break;
            case RmaEventNames::RMA_RATED:
                $autoEvent = AutoEvents::CUSTOMER_RATED_RMA;
                break;
            case RmaEventNames::RMA_CANCELED:
                $autoEvent = AutoEvents::CUSTOMER_CANCELED_RMA;
                break;
        }

        /** @var \Amasty\Rma\Api\Data\RequestInterface $request */
        if ($autoEvent && ($request = $observer->getData('request'))) {
            if ($autoEvent == AutoEvents::CUSTOMER_CANCELED_RMA) {
                $statusCollection = $this->statusRepository->getEmptyStatusCollection();
                $statusCollection->addFieldToFilter(StatusInterface::IS_ENABLED, 1)
                    ->addFieldToFilter(StatusInterface::AUTO_EVENT, (int)$autoEvent)
                    ->addFieldToFilter(StatusInterface::STATE, State::CANCELED)
                    ->setPageSize(1)
                    ->setCurPage(1);
            } else {
                $currentStatus = $this->statusRepository->getById($request->getStatus());
                $statusCollection = $this->statusRepository->getEmptyStatusCollection();
                $statusCollection->addFieldToFilter(StatusInterface::IS_ENABLED, 1)
                    ->addFieldToFilter(StatusInterface::AUTO_EVENT, (int)$autoEvent)
                    ->setPageSize(1)
                    ->setCurPage(1);
                $statusCollection->getSelect()->order(
                    new \Zend_Db_Expr(
                        'FIELD(main_table.' . StatusInterface::STATE . ', '
                        . $statusCollection->getConnection()->quote($currentStatus->getState()) . ') DESC, '
                        . 'main_table.' . StatusInterface::STATE . ' ASC'
                    )
                );
            }
            /** @var \Amasty\Rma\Api\Data\StatusInterface $status */
            $status = $statusCollection->fetchItem();
            if ($status && ($status->getStatusId() != $request->getStatus())) {
                $statusBefore = $request->getStatus();
                $request->setStatus($status->getStatusId());
                $this->requestRepository->save($request);
                $this->eventManager->dispatch(
                    RmaEventNames::STATUS_AUTOMATICALLY_CHANGED,
                    [
                        'from' => $statusBefore,
                        'to' => $status->getStatusId(),
                        'request' => $request
                    ]
                );
                $request->setOrigData(RequestInterface::STATUS, $request->getStatus());
            }
        }
    }
}
