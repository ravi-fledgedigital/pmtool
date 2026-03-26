<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ApplicationServer\ObjectManager;

use Magento\Framework\App\Bootstrap;
use Magento\Framework\App\ObjectManagerFactory;

/**
 * AppBootstrap for Application Server.
 */
class AppBootstrap extends Bootstrap
{
    /**
     * Static method so that client code does not have to create Object Manager Factory every time Bootstrap is called
     *
     * Only copied from parent because it wasn't using late static bindings
     *
     * @param string $rootDir
     * @param array $initParams
     * @param ObjectManagerFactory $factory
     * @return Bootstrap
     * phpcs:disable Magento2.Functions.StaticFunction
     */
    public static function create($rootDir, array $initParams, ObjectManagerFactory $factory = null)
    {
        static::populateAutoloader($rootDir, $initParams);
        if ($factory === null) {
            $factory = static::createObjectManagerFactory($rootDir, $initParams);
        }
        return new self($factory, $rootDir, $initParams);
    }
    //phpcs:enable Magento2.Functions.StaticFunction

    /**
     * Creates instance of object manager factory
     *
     * Using AppObjectManagerFactory to create AppObjectManager
     *
     * @param string $rootDir
     * @param array $initParams
     * @return ObjectManagerFactory
     * phpcs:disable Magento2.Functions.StaticFunction
     */
    public static function createObjectManagerFactory($rootDir, array $initParams)
    {
        $dirList = static::createFilesystemDirectoryList($rootDir, $initParams);
        $driverPool = static::createFilesystemDriverPool($initParams);
        $configFilePool = static::createConfigFilePool();
        return new AppObjectManagerFactory($dirList, $driverPool, $configFilePool);
    }
    //phpcs:enable Magento2.Functions.StaticFunction
}
