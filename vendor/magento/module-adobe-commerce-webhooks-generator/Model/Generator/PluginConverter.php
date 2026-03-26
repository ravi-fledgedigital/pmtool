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

namespace Magento\AdobeCommerceWebhooksGenerator\Model\Generator;

use Magento\AdobeCommerceOutOfProcessExtensibility\Model\Generator\PluginConverter\InterfaceProcessor;

/**
 * Converts webhook data to the data format needed for plugin generation
 */
class PluginConverter
{
    public const TYPE_API_INTERFACE = 'Api';
    public const TYPE_RESOURCE_MODEL = 'ResourceModel';

    /**
     * @param InterfaceProcessor $interfaceProcessor
     */
    public function __construct(
        private InterfaceProcessor $interfaceProcessor
    ) {
    }

    /**
     * Converts data for a class or interface and webhook information into an array of data for plugin generation.
     *
     * @param array $interfaceData
     * @param string|null $webhookName
     * @param string|null $webhookType
     * @param string|null $type
     * @return array
     */
    public function convert(
        array $interfaceData,
        ?string $webhookName = null,
        ?string $webhookType = null,
        ?string $type = null
    ): array {
        $interface = key($interfaceData);
        $interfaceNameData = $this->interfaceProcessor->process(
            $interface,
            WebhooksClassGenerator::MODULE_VENDOR,
            WebhooksClassGenerator::MODULE_NAME,
            WebhooksClassGenerator::MODULE_PLUGIN_SPACE,
            $type
        );

        $pluginNamespace = $interfaceNameData[InterfaceProcessor::PLUGIN_NAMESPACE];
        $interfaceNameShort = $interfaceNameData[InterfaceProcessor::INTERFACE_NAME_SHORT];

        $method = $interfaceData[$interface][0];

        $pluginMethodName =
            array_key_exists('name', $method) && $webhookType !== null
                ? ucfirst($webhookType) . ucfirst($method['name'])
                : '';

        return [
            'class' => $pluginNamespace . '\\' . $interfaceNameShort . $pluginMethodName . 'Plugin',
            'namespace' => $pluginNamespace,
            'interface' => $interface,
            'interfaceShort' => $interfaceNameShort,
            'pluginName' => implode('_', array_map(
                'strtolower',
                [
                    $interfaceNameData[InterfaceProcessor::VENDOR],
                    $interfaceNameData[InterfaceProcessor::MODULE],
                    $interfaceNameShort . $pluginMethodName,
                    'Plugin'
                ]
            )),
            'name' => $interfaceNameShort . $pluginMethodName . 'Plugin',
            'path' => $interfaceNameData[InterfaceProcessor::PATH] . $interfaceNameShort . $pluginMethodName
                . 'Plugin.php',
            'type' => $type,
            'webhookName' => $webhookName,
            'webhookType' => $webhookType,
            'method' => [
                'methodName' => $method['name'] ?? null,
                'pluginMethodName' => lcfirst($pluginMethodName),
                'params' => $method['params'] ?? []
            ]
        ];
    }
}
