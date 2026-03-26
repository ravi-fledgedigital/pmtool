<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ApplicationServer\ObjectManager;

use Magento\Framework\App\ObjectManagerFactory;

/**
 * ObjectManagerFactory for Application Server.
 */
class AppObjectManagerFactory extends ObjectManagerFactory
{
    /** @var string */
    protected $envFactoryClassName = AppEnvironmentFactory::class;

    /**
     * @var string
     */
    private static string $locatorClassNameOverride = AppObjectManager::class;

    /**
     * phpcs:disable Magento2.Annotation.MethodArguments
     * @param mixed ...$args
     */
    public function __construct(mixed ...$args)
    {
        $this->_locatorClassName = static::$locatorClassNameOverride;
        parent::__construct(...$args);
    }

    /**
     * Override _locatorClassName to use custom ObjectManager class
     *
     * @param string $locatorClassName
     * @return void
     * @phpcs:disable Magento2.Functions.StaticFunction
     */
    public static function setLocatorClassNameOverride(string $locatorClassName) : void
    {
        static::$locatorClassNameOverride = $locatorClassName;
    }
}
