<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogSyncAdmin\Cron;


/**
 * Class to execute a full catalog re-sync
 * 
 */
interface ForceResyncCatalogInterface
{
    /**
     *  Execute feed data submission
     */
    public function execute();
}
