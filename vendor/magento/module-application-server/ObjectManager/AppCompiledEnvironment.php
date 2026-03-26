<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ApplicationServer\ObjectManager;

use Magento\Framework\App\ObjectManager\Environment\Compiled;

/**
 * Compiled Environment for Application Server.
 */
class AppCompiledEnvironment extends Compiled
{
    /** @var string */
    protected $configPreference = CompiledFactoryPreferencesDecorator::class;
}
