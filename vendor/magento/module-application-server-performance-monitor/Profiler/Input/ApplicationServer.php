<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ApplicationServerPerformanceMonitor\Profiler\Input;

use Magento\ApplicationPerformanceMonitor\Profiler\InputInterface;
use Magento\ApplicationServer\App\Application;
use Magento\Framework\AppInterface;

/**
 * Profiler input specific for ApplicationServer
 */
class ApplicationServer implements InputInterface
{
    /**
     * @inheritDoc
     */
    public function doInput(AppInterface $application) : array
    {
        if ($application instanceof Application) {
            return ['applicationServer' => 1];
        }
        return [];
    }
}
