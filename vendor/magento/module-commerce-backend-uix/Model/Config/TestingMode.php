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

/**
 * Testing mode options
 */
class TestingMode implements OptionSourceInterface
{
    public const LOCAL_TESTING = 'Local testing';
    public const SANDBOX = 'Sandbox';

    /**
     * @inheritDoc
     */
    public function toOptionArray()
    {
        return [
            ['value' => self::LOCAL_TESTING, 'label' => __('Local testing')],
            ['value' => self::SANDBOX, 'label' => __('Sandbox')]
        ];
    }
}
