<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package RMA Base for Magento 2
 */

namespace Amasty\Rma\Model\History;

use Amasty\Rma\Api\HistoryRepositoryInterface;
use Amasty\Rma\Model\OptionSource\EventInitiator;
use Magento\Backend\Model\Auth\Session;
use Magento\Framework\Exception\CouldNotSaveException;

class CreateEvent
{
    /**
     * @var HistoryRepositoryInterface
     */
    private $historyRepository;

    /**
     * @var Session
     */
    private $authSession;

    public function __construct(
        HistoryRepositoryInterface $historyRepository,
        Session $authSession
    ) {
        $this->historyRepository = $historyRepository;
        $this->authSession = $authSession;
    }

    public function execute(
        $eventType,
        \Amasty\Rma\Api\Data\RequestInterface $request,
        $initiator,
        $additionalData = []
    ) {
        $event = $this->historyRepository->getEmptyEventModel()
            ->setRequestId($request->getRequestId())
            ->setEventType($eventType)
            ->setEventInitiator($initiator)
            ->setEventData($additionalData);

        switch ($initiator) {
            case EventInitiator::MANAGER:
                $userName = __('CLI');
                $user = $this->authSession->getUser();

                if ($user !== null) {
                    $userName = $user->getName();
                }

                $event->setEventInitiatorName($userName);
                break;
            case EventInitiator::CUSTOMER:
                $event->setEventInitiatorName($request->getCustomerName());
                break;
        }

        try {
            $this->historyRepository->create($event);
        } catch (CouldNotSaveException $exception) {
            return false;
        }

        return true;
    }
}
