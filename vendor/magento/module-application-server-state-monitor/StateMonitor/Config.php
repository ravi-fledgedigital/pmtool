<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ApplicationServerStateMonitor\StateMonitor;

/**
 * Class to tell if ApplicationServer is in "state monitor" mode.
 */
class Config
{
    /**
     * @var bool
     */
    private static bool $enabled = false;

    /**
     * Checks if "state monitor" mode enabled for Application Server
     *
     * @return bool
     * @phpcs:disable Magento2.Functions.StaticFunction
     */
    public static function isEnabled() : bool
    {
        return static::$enabled;
    }

    /**
     * Enables "monitor state" mode for Application Server
     *
     * @param bool $enabled
     * @return void
     * @phpcs:disable Magento2.Functions.StaticFunction
     */
    public static function setEnabled(bool $enabled) : void
    {
        static::$enabled = $enabled;
    }
}
