<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package RMA Base for Magento 2
 */

namespace Amasty\Rma\Api;

/**
 * Interface ChatRepositoryInterface
 */
interface ChatRepositoryInterface
{
    /**
     * @param int $messageId
     *
     * @return \Amasty\Rma\Api\Data\MessageInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getById($messageId);

    /**
     * @param \Amasty\Rma\Api\Data\MessageInterface $message
     *
     * @return bool true on success
     */
    public function delete(\Amasty\Rma\Api\Data\MessageInterface $message);

    /**
     * @param int $messageId
     *
     * @return bool true on success
     * @throws \Magento\Framework\Exception\CouldNotDeleteException
     */
    public function deleteById($messageId);

    /**
     * @param int $requestId
     * @param int $lastMessageId
     * @param bool $isCustomer
     *
     * @return \Amasty\Rma\Api\Data\MessageInterface[]
     */
    public function getMessagesByRequestId($requestId, $lastMessageId = null, $isCustomer = true);

    /**
     * @param \Amasty\Rma\Api\Data\MessageInterface $message
     * @param bool $notify
     *
     * @return \Amasty\Rma\Api\Data\MessageInterface
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     */
    public function save(\Amasty\Rma\Api\Data\MessageInterface $message, $notify = false);

    /**
     * @return \Amasty\Rma\Api\Data\MessageInterface
     */
    public function getEmptyMessageModel();

    /**
     * @return \Amasty\Rma\Api\Data\MessageFileInterface
     */
    public function getEmptyMessageFileModel();
}
