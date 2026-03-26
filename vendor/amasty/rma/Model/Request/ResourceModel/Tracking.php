<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package RMA Base for Magento 2
 */

namespace Amasty\Rma\Model\Request\ResourceModel;

use Amasty\Rma\Api\Data\TrackingInterface;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Tracking extends AbstractDb
{
    public const TABLE_NAME = 'amasty_rma_tracking';

    protected function _construct()
    {
        $this->_init(self::TABLE_NAME, TrackingInterface::TRACKING_ID);
    }
}
