<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ApplicationServer\ObjectManager;

use Magento\Framework\App\ObjectManager\Environment\Developer;

/**
 * Developer Environment for Application Server.
 */
class AppDeveloperEnvironment extends Developer
{
    /** @var string */
    protected $configPreference = DynamicFactoryDecorator::class;
}
