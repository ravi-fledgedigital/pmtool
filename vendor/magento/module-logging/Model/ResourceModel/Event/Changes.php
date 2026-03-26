<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Logging\Model\ResourceModel\Event;

use Magento\Framework\ObjectManager\ResetAfterRequestInterface;

/**
 * Logging event changes model
 */
class Changes extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb implements ResetAfterRequestInterface
{
    /**
     * Initialize resource
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('magento_logging_event_changes', 'id');
    }

    /**
     * @inheritDoc
     */
    public function _resetState(): void
    {
        $this->_uniqueFields = null;
    }
}
