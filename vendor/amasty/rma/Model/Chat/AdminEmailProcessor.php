<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package RMA Base for Magento 2
 */

namespace Amasty\Rma\Model\Chat;

use Amasty\Rma\Api\ChatRepositoryInterface;
use Amasty\Rma\Api\Data\MessageInterface;
use Amasty\Rma\Api\Data\RequestInterface;
use Amasty\Rma\Model\Chat\ResourceModel\CollectionFactory as MessageCollectionFactory;
use Amasty\Rma\Model\Chat\ResourceModel\Message;
use Amasty\Rma\Model\ConfigProvider;
use Amasty\Rma\Model\Request\Email\EmailRequest;
use Amasty\Rma\Utils\Email;
use Magento\Framework\App\Area;
use Magento\Framework\App\ResourceConnection;

class AdminEmailProcessor
{
    /**
     * @var ChatRepositoryInterface
     */
    private $chatRepository;

    /**
     * @var ConfigProvider
     */
    private $configProvider;

    /**
     * @var EmailRequest
     */
    private $emailRequest;

    /**
     * @var Email
     */
    private $email;

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    public function __construct(
        ChatRepositoryInterface $chatRepository,
        ConfigProvider $configProvider,
        MessageCollectionFactory $messageCollectionFactory,
        EmailRequest $emailRequest,
        Email $email,
        ResourceConnection $resourceConnection
    ) {
        $this->chatRepository = $chatRepository;
        $this->configProvider = $configProvider;
        $this->emailRequest = $emailRequest;
        $this->email = $email;
        $this->resourceConnection = $resourceConnection;
    }
    public function process(RequestInterface $request, MessageInterface $message): void
    {
        $storeId = $request->getStoreId();
        if (!$message->isNotified()
            && $this->configProvider->isNotifyAdmin($storeId)
            && $this->configProvider->isNotifyAdminAboutNewMessage($storeId)
        ) {
            if ($this->isNeedToNotify($message, $storeId)) {
                $emailRequest = $this->emailRequest->parseRequest($request);

                $this->email->sendEmail(
                    $this->configProvider->getAdminEmails($storeId),
                    $storeId,
                    $this->configProvider->getNewMessageAdminTemplate($storeId),
                    ['email_request' => $emailRequest],
                    Area::AREA_ADMINHTML
                );

                $message->setIsNotified(true);
                $this->chatRepository->save($message);
            }
        }
    }

    private function isNeedToNotify(MessageInterface $message, int $storeId): bool
    {
        $connection = $this->resourceConnection->getConnection();

        $select = $connection->select()
            ->from($this->resourceConnection->getTableName(Message::TABLE_NAME))
            ->where(MessageInterface::REQUEST_ID . ' = ?', $message->getRequestId())
            ->where(MessageInterface::MESSAGE_ID . ' < ?', $message->getMessageId())
            ->where(
                MessageInterface::CREATED_AT . ' >= NOW() - INTERVAL ? MINUTE',
                $this->configProvider->getNotifyAdminGapTime($storeId)
            )
            ->where(
                new \Zend_Db_Expr(
                    MessageInterface::IS_NOTIFIED . ' = 1 OR '
                    . MessageInterface::IS_MANAGER . ' = 1 OR ' . MessageInterface::IS_READ . ' = 1'
                )
            );

        return count($connection->fetchCol($select)) === 0;
    }
}
