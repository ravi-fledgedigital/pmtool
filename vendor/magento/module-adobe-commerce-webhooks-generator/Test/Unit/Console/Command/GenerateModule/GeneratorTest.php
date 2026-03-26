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

namespace Magento\AdobeCommerceWebhooksGenerator\Test\Unit\Console\Command\GenerateModule;

use Exception;
use Magento\AdobeCommerceOutOfProcessExtensibility\Model\Generator\Collector\ModuleCollector;
use Magento\AdobeCommerceOutOfProcessExtensibility\Model\Generator\ModuleGenerator;
use Magento\AdobeCommerceWebhooks\Model\Validator\WebhookAllowedValidatorInterface;
use Magento\AdobeCommerceWebhooks\Model\Webhook;
use Magento\AdobeCommerceWebhooks\Model\WebhookList;
use Magento\AdobeCommerceWebhooksGenerator\Console\Command\GenerateModule\Generator;
use Magento\AdobeCommerceWebhooksGenerator\Model\Generator\PluginCollector;
use Magento\AdobeCommerceWebhooksGenerator\Model\Generator\PluginConverter;
use Magento\Framework\Exception\ValidatorException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Tests for @see Generator class.
 */
class GeneratorTest extends TestCase
{
    /**
     * @var ModuleGenerator|MockObject
     */
    private ModuleGenerator|MockObject $moduleGeneratorMock;

    /**
     * @var PluginConverter|MockObject
     */
    private PluginConverter|MockObject $pluginConverterMock;

    /**
     * @var PluginCollector|MockObject
     */
    private PluginCollector|MockObject $pluginCollectorMock;

    /**
     * @var ModuleCollector|MockObject
     */
    private ModuleCollector|MockObject $moduleCollectorMock;

    /**
     * @var WebhookList|MockObject
     */
    private WebhookList|MockObject $webhookListMock;

    /**
     * @var WebhookAllowedValidatorInterface|MockObject
     */
    private WebhookAllowedValidatorInterface|MockObject $webhookAllowedValidator;

    /**
     * @var Generator
     */
    private Generator $generator;

    protected function setUp(): void
    {
        $this->moduleGeneratorMock = $this->createMock(ModuleGenerator::class);
        $this->pluginConverterMock = $this->createMock(PluginConverter::class);
        $this->pluginCollectorMock = $this->createMock(PluginCollector::class);
        $this->moduleCollectorMock = $this->createMock(ModuleCollector::class);
        $this->webhookListMock = $this->createMock(WebhookList::class);
        $this->webhookAllowedValidator = $this->createMock(WebhookAllowedValidatorInterface::class);
        $this->generator = new Generator(
            $this->moduleGeneratorMock,
            $this->pluginConverterMock,
            $this->pluginCollectorMock,
            $this->moduleCollectorMock,
            $this->webhookListMock,
            $this->webhookAllowedValidator
        );
    }

    /**
     * Checks that webhook and plugin information is correctly processed before the generated plugin module is created.
     *
     * @return void
     * @throws Exception
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testRun(): void
    {
        $resourceModelWebhookName = 'plugin.resource_model_event';
        $resourceModelWebhookType = 'after';
        $resourceModelPlugin = ['name' => 'ResourceModelClassPlugin'];

        $apiWebhookName = 'plugin.api_event';
        $apiWebhookType = 'before';
        $apiInterfacePlugin = ['name' => 'ApiInterfacePlugin'];

        $observerWebhookName = 'observer.event';
        $observerWebhookType = 'before';
        $observerEventPlugin = ['name' => 'ManagerInterfacePlugin'];
        $observerWebhooks = [
            [
                'webhookName' => $observerWebhookName,
                'webhookType' => $observerWebhookType
            ]
        ];

        $plugins = [$apiInterfacePlugin, $resourceModelPlugin];
        $dependencies = [
            'magento/module-test' => [
                'packageName' => 'magento/module-test',
            ]
        ];
        $outputDirectory = "./outputDir";

        // Set expectations for test Webhooks.
        $resourceModelWebhookMock = $this->createMock(Webhook::class);
        $resourceModelWebhookMock->expects(self::once())
            ->method('getName')
            ->willReturn($resourceModelWebhookName);
        $resourceModelWebhookMock->expects(self::once())
            ->method('getType')
            ->willReturn($resourceModelWebhookType);
        $apiWebhookMock = $this->createMock(Webhook::class);
        $apiWebhookMock->expects(self::once())
            ->method('getName')
            ->willReturn($apiWebhookName);
        $apiWebhookMock->expects(self::once())
            ->method('getType')
            ->willReturn($apiWebhookType);
        $observerWebhookMock = $this->createMock(Webhook::class);
        $observerWebhookMock->expects(self::once())
            ->method('getName')
            ->willReturn($observerWebhookName);
        $observerWebhookMock->expects(self::once())
            ->method('getType')
            ->willReturn($observerWebhookType);

        $this->webhookListMock->expects(self::exactly(1))
            ->method('getAll')
            ->willReturn([
                $apiWebhookMock,
                $resourceModelWebhookMock,
                $observerWebhookMock
            ]);

        $this->webhookAllowedValidator->expects(self::exactly(3))
            ->method('validate');

        $this->pluginCollectorMock->expects(self::exactly(2))
            ->method('collect')
            ->willReturnCallback(
                function (
                    string $webhookName,
                    string $webhookType
                ) use (
                    $apiWebhookName,
                    $apiWebhookType,
                    $resourceModelWebhookName,
                    $resourceModelWebhookType,
                    $apiInterfacePlugin,
                    $resourceModelPlugin,
                ) {
                    static $count = 0;
                    switch ($count++) {
                        case 0:
                            self::assertEquals($webhookName, $apiWebhookName);
                            self::assertEquals($webhookType, $apiWebhookType);
                            return $apiInterfacePlugin;
                        case 1:
                            self::assertEquals($webhookName, $resourceModelWebhookName);
                            self::assertEquals($webhookType, $resourceModelWebhookType);
                            return $resourceModelPlugin;
                    };
                    return null;
                }
            );
        $this->pluginConverterMock->expects(self::once())
            ->method('convert')
            ->with(Generator::OBSERVER_EVENT_INTERFACE_CONFIGURATION)
            ->willReturn($observerEventPlugin);

        $this->moduleCollectorMock->expects(self::once())
            ->method('getModules')
            ->willReturn($dependencies);

        $this->moduleGeneratorMock->expects(self::once())
            ->method('setOutputDir')
            ->with($outputDirectory);

        // Set expectations for setup of Module passed to the ModuleGenerator's run method.
        $this->moduleGeneratorMock->expects(self::once())
            ->method('run')
            ->with(
                $this->callback(
                    function ($module) use (
                        $plugins,
                        $dependencies,
                        $observerEventPlugin,
                        $observerWebhooks
                    ) {
                        $this->assertEquals($plugins, $module->getPlugins());
                        $this->assertEquals($dependencies, $module->getDependencies());
                        $this->assertEquals($observerEventPlugin, $module->getObserverEventPlugin());
                        $this->assertEquals($observerWebhooks, $module->getObserverEvents());
                        return true;
                    }
                ),
                null
            );
        $this->generator->run($outputDirectory);
    }

    /**
     * Checks that collector and converter methods do not run two times for the same webhook.
     *
     * @return void
     * @throws Exception
     */
    public function testRunWithDuplicateWebhooks()
    {
        $resourceModelWebhookName = 'plugin.resource_model_event';
        $resourceModelWebhookType = 'after';
        $resourceModelCollection = ['ResourceModelClass' => ['name' => 'methodName']];

        // Set expectations for test Webhook
        $webhookMock = $this->createMock(Webhook::class);
        $webhookMock->expects(self::exactly(2))
            ->method('getName')
            ->willReturn($resourceModelWebhookName);
        $webhookMock->expects(self::exactly(2))
            ->method('getType')
            ->willReturn($resourceModelWebhookType);

        $this->webhookListMock->expects(self::once())
            ->method('getAll')
            ->willReturn([$webhookMock, $webhookMock]);

        $this->webhookAllowedValidator->expects(self::exactly(2))
            ->method('validate');

        $this->pluginCollectorMock->expects(self::once())
            ->method('collect')
            ->willReturn($resourceModelCollection);
        $this->pluginConverterMock->expects(self::once())
            ->method('convert')
            ->with(Generator::OBSERVER_EVENT_INTERFACE_CONFIGURATION);

        $this->generator->run('./output');
    }

    /**
     * Checks running of collector and converter classes when 2 webhooks have the same name but different types.
     *
     * @return void
     * @throws Exception
     */
    public function testRunWithCommonWebhookName()
    {
        $resourceModelWebhookName = 'plugin.resource_model_event';

        // Set expectations for test Webhook
        $webhookOneMock = $this->createMock(Webhook::class);
        $webhookOneMock->expects(self::once())
            ->method('getName')
            ->willReturn($resourceModelWebhookName);
        $webhookOneMock->expects(self::once())
            ->method('getType')
            ->willReturn('before');
        $webhookTwoMock = $this->createMock(Webhook::class);
        $webhookTwoMock->expects(self::once())
            ->method('getName')
            ->willReturn($resourceModelWebhookName);
        $webhookTwoMock->expects(self::once())
            ->method('getType')
            ->willReturn('after');

        $this->webhookListMock->expects(self::once())
            ->method('getAll')
            ->willReturn([$webhookOneMock, $webhookTwoMock]);

        $this->webhookAllowedValidator->expects(self::exactly(2))
            ->method('validate');

        $this->pluginCollectorMock->expects(self::exactly(2))
            ->method('collect')
            ->willReturnCallback(function (string $webhookName, string $webhookType) {
                static $count = 0;
                switch ($count++) {
                    case 0:
                        self::assertEquals('plugin.resource_model_event', $webhookName);
                        self::assertEquals('before', $webhookType);
                        break;
                    case 1:
                        self::assertEquals('plugin.resource_model_event', $webhookName);
                        self::assertEquals('after', $webhookType);
                        break;
                };
                return ['ResourceModelClass' => ['name' => 'methodName']];
            });
        $this->pluginConverterMock->expects(self::once())
            ->method('convert')
            ->with(Generator::OBSERVER_EVENT_INTERFACE_CONFIGURATION);

        $this->generator->run('./output');
    }

    /**
     * Checks that an exception is thrown when an webhook method name with an unknown prefix is processed.
     *
     * @return void
     */
    public function testRunInvalidName(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('The specified method name has an invalid prefix: "unknown"');

        $webhookMock = $this->createMock(Webhook::class);
        $webhookMock->expects(self::once())
            ->method('getName')
            ->willReturn('unknown.event');
        $this->webhookListMock->expects(self::once())
            ->method('getAll')
            ->willReturn([$webhookMock]);

        $this->generator->run("output");
    }

    /**
     * Checks that an exception is thrown when a disallowed webhook method name is processed.
     *
     * @return void
     * @throws Exception
     */
    public function testRunDisallowedWebhookName(): void
    {
        $errorMessage = 'Creating a webhook with method name "plugin.disallowed_event" is not allowed';
        $this->expectException(Exception::class);
        $this->expectExceptionMessage($errorMessage);

        $webhookMock = $this->createMock(Webhook::class);
        $webhookMock->expects(self::once())
            ->method('getName')
            ->willReturn('plugin.disallowed_event');
        $this->webhookListMock->expects(self::once())
            ->method('getAll')
            ->willReturn([$webhookMock]);
        $this->webhookAllowedValidator->expects(self::once())
            ->method('validate')
            ->willThrowException(new ValidatorException(__($errorMessage)));

        $this->generator->run("output");
    }
}
