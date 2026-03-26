<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package RMA Base for Magento 2
 */

namespace Amasty\Rma\Model\Chat;

use Amasty\Rma\Api\Data\MessageInterface;
use Magento\Framework\Model\AbstractModel;

class Message extends AbstractModel implements MessageInterface
{
    public function _construct()
    {
        parent::_construct();
        $this->_init(\Amasty\Rma\Model\Chat\ResourceModel\Message::class);
        $this->setIdFieldName(MessageInterface::MESSAGE_ID);
    }

    /**
     * @inheritdoc
     */
    public function setMessageId($messageId)
    {
        return $this->setData(MessageInterface::MESSAGE_ID, (int)$messageId);
    }

    /**
     * @inheritdoc
     */
    public function getMessageId()
    {
        return (int)$this->_getData(MessageInterface::MESSAGE_ID);
    }

    /**
     * @inheritdoc
     */
    public function setRequestId($requestId)
    {
        return $this->setData(MessageInterface::REQUEST_ID, (int)$requestId);
    }

    /**
     * @inheritdoc
     */
    public function getRequestId()
    {
        return (int)$this->_getData(MessageInterface::REQUEST_ID);
    }

    public function setCreatedAt(string $created_at): void
    {
        $this->setData(MessageInterface::CREATED_AT, $created_at);
    }

    /**
     * inheritdoc
     */
    public function getCreatedAt()
    {
        return $this->_getData(MessageInterface::CREATED_AT);
    }

    /**
     * @inheritdoc
     */
    public function setMessage($message)
    {
        return $this->setData(MessageInterface::MESSAGE, $message);
    }

    /**
     * @inheritdoc
     */
    public function getMessage()
    {
        return $this->_getData(MessageInterface::MESSAGE);
    }

    /**
     * @inheritdoc
     */
    public function setName($name)
    {
        return $this->setData(MessageInterface::NAME, $name);
    }

    /**
     * @inheritdoc
     */
    public function getName()
    {
        return $this->_getData(MessageInterface::NAME);
    }

    /**
     * @inheritdoc
     */
    public function setCustomerId($customerId)
    {
        return $this->setData(MessageInterface::CUSTOMER_ID, (int)$customerId);
    }

    /**
     * @inheritdoc
     */
    public function getCustomerId()
    {
        return (int)$this->_getData(MessageInterface::CUSTOMER_ID);
    }

    /**
     * @inheritdoc
     */
    public function setManagerId($managerId)
    {
        return $this->setData(MessageInterface::MANAGER_ID, (int)$managerId);
    }

    /**
     * @inheritdoc
     */
    public function getManagerId()
    {
        return (int)$this->_getData(MessageInterface::MANAGER_ID);
    }

    /**
     * @inheritdoc
     */
    public function setIsSystem($isSystem)
    {
        return $this->setData(MessageInterface::IS_SYSTEM, (bool)$isSystem);
    }

    /**
     * @inheritdoc
     */
    public function isSystem()
    {
        return (bool)$this->_getData(MessageInterface::IS_SYSTEM);
    }

    /**
     * @inheritdoc
     */
    public function setIsManager($isManager)
    {
        return $this->setData(MessageInterface::IS_MANAGER, (bool)$isManager);
    }

    /**
     * @inheritdoc
     */
    public function isManager()
    {
        return (bool)$this->_getData(MessageInterface::IS_MANAGER);
    }

    /**
     * @inheritdoc
     */
    public function setIsRead($isRead)
    {
        return $this->setData(MessageInterface::IS_READ, (bool)$isRead);
    }

    /**
     * @inheritdoc
     */
    public function isRead()
    {
        return (bool)$this->_getData(MessageInterface::IS_READ);
    }

    /**
     * @inheritdoc
     */
    public function setMessageFiles($files)
    {
        return $this->setData(MessageInterface::MESSAGE_FILES, $files);
    }

    /**
     * @inheritdoc
     */
    public function getMessageFiles()
    {
        return $this->_getData(MessageInterface::MESSAGE_FILES);
    }

    public function setIsNotified(bool $isNotified): MessageInterface
    {
        return $this->setData(MessageInterface::IS_NOTIFIED, $isNotified);
    }

    public function isNotified(): bool
    {
        return (bool)$this->_getData(MessageInterface::IS_NOTIFIED);
    }
}
