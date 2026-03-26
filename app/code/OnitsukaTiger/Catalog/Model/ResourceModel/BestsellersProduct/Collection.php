<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace OnitsukaTiger\Catalog\Model\ResourceModel\BestsellersProduct;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{

    /**
     * @inheritDoc
     */
    protected $_idFieldName = 'id';


    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            \OnitsukaTiger\Catalog\Model\BestsellersProduct::class,
            \OnitsukaTiger\Catalog\Model\ResourceModel\BestsellersProduct::class
        );
    }
}
