<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package RMA Base for Magento 2
 */

namespace Amasty\Rma\Model\Chat\ResourceModel;

use Amasty\Rma\Api\Data\MessageInterface;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Message extends AbstractDb
{
    public const TABLE_NAME = 'amasty_rma_message';

    protected function _construct()
    {
        $this->_init(self::TABLE_NAME, MessageInterface::MESSAGE_ID);
    }

    /**
     * @param int $requestId
     * @param array $messages
     */
    public function setIsRead($requestId, $messages)
    {
        if (!empty($requestId) && !empty($messages)) {
            $this->getConnection()->update(
                $this->getMainTable(),
                [MessageInterface::IS_READ => 1],
                [
                    MessageInterface::REQUEST_ID . ' = ?' => (int)$requestId,
                    MessageInterface::MESSAGE_ID . ' IN (?)' => $messages
                ]
            );
        }
    }
}
