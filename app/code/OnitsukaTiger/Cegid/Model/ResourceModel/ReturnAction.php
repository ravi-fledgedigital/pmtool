<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace OnitsukaTiger\Cegid\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class ReturnAction extends AbstractDb
{


    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init('onitsukatiger_cegid_returnaction', 'returnaction_id');
    }
}
