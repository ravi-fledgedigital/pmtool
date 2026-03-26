<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ApplicationServerStateMonitor\StateMonitor\AggregateOutput;

/**
 * OutputInterface for AggregateOutput of StateMonitor
 */
interface OutputInterface
{
    /**
     * Does output
     *
     * @param array $errorsByClassName
     * @param array $errorsByRequestName
     * @return string
     */
    public function doOutput(array $errorsByClassName, array $errorsByRequestName) : string;
}
