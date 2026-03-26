<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ApplicationServer\Framework\Config\Data;

use Magento\Framework\Config\Data;

/**
 * Decorator for Data that adds reinitData method
 *
 */
class ReinitData extends Data
{
    /**
     * Reinitialise data for configuration
     *
     * @return void
     */
    public function reinitData()
    {
        $this->_data = [];
        $this->initData();
    }
}
