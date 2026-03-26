<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ApplicationServerStateMonitor\StateMonitor;

/**
 * Interface to get the request name
 */
interface RequestNameInterface
{
    /**
     * Gets request name
     *
     * @return string
     */
    public function getRequestName() : string;
}
