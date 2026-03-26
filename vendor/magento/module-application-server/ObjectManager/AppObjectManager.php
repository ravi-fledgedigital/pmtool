<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ApplicationServer\ObjectManager;

use Magento\Framework\App\ObjectManager as ObjectManager;
use Magento\Framework\ObjectManager\ResetAfterRequestInterface;

/**
 * ObjectManager for Application Server.
 */
class AppObjectManager extends ObjectManager implements ResetAfterRequestInterface
{
    public const RUNTIME_PREFERENCES = 'runtime-preferences';

    /**
     * Clear InstanceManager cache and reset other shared instances
     *
     * @return void
     */
    public function _resetState(): void
    {
        $this->_factory->_resetState();
    }

    /**
     * @inheritDoc
     */
    public function configure(array $configuration)
    {
        if (isset($configuration[self::RUNTIME_PREFERENCES])) {
            // compiled config store resolved preferences
            // so if constructor parameter request interface, config will store preference of interface
            // lets add dynamic preferences on for resolved classes so that we can use them in constructor
            foreach ($configuration[self::RUNTIME_PREFERENCES] as $type => $preference) {
                $typePreference = $this->_config->getPreference($type);
                if (!isset($configuration[self::RUNTIME_PREFERENCES][$typePreference])
                    && $preference !== $typePreference // no need add if we have already proper type
                ) {
                    $configuration[self::RUNTIME_PREFERENCES][$typePreference] = $preference;
                }
            }
            $configuration['preferences'] = $configuration[self::RUNTIME_PREFERENCES];
            unset($configuration[self::RUNTIME_PREFERENCES]);
        }
        parent::configure($configuration);
    }
}
