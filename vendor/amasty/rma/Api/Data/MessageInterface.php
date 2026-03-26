<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package RMA Base for Magento 2
 */

namespace Amasty\Rma\Api\Data;

/**
 * Interface MessageInterface
 */
interface MessageInterface
{
    /**#@+
     * Constants defined for keys of data array
     */
    public const MESSAGE_ID = 'message_id';
    public const REQUEST_ID = 'request_id';
    public const CREATED_AT = 'created_at';
    public const MESSAGE = 'message';
    public const NAME = 'name';
    public const CUSTOMER_ID = 'customer_id';
    public const MANAGER_ID = 'manager_id';
    public const IS_SYSTEM = 'is_system';
    public const IS_MANAGER = 'is_manager';
    public const IS_READ = 'is_read';
    public const MESSAGE_FILES = 'message_files';
    public const IS_NOTIFIED = 'is_notified';
    /**#@-*/

    /**
     * @param int $messageId
     *
     * @return \Amasty\Rma\Api\Data\MessageInterface
     */
    public function setMessageId($messageId);

    /**
     * @return int
     */
    public function getMessageId();

    /**
     * @param int $requestId
     *
     * @return \Amasty\Rma\Api\Data\MessageInterface
     */
    public function setRequestId($requestId);

    /**
     * @return int
     */
    public function getRequestId();

    /**
     * @param string $created_at
     * @return void
     */
    public function setCreatedAt(string $created_at): void;

    /**
     * @return string
     */
    public function getCreatedAt();

    /**
     * @param string $message
     *
     * @return \Amasty\Rma\Api\Data\MessageInterface
     */
    public function setMessage($message);

    /**
     * @return string
     */
    public function getMessage();

    /**
     * @param string $name
     *
     * @return \Amasty\Rma\Api\Data\MessageInterface
     */
    public function setName($name);

    /**
     * @return string
     */
    public function getName();

    /**
     * @param int $customerId
     *
     * @return \Amasty\Rma\Api\Data\MessageInterface
     */
    public function setCustomerId($customerId);

    /**
     * @return int
     */
    public function getCustomerId();

    /**
     * @param int $managerId
     *
     * @return \Amasty\Rma\Api\Data\MessageInterface
     */
    public function setManagerId($managerId);

    /**
     * @return int
     */
    public function getManagerId();

    /**
     * @param bool $isSystem
     *
     * @return \Amasty\Rma\Api\Data\MessageInterface
     */
    public function setIsSystem($isSystem);

    /**
     * @return bool
     */
    public function isSystem();

    /**
     * @param bool $isManager
     *
     * @return \Amasty\Rma\Api\Data\MessageInterface
     */
    public function setIsManager($isManager);

    /**
     * @return bool
     */
    public function isManager();

    /**
     * @param bool $isRead
     *
     * @return \Amasty\Rma\Api\Data\MessageInterface
     */
    public function setIsRead($isRead);

    /**
     * @return bool
     */
    public function isRead();

    /**
     * @param \Amasty\Rma\Api\Data\MessageFileInterface[] $files
     *
     * @return \Amasty\Rma\Api\Data\MessageInterface
     */
    public function setMessageFiles($files);

    /**
     * @return \Amasty\Rma\Api\Data\MessageFileInterface[]
     */
    public function getMessageFiles();

    /**
     * @param bool $isNotified
     *
     * @return \Amasty\Rma\Api\Data\MessageInterface
     */
    public function setIsNotified(bool $isNotified): MessageInterface;

    /**
     * @return bool
     */
    public function isNotified(): bool;
}
