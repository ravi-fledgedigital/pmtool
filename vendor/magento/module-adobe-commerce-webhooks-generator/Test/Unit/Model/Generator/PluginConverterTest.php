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

use Magento\AdobeCommerceWebhooksGenerator\Model\Generator\PluginConverter;
use Magento\AdobeCommerceOutOfProcessExtensibility\Model\Generator\PluginConverter\InterfaceProcessor;
use PHPUnit\Framework\TestCase;

/**
 * Tests for @see PluginConverter
 */
class PluginConverterTest extends TestCase
{
    /**
     * @var PluginConverter
     */
    private PluginConverter $pluginConverter;

    protected function setUp(): void
    {
        $this->pluginConverter = new PluginConverter(new InterfaceProcessor());
    }

    public function testConvertApiInterface()
    {
        self::assertEquals(
            [
                'class' => 'Magento\AdobeCommerceWebhookPlugins\Plugin\Customer\Api' .
                    '\CustomerRepositoryInterfaceAfterSavePlugin',
                'namespace' => 'Magento\AdobeCommerceWebhookPlugins\Plugin\Customer\Api',
                'interface' => 'Magento\Customer\Api\CustomerRepositoryInterface',
                'interfaceShort' => 'CustomerRepositoryInterface',
                'pluginName' => 'magento_customer_customerrepositoryinterfaceaftersave_plugin',
                'name' => 'CustomerRepositoryInterfaceAfterSavePlugin',
                'type' => 'Api',
                'path' => '/Plugin/Customer/Api/CustomerRepositoryInterfaceAfterSavePlugin.php',
                'webhookName' => 'plugin.magento.customer.api.customer_repository.save',
                'webhookType' => 'after',
                'method' => [
                    'methodName' => 'save',
                    'pluginMethodName' => 'afterSave',
                    'params' => []
                ]
            ],
            $this->pluginConverter->convert(
                [
                    'Magento\Customer\Api\CustomerRepositoryInterface' => [
                        ['name' => 'save']
                    ]
                ],
                'plugin.magento.customer.api.customer_repository.save',
                'after',
                PluginConverter::TYPE_API_INTERFACE
            )
        );
    }

    public function testConvertResourceModels()
    {
        self::assertEquals(
            [
                'class' => 'Magento\AdobeCommerceWebhookPlugins\Plugin\Sales\ResourceModel\Order\TaxBeforeDeletePlugin',
                'namespace' => 'Magento\AdobeCommerceWebhookPlugins\Plugin\Sales\ResourceModel\Order',
                'interface' => 'Magento\Sales\Model\ResourceModel\Order\Tax',
                'interfaceShort' => 'Tax',
                'pluginName' => 'magento_sales_taxbeforedelete_plugin',
                'name' => 'TaxBeforeDeletePlugin',
                'type' => 'ResourceModel',
                'path' => '/Plugin/Sales/ResourceModel/Order/TaxBeforeDeletePlugin.php',
                'webhookName' => 'plugin.magento.sales.model.resource_model.order.tax.delete',
                'webhookType' => 'before',
                'method' => [
                    'methodName' => 'delete',
                    'pluginMethodName' => 'beforeDelete',
                    'params' => ['some params']
                ]
            ],
            $this->pluginConverter->convert(
                [
                    'Magento\Sales\Model\ResourceModel\Order\Tax' => [
                        ['name' => 'delete', 'params' => ['some params']]
                    ]
                ],
                'plugin.magento.sales.model.resource_model.order.tax.delete',
                'before',
                PluginConverter::TYPE_RESOURCE_MODEL
            )
        );
    }

    public function testConvertWithoutOptionalArguments()
    {
        self::assertEquals(
            [
                'class' => 'Magento\AdobeCommerceWebhookPlugins\Plugin\Framework\ManagerInterfacePlugin',
                'namespace' => 'Magento\AdobeCommerceWebhookPlugins\Plugin\Framework',
                'interface' => 'Magento\Framework\Event\ManagerInterface',
                'interfaceShort' => 'ManagerInterface',
                'pluginName' => 'magento_framework_managerinterface_plugin',
                'name' => 'ManagerInterfacePlugin',
                'type' => null,
                'path' => '/Plugin/Framework/ManagerInterfacePlugin.php',
                'webhookName' => null,
                'webhookType' => null,
                'method' => [
                    'methodName' => 'dispatch',
                    'pluginMethodName' => '',
                    'params' => []
                ]
            ],
            $this->pluginConverter->convert(
                [
                    'Magento\Framework\Event\ManagerInterface' => [
                        ['name' => 'dispatch']
                    ]
                ]
            )
        );
    }
}
