<?php

/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\ProductRecommendationsAdmin\Model;

/**
 * Reads version of the module.
 */
class ModuleVersionReader
{
    /** @var string|null */
    private static $version;

    /**
     * Return version of the module from compose.json file.
     *
     * @return string|null
     */
    public function getVersion(): ?string
    {
        if (self::$version === null) {
            $class = \Composer\InstalledVersions::class;
            if (class_exists($class)) {
                if (method_exists($class, 'isInstalled')){
                    $metapackage = 'magento/product-recommendations';
                    if ($class::isInstalled($metapackage) && method_exists($class, 'getVersion')) {
                        self::$version = $class::getVersion($metapackage);
                    }
                }
            }
        }
        return self::$version;
    }
}