<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package RMA Base for Magento 2
 */

namespace Amasty\Rma\Model\Reason\ResourceModel;

use Amasty\Rma\Api\Data\ReasonInterface;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Reason extends AbstractDb
{
    public const TABLE_NAME = 'amasty_rma_reason';

    protected function _construct()
    {
        $this->_init(self::TABLE_NAME, ReasonInterface::REASON_ID);
    }
}
