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

namespace Magento\AdobeCommerceWebhooksGenerator\Console\Command\GenerateModule;

use Exception;
use Magento\AdobeCommerceOutOfProcessExtensibility\Model\Generator\Collector\CollectorException;
use Magento\AdobeCommerceOutOfProcessExtensibility\Model\Generator\Collector\ModuleCollector;
use Magento\AdobeCommerceOutOfProcessExtensibility\Model\Generator\Module;
use Magento\AdobeCommerceOutOfProcessExtensibility\Model\Generator\ModuleGenerator;
use Magento\AdobeCommerceWebhooks\Model\WebhookList;
use Magento\AdobeCommerceWebhooks\Model\Validator\WebhookAllowedValidatorInterface;
use Magento\AdobeCommerceWebhooksGenerator\Model\Generator\PluginCollector;
use Magento\AdobeCommerceWebhooksGenerator\Model\Generator\PluginConverter;
use Magento\AdobeCommerceWebhooksGenerator\Model\Generator\WebhooksClassGenerator;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Exception\ValidatorException;

/**
 * Generates Adobe Commerce module with plugins for subscribed webhooks
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Generator
{
    public const OBSERVER_EVENT_INTERFACE = ManagerInterface::class;
    public const OBSERVER_EVENT_METHOD = "dispatch";
    public const OBSERVER_EVENT_INTERFACE_CONFIGURATION = [
        self::OBSERVER_EVENT_INTERFACE => [
            ['name' => self::OBSERVER_EVENT_METHOD]
        ]
    ];
    public const WEBHOOK_PLUGIN = "plugin";
    public const WEBHOOK_OBSERVER = "observer";

    /**
     * @param ModuleGenerator $moduleGenerator
     * @param PluginConverter $pluginConverter
     * @param PluginCollector $pluginCollector
     * @param ModuleCollector $moduleCollector
     * @param WebhookList $webhookList
     * @param WebhookAllowedValidatorInterface $webhookAllowedValidator
     */
    public function __construct(
        private ModuleGenerator $moduleGenerator,
        private PluginConverter $pluginConverter,
        private PluginCollector $pluginCollector,
        private ModuleCollector $moduleCollector,
        private WebhookList $webhookList,
        private WebhookAllowedValidatorInterface $webhookAllowedValidator
    ) {
    }

    /**
     * Runs module generation.
     *
     * @param string $outputDir
     * @throws Exception
     */
    public function run(string $outputDir)
    {
        $webhooks = $this->webhookList->getAll();

        $errors = [];

        $plugins = $observerWebhooks = $visitedEventNames = [];

        foreach ($webhooks as $webhook) {
            try {
                $webhookName = $webhook->getName();
                $this->webhookAllowedValidator->validate($webhookName);
                $webhookType = $webhook->getType();

                $eventName = $webhookName . '_' . $webhookType;
                if (in_array($eventName, $visitedEventNames)) {
                    continue;
                }

                $methodNameParts = explode('.', $webhookName);
                if ($methodNameParts[0] === self::WEBHOOK_PLUGIN) {
                    $plugins[] = $this->pluginCollector->collect($webhookName, $webhookType);
                } elseif ($methodNameParts[0] === self::WEBHOOK_OBSERVER) {
                    $observerWebhooks[] = [
                        'webhookName' => $webhookName,
                        'webhookType' => $webhookType
                    ];
                } else {
                    $errors[] = sprintf(
                        'The specified method name has an invalid prefix: "%s". The prefix must be %s or %s.',
                        $methodNameParts[0],
                        self::WEBHOOK_PLUGIN,
                        self::WEBHOOK_OBSERVER
                    );
                }

                $visitedEventNames[] = $eventName;
            } catch (CollectorException|ValidatorException $e) {
                $errors[] = $e->getMessage();
            }
        }

        if (!empty($errors)) {
            throw new Exception(
                'Can not execute generation for some webhook methods:' . PHP_EOL . implode(PHP_EOL, $errors)
            );
        }

        $module = new Module(WebhooksClassGenerator::MODULE_VENDOR, WebhooksClassGenerator::MODULE_NAME);
        $module->setPlugins($plugins);
        $module->setDependencies($this->moduleCollector->getModules());

        $module->setObserverEventPlugin($this->pluginConverter->convert(self::OBSERVER_EVENT_INTERFACE_CONFIGURATION));
        $module->setObserverEvents($observerWebhooks);

        $this->moduleGenerator->setOutputDir($outputDir);
        $this->moduleGenerator->run($module, null);
    }
}
