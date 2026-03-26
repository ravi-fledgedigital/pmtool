<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\AsyncOrder\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\Context;
use Magento\Framework\Model\ResourceModel\Db\VersionControl\AbstractDb;
use Magento\Framework\Model\ResourceModel\Db\VersionControl\Snapshot;
use Magento\Framework\Model\ResourceModel\Db\VersionControl\RelationComposite;

/**
 * Quote resource model
 */
class Quote extends AbstractDb
{

    /**
     * @param Context $context
     * @param Snapshot $entitySnapshot
     * @param RelationComposite $entityRelationComposite
     * @param string|null $connectionName
     */
    public function __construct(
        Context $context,
        Snapshot $entitySnapshot,
        RelationComposite $entityRelationComposite,
        $connectionName = null
    ) {
        if ($connectionName === null) {
            $connectionName = 'checkout';
        }
        parent::__construct($context, $entitySnapshot, $entityRelationComposite, $connectionName);
    }

    /**
     * Initialize table nad PK name
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('quote', 'entity_id');
    }
}
