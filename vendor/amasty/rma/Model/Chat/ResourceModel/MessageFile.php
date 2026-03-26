<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package RMA Base for Magento 2
 */

namespace Amasty\Rma\Model\Chat\ResourceModel;

use Amasty\Rma\Api\Data\MessageFileInterface;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class MessageFile extends AbstractDb
{
    public const TABLE_NAME = 'amasty_rma_message_file';

    protected function _construct()
    {
        $this->_init(self::TABLE_NAME, MessageFileInterface::MESSAGE_FILE_ID);
    }
}
