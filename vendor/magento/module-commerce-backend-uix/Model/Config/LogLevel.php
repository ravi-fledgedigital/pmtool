<?php
/**
 * ADOBE CONFIDENTIAL
 *
 * Copyright 2024 Adobe
 * All Rights Reserved.
 *
 * NOTICE: All information contained herein is, and remains
 * the property of Adobe and its suppliers, if any. The intellectual
 * and technical concepts contained herein are proprietary to Adobe
 * and its suppliers and are protected by all applicable intellectual
 * property laws, including trade secret and copyright laws.
 * Dissemination of this information or reproduction of this material
 * is strictly forbidden unless prior written permission is obtained
 * from Adobe.
 */
declare(strict_types=1);

namespace Magento\CommerceBackendUix\Model\Config;

use Magento\Framework\Data\OptionSourceInterface;
use Magento\Framework\Logger\Monolog;

/**
 * Log level options
 */
class LogLevel implements OptionSourceInterface
{
    /**
     * @var array
     */
    private array $usedLevels = [
        Monolog::DEBUG => 'Debug',
        Monolog::INFO => 'Info',
        Monolog::WARNING => 'Warning',
        Monolog::ERROR => 'Error'
    ];

    /**
     * @inheritDoc
     */
    public function toOptionArray()
    {
        $levelOptions = [[
            'value' => '',
            'label' => '-- Select the log level --'
        ]];

        foreach ($this->usedLevels as $level => $label) {
            $levelOptions[] = [
                'value' => $level,
                'label' => $label
            ];
        }

        return $levelOptions;
    }
}
