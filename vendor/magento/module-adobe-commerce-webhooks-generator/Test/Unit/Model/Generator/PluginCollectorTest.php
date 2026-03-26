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

namespace Magento\AdobeCommerceWebhooksGenerator\Test\Unit\Model\Generator;

use Magento\AdobeCommerceOutOfProcessExtensibility\Model\Generator\Collector\ApiServiceCollector;
use Magento\AdobeCommerceOutOfProcessExtensibility\Model\Generator\Collector\CollectorException;
use Magento\AdobeCommerceOutOfProcessExtensibility\Model\Generator\Collector\ResourceModelCollector;
use Magento\AdobeCommerceWebhooksGenerator\Model\Generator\PluginCollector;
use Magento\AdobeCommerceWebhooksGenerator\Model\Generator\PluginConverter;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Tests for @see PluginCollector
 */
class PluginCollectorTest extends TestCase
{
    /**
     * @var ApiServiceCollector|MockObject
     */
    private ApiServiceCollector|MockObject $apiServiceCollectorMock;

    /**
     * @var ResourceModelCollector|MockObject
     */
    private ResourceModelCollector|MockObject $resourceModelCollectorMock;

    /**
     * @var PluginConverter|MockObject
     */
    private PluginConverter|MockObject $pluginConverterMock;

    /**
     * @var PluginCollector
     */
    private PluginCollector $pluginCollector;

    protected function setUp(): void
    {
        $this->apiServiceCollectorMock = $this->createMock(ApiServiceCollector::class);
        $this->pluginConverterMock = $this->createMock(PluginConverter::class);
        $this->resourceModelCollectorMock = $this->createMock(ResourceModelCollector::class);

        $this->pluginCollector = new PluginCollector(
            $this->apiServiceCollectorMock,
            $this->pluginConverterMock,
            $this->resourceModelCollectorMock,
        );
    }

    public function testCollectResourceModel()
    {
        $webhookName = 'plugin.resource_model.some_name';
        $webhookType = 'before';
        $this->resourceModelCollectorMock->expects(self::once())
            ->method('collect')
            ->with($webhookName)
            ->willReturn(['ResourceModelClass' => ['name' => 'ResourceModelClass']]);
        $this->pluginConverterMock->expects(self::once())
            ->method('convert')
            ->with(
                ['ResourceModelClass' => ['name' => 'ResourceModelClass']],
                $webhookName,
                $webhookType,
                PluginConverter::TYPE_RESOURCE_MODEL,
            );
        $this->apiServiceCollectorMock->expects(self::never())
            ->method('collect');

        $this->pluginCollector->collect($webhookName, $webhookType);
    }

    public function testCollectApiInterface()
    {
        $webhookName = 'plugin.api.some_name';
        $webhookType = 'before';
        $this->apiServiceCollectorMock->expects(self::once())
            ->method('collect')
            ->with($webhookName)
            ->willReturn(['ApiClass' => ['name' => 'ApiInterface']]);
        $this->pluginConverterMock->expects(self::once())
            ->method('convert')
            ->with(
                ['ApiClass' => ['name' => 'ApiInterface']],
                $webhookName,
                $webhookType,
                PluginConverter::TYPE_API_INTERFACE,
            );
        $this->resourceModelCollectorMock->expects(self::never())
            ->method('collect');

        $this->pluginCollector->collect($webhookName, $webhookType);
    }

    public function testCollectorException()
    {
        $this->expectException(CollectorException::class);

        $webhookName = 'plugin.resource_model.some_name';
        $webhookType = 'before';
        $this->resourceModelCollectorMock->expects(self::once())
            ->method('collect')
            ->willThrowException(new CollectorException('Some Error'));
        $this->pluginConverterMock->expects(self::never())
            ->method('convert');
        $this->apiServiceCollectorMock->expects(self::never())
            ->method('collect');

        $this->pluginCollector->collect($webhookName, $webhookType);
    }
}
