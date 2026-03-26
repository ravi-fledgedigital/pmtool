<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package RMA Base for Magento 2
 */

namespace Amasty\Rma\Model\Notifier;

use Amasty\Rma\Api\Data\HistoryInterface;
use Amasty\Rma\Api\Data\MessageInterface;
use Amasty\Rma\Api\Data\RequestInterface;
use Amasty\Rma\Api\Data\NotifierInterface;
use Amasty\Rma\Model\Chat\ResourceModel\CollectionFactory as MessageCollectionFactory;
use Amasty\Rma\Model\ConfigProvider;
use Amasty\Rma\Model\History\ResourceModel\CollectionFactory as HistoryCollectionFactory;
use Amasty\Rma\Model\OptionSource\EventType;
use Amasty\Rma\Model\Request\Email\EmailRequest;
use Amasty\Rma\Utils\Email;
use Magento\Framework\App\Area;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;
use Psr\Log\LoggerInterface;

class CustomerNotifier implements NotifierInterface
{
    /**
     * @var ConfigProvider
     */
    private $configProvider;

    /**
     * @var Email
     */
    private $emailSender;

    /**
     * @var EmailRequest
     */
    private $emailRequest;

    /**
     * @var HistoryCollectionFactory
     */
    private $historyCollectionFactory;

    /**
     * @var MessageCollectionFactory
     */
    private $messageCollectionFactory;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        ConfigProvider $configProvider,
        Email $emailSender,
        EmailRequest $emailRequest,
        HistoryCollectionFactory $historyCollectionFactory,
        MessageCollectionFactory $messageCollectionFactory,
        LoggerInterface $logger
    ) {
        $this->configProvider = $configProvider;
        $this->emailSender = $emailSender;
        $this->emailRequest = $emailRequest;
        $this->historyCollectionFactory = $historyCollectionFactory;
        $this->messageCollectionFactory = $messageCollectionFactory;
        $this->logger = $logger;
    }

    public function notify(RequestInterface $request, MessageInterface $message): void
    {
        if ($this->shouldNotifyCustomer($request, $message)) {
            try {
                $emailRequest = $this->emailRequest->parseRequest($request);
                $storeId = $request->getStoreId();
                $this->emailSender->sendEmail(
                    $emailRequest->getCustomerEmail(),
                    $storeId,
                    $this->configProvider->getEmailTemplateForNewAdminMessage($storeId),
                    ['email_request' => $emailRequest],
                    Area::AREA_FRONTEND,
                    $this->configProvider->getChatSender($storeId)
                );
            } catch (NoSuchEntityException | InputException $exception) {
                $this->logger->critical($exception);
            }
        }
    }

    private function shouldNotifyCustomer(RequestInterface $request, MessageInterface $message): bool
    {
        return $this->configProvider->isNotifyCustomerAboutNewMessage($request->getStoreId())
            && !$this->isFirstMessageAfterStatusChanged($request, $message);
    }

    private function isFirstMessageAfterStatusChanged(RequestInterface $request, MessageInterface $message): bool
    {
        $statusChangeTimestamp = $this->getTimestampForLastStatusChange($request);
        $messageCollection = $this->messageCollectionFactory->create();
        $messageCollection->addFieldToFilter(MessageInterface::REQUEST_ID, $request->getRequestId());
        $messageCollection->addFieldToFilter(MessageInterface::IS_MANAGER, 1);
        $messageCollection->addFieldToFilter(MessageInterface::MESSAGE_ID, ['nin' => [$message->getMessageId()]]);

        if ($statusChangeTimestamp) {
            $messageCollection->addFieldToFilter(MessageInterface::CREATED_AT, ['gteq' => $statusChangeTimestamp]);
        }

        return $messageCollection->count() === 0;
    }

    private function getTimestampForLastStatusChange(RequestInterface $request): ?string
    {
        $historyCollection = $this->historyCollectionFactory->create();
        $historyCollection->addFieldToFilter(HistoryInterface::REQUEST_ID, $request->getRequestId());
        $historyCollection->addFieldToFilter(HistoryInterface::EVENT_TYPE, EventType::MANAGER_SAVED_RMA);
        $historyCollection->unshiftOrder(HistoryInterface::EVENT_DATE);
        $historyForLastStatusChange = null;

        /** @var HistoryInterface $history */
        foreach ($historyCollection as $history) {
            $data = $history->getEventData();

            if (isset($data['after']['status'])) {
                $historyForLastStatusChange = $history;
                break;
            }
        }

        return $historyForLastStatusChange ? $historyForLastStatusChange->getEventDate() : null;
    }
}
