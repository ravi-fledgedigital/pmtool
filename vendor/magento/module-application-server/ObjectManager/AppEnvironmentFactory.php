<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ApplicationServer\ObjectManager;

use Magento\Framework\App\Area;
use Magento\Framework\App\EnvironmentFactory;
use Magento\Framework\App\ObjectManager\ConfigLoader\Compiled as ConfigLoaderCompiled;
use Magento\Framework\App\ObjectManager\Environment\Developer as DeveloperEnvironment;

/**
 * Environment Factory for Application Server.
 */
class AppEnvironmentFactory extends EnvironmentFactory
{
    /**
     * @inheritDoc
     */
    public function createEnvironment()
    {
        switch ($this->getMode()) {
            case AppCompiledEnvironment::MODE:
                return new AppCompiledEnvironment($this);
            default:
                return new AppDeveloperEnvironment($this);
        }
    }

    /**
     * Determinate running mode
     *
     * Copied method from superclass because it is private.
     *
     * @return string
     */
    private function getMode()
    {
        // phpcs:disable Magento2.Functions.DiscouragedFunction
        if (file_exists(ConfigLoaderCompiled::getFilePath(Area::AREA_GLOBAL))) {
            return AppCompiledEnvironment::MODE;
        }
        return DeveloperEnvironment::MODE;
    }
}
