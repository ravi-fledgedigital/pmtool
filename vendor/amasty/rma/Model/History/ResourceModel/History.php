<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package RMA Base for Magento 2
 */

namespace Amasty\Rma\Model\History\ResourceModel;

use Amasty\Rma\Api\Data\HistoryInterface;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class History extends AbstractDb
{
    public const TABLE_NAME = 'amasty_rma_history';

    protected function _construct()
    {
        $this->_init(self::TABLE_NAME, HistoryInterface::EVENT_ID);
    }
}
