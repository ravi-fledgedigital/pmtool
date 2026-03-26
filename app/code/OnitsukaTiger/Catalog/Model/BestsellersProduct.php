<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace OnitsukaTiger\Catalog\Model;

use Magento\Framework\Model\AbstractModel;

class BestsellersProduct extends AbstractModel
{
    /**
     * @return void
     */
    public function _construct(): void
    {
        $this->_init(\OnitsukaTiger\Catalog\Model\ResourceModel\BestsellersProduct::class);
    }

}
