<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package RMA Base for Magento 2
 */

namespace Amasty\Rma\Model\Chat;

use Amasty\Rma\Api\ChatRepositoryInterface;
use Amasty\Rma\Api\Data\MessageFileInterface;
use Amasty\Rma\Api\Data\MessageInterface;
use Amasty\Rma\Api\Data\RequestInterface;
use Amasty\Rma\Api\RequestRepositoryInterface;
use Amasty\Rma\Utils\FileUpload;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;

class Repository implements ChatRepositoryInterface
{
    /**
     * @var \Amasty\Rma\Api\Data\MessageInterfaceFactory
     */
    private $messageFactory;

    /**
     * @var \Amasty\Rma\Api\Data\MessageFileInterfaceFactory
     */
    private $messageFileFactory;

    /**
     * @var ResourceModel\Message
     */
    private $messageResource;

    /**
     * @var ResourceModel\MessageFile
     */
    private $messageFileResource;

    /**
     * @var ResourceModel\CollectionFactory
     */
    private $messageCollectionFactory;

    /**
     * @var ResourceModel\MessageFileCollectionFactory
     */
    private $messageFileCollectionFactory;

    /**
     * @var \Magento\Framework\Event\ManagerInterface
     */
    private $eventManager;

    /**
     * @var RequestRepositoryInterface
     */
    private $requestRepository;

    /**
     * @var FileUpload
     */
    private $fileUpload;

    public function __construct(
        \Amasty\Rma\Api\Data\MessageInterfaceFactory $messageFactory,
        \Amasty\Rma\Api\Data\MessageFileInterfaceFactory $messageFileFactory,
        \Amasty\Rma\Model\Chat\ResourceModel\Message $messageResource,
        \Amasty\Rma\Model\Chat\ResourceModel\MessageFile $messageFileResource,
        \Amasty\Rma\Model\Chat\ResourceModel\CollectionFactory $messageCollectionFactory,
        \Amasty\Rma\Model\Chat\ResourceModel\MessageFileCollectionFactory $messageFileCollectionFactory,
        \Amasty\Rma\Api\RequestRepositoryInterface $requestRepository,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        FileUpload $fileUpload
    ) {
        $this->messageFactory = $messageFactory;
        $this->messageFileFactory = $messageFileFactory;
        $this->messageResource = $messageResource;
        $this->messageFileResource = $messageFileResource;
        $this->messageCollectionFactory = $messageCollectionFactory;
        $this->messageFileCollectionFactory = $messageFileCollectionFactory;
        $this->eventManager = $eventManager;
        $this->requestRepository = $requestRepository;
        $this->fileUpload = $fileUpload;
    }

    /**
     * @inheritdoc
     */
    public function getById($messageId)
    {
        $message = $this->messageFactory->create();
        $this->messageResource->load($message, $messageId);
        if (!$message->getMessageId()) {
            throw new NoSuchEntityException(__('Message with specified ID "%1" not found.', $messageId));
        }
        $this->setMessageFiles($message);

        return $message;
    }

    /**
     * @inheritDoc
     */
    public function delete(\Amasty\Rma\Api\Data\MessageInterface $message)
    {
        try {
            if ($message->getMessageFiles()) {
                foreach ($message->getMessageFiles() as $messageFile) {
                    try {
                        $this->fileUpload->deleteMessageFile($message->getRequestId(), $messageFile->getFilepath());
                    } catch (\Exception $e) {
                        null;
                    }
                    $this->messageFileResource->delete($messageFile);
                }
            }
            $this->messageResource->delete($message);
            $this->eventManager->dispatch(
                $message->isManager()
                    ? \Amasty\Rma\Observer\RmaEventNames::CHAT_MESSAGE_DELETED_BY_MANAGER
                    : \Amasty\Rma\Observer\RmaEventNames::CHAT_MESSAGE_DELETED_BY_CUSTOMER,
                ['request' => $this->requestRepository->getById($message->getRequestId()), 'message' => $message]
            );
        } catch (\Exception $e) {
            if ($message->getMessageId()) {
                throw new CouldNotDeleteException(
                    __(
                        'Unable to remove message with ID %1. Error: %2',
                        [$message->getMessageId(), $e->getMessage()]
                    )
                );
            }
            throw new CouldNotDeleteException(__('Unable to remove message. Error: %1', $e->getMessage()));
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    public function deleteById($messageId)
    {
        $message = $this->getById($messageId);

        return $this->delete($message);
    }

    /**
     * @inheritdoc
     */
    public function getMessagesByRequestId($requestId, $lastMessageId = null, $isCustomer = true)
    {
        $messageCollection = $this->messageCollectionFactory->create();
        $messageCollection->addFieldToFilter(MessageInterface::REQUEST_ID, $requestId)
            ->addOrder(MessageInterface::MESSAGE_ID, \Magento\Framework\Data\Collection::SORT_ORDER_ASC);
        if ($lastMessageId) {
            $messageCollection->addFieldToFilter(MessageInterface::MESSAGE_ID, ['gt' => $lastMessageId]);
        }

        if ($items = $messageCollection->getItems()) {
            $setIsRead = [];
            /** @var \Amasty\Rma\Api\Data\MessageInterface $item */
            foreach ($items as $item) {
                if ($isCustomer && ($item->isSystem() || $item->isManager()) && !$item->isRead()) {
                    $setIsRead[] = $item->getMessageId();
                } elseif (!$isCustomer && !$item->isSystem() && !$item->isManager() && !$item->isRead()) {
                    $setIsRead[] = $item->getMessageId();
                }
                $this->setMessageFiles($item);
            }

            if (!empty($setIsRead)) {
                $this->messageResource->setIsRead($item->getRequestId(), $setIsRead);
            }
        }

        return $items;
    }

    /**
     * @inheritdoc
     */
    public function save(\Amasty\Rma\Api\Data\MessageInterface $message, $notify = true)
    {
        try {
            $this->messageResource->save($message);

            if (!$message->isSystem() && $notify) {
                $this->eventManager->dispatch(
                    $message->isManager()
                        ? \Amasty\Rma\Observer\RmaEventNames::NEW_CHAT_MESSAGE_BY_MANAGER
                        : \Amasty\Rma\Observer\RmaEventNames::NEW_CHAT_MESSAGE_BY_CUSTOMER,
                    ['request' => $this->requestRepository->getById($message->getRequestId()), 'message' => $message]
                );
            }

            if ($messageFiles = $message->getMessageFiles()) {
                $this->fileUpload->saveFiles($messageFiles, $message->getRequestId());

                foreach ($messageFiles as $messageFile) {
                    $messageFile->setMessageId($message->getMessageId());
                    //phpcs:ignore
                    $messageFile->setFilename(pathinfo($messageFile->getFilename(), PATHINFO_BASENAME));
                    $this->messageFileResource->save($messageFile);
                }
            }

        } catch (\Exception $e) {
            throw new CouldNotSaveException(__('Unable to save new reason. Error: %1', $e->getMessage()));
        }

        return $message;
    }

    /**
     * @inheritdoc
     */
    public function getEmptyMessageModel()
    {
        return $this->messageFactory->create();
    }

    /**
     * @inheritdoc
     */
    public function getEmptyMessageFileModel()
    {
        return $this->messageFileFactory->create();
    }

    /**
     * @param MessageInterface $message
     */
    public function setMessageFiles(\Amasty\Rma\Api\Data\MessageInterface $message)
    {
        /** @var \Amasty\Rma\Model\Chat\ResourceModel\MessageFileCollection $collection */
        $collection = $this->messageFileCollectionFactory->create();

        $message->setMessageFiles(
            $collection->addFieldToFilter(
                MessageFileInterface::MESSAGE_ID,
                $message->getMessageId()
            )->getItems()
        );
    }
}
