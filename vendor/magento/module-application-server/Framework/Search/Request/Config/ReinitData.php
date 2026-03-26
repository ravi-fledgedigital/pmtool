<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ApplicationServer\Framework\Search\Request\Config;

use Magento\Framework\Search\Request\Config;

/**
 * Decorator for Config that adds reinitData method
 *
 */
class ReinitData extends Config
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
