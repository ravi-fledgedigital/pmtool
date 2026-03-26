<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace OnitsukaTiger\Catalog\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class BestsellersProduct extends AbstractDb
{

    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init('bestsellers_product_list', 'id');
    }
}
