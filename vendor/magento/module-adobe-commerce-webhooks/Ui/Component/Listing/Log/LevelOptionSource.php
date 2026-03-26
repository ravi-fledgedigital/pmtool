<?php
/************************************************************************
 *
 * ADOBE CONFIDENTIAL
 * ___________________
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
 * ************************************************************************
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceWebhooks\Ui\Component\Listing\Log;

use Magento\Framework\Data\OptionSourceInterface;
use Magento\Framework\Logger\Monolog;

/**
 * Returns options for the level field
 */
class LevelOptionSource implements OptionSourceInterface
{
    /**
     * @var array
     */
    private array $usedLevels = [
        Monolog::DEBUG => 'debug',
        Monolog::INFO => 'info',
        Monolog::WARNING => 'warning',
        Monolog::ERROR => 'error',
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
                'label' => ucfirst($label),
            ];
        }

        return $levelOptions;
    }
}
