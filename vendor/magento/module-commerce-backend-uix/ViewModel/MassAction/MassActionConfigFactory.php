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

namespace Magento\CommerceBackendUix\ViewModel\MassAction;

/**
 * Mass actions factory for config
 */
class MassActionConfigFactory
{
    /**
     * @param array $config
     */
    public function __construct(private array $config = [])
    {
    }

    /**
     * Create mass action
     *
     * @param string $type
     * @return MassActionConfigInterface
     */
    public function create(string $type): MassActionConfigInterface
    {
        if (!isset($this->config[$type])) {
            throw new \InvalidArgumentException("Invalid type: $type");
        }
        return $this->config[$type];
    }
}
