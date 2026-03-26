<?php
/************************************************************************
 *
 * ADOBE CONFIDENTIAL
 * ___________________
 *
 * Copyright 2023 Adobe
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

namespace Magento\AdobeCommerceWebhooksGenerator\Console;

use Magento\AdobeCommerceWebhooksGenerator\Console\Command\GenerateModuleCommand;
use Magento\Framework\Console\CommandListInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Module\ModuleList;
use Magento\Framework\ObjectManagerInterface;

/**
 * Provides list of commands to be available for uninstalled application
 */
class CommandList implements CommandListInterface
{
    /**
     * @param ObjectManagerInterface $objectManager
     * @param ModuleList $moduleList
     */
    public function __construct(
        private ObjectManagerInterface $objectManager,
        private ModuleList $moduleList
    ) {
    }

    /**
     * Gets list of command classes
     *
     * @return string[]
     */
    private function getCommandsClasses(): array
    {
        return [
            GenerateModuleCommand::class => [
                'Magento_AdobeCommerceWebhooks',
                'Magento_AdobeCommerceWebhooksGenerator',
                'Magento_AdobeCommerceOutOfProcessExtensibility',
            ]
        ];
    }

    /**
     * @inheritDoc
     *
     * @throws LocalizedException
     */
    public function getCommands(): array
    {
        $commands = [];
        foreach ($this->getCommandsClasses() as $class => $requiredModules) {
            if (!$this->checkModulesEnabled($requiredModules)) {
                continue;
            }

            if (class_exists($class)) {
                $commands[] = $this->objectManager->get($class);
            } else {
                throw new LocalizedException(__('Class ' . $class . ' does not exist'));
            }
        }

        return $commands;
    }

    /**
     * Checks if array of required modules are enabled
     *
     * @param array $requiredModules
     * @return bool
     */
    private function checkModulesEnabled(array $requiredModules): bool
    {
        foreach ($requiredModules as $moduleName) {
            if (!$this->moduleList->has($moduleName)) {
                return false;
            }
        }

        return true;
    }
}
