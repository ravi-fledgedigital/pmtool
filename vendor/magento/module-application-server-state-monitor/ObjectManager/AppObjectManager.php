<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ApplicationServerStateMonitor\ObjectManager;

use Magento\ApplicationServer\ObjectManager\AppObjectManager as ApplicationServerAppObjectManager;
use Magento\Framework\ObjectManager\Resetter\ResetterInterface;
use Magento\Framework\TestFramework\ApplicationStateComparator\ObjectManagerInterface;

/**
 * ObjectManager for ApplicationServer StateMonitor.
 */
class AppObjectManager extends ApplicationServerAppObjectManager implements ObjectManagerInterface
{
    /**
     * @inheritDoc
     */
    public function getResetter(): ResetterInterface
    {
        return $this->_factory->getResetter();
    }

    /**
     * Returns shared instances
     *
     * @return object[]
     */
    public function getSharedInstances() : array
    {
        return $this->_sharedInstances;
    }
}
