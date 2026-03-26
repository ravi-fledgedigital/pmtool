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

use Magento\AdobeCommerceOutOfProcessExtensibility\Model\Generator\ClassGeneratorInterface;
use Magento\AdobeCommerceOutOfProcessExtensibility\Model\Generator\ModuleBlock;
use Magento\AdobeCommerceOutOfProcessExtensibility\Model\Generator\ModuleFileWriter;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\View\Element\BlockInterface;
use Magento\Framework\View\TemplateEngine\Php;

/**
 * Generates php classes to be included in a generated webhooks module.
 */
class WebhooksClassGenerator implements ClassGeneratorInterface
{
    public const MODULE_VENDOR = 'Magento';
    public const MODULE_NAME = 'AdobeCommerceWebhookPlugins';

    public const MODULE_PLUGIN_SPACE = 'Plugin';

    private const PLUGIN_API_RESOURCE_MODEL_TPL = 'pluginApiAndResourceModel.phtml';
    private const OBSERVER_EVENT_PLUGIN_TPL = 'observerPlugin.phtml';
    private const OBSERVER_IGNORED_EVENTS = [
        'core_collection_abstract_load_before',
        'core_collection_abstract_load_after',
        'core_abstract_load_before',
        'core_abstract_load_after',
        'model_load_before',
        'model_load_after',
        'customer_session_init',
        'controller_front_send_response_before',
    ];

    private const METHOD_BEFORE = 'before';
    private const METHOD_AFTER = 'after';

    private const PHP_REQ = '^8.1';

    /**
     * @param Php $templateEngine
     * @param ModuleFileWriter $moduleFileWriter
     * @param string|null $templatesPath
     */
    public function __construct(
        private Php $templateEngine,
        private ModuleFileWriter $moduleFileWriter,
        private ?string $templatesPath = null
    ) {
        $this->templatesPath = $templatesPath ?: __DIR__ . '/../../templates';
    }

    /**
     * @inheritDoc
     */
    public function generateClasses(ModuleBlock $moduleBlock, string $path): void
    {
        $this->generatePluginList($moduleBlock, $path);
        $this->generateObserverList($moduleBlock, $path);
        $this->generatePlugins($moduleBlock, $path);
        $this->generateObserverEventPlugin($moduleBlock, $path);
    }

    /**
     * @inheritDoc
     */
    public function getTemplatesPath(): string
    {
        return $this->templatesPath;
    }

    /**
     * @inheritDoc
     */
    public function getPhpVersion(): string
    {
        return self::PHP_REQ;
    }

    /**
     * Generates plugins for api interfaces and resource model classes.
     *
     * @param ModuleBlock $moduleBlock
     * @param string $basePath
     * @return void
     * @throws FileSystemException
     */
    private function generatePlugins(ModuleBlock $moduleBlock, string $basePath): void
    {
        $module = $moduleBlock->getModule();
        foreach ($module->getPlugins() as $plugin) {
            $plugin['renderedMethods'][] = $this->renderMethod(
                $moduleBlock,
                $plugin['webhookType'],
                $plugin
            );

            $this->moduleFileWriter->createFileFromTemplate(
                $moduleBlock,
                $this->templatesPath . DIRECTORY_SEPARATOR . self::PLUGIN_API_RESOURCE_MODEL_TPL,
                $basePath . $plugin['path'],
                $plugin
            );
        }
    }

    /**
     * Generates plugin for observer events.
     *
     * @param ModuleBlock $moduleBlock
     * @param string $basePath
     * @return void
     * @throws FileSystemException
     */
    private function generateObserverEventPlugin(ModuleBlock $moduleBlock, string $basePath): void
    {
        $module = $moduleBlock->getModule();
        $plugin = $module->getObserverEventPlugin();

        if (!empty($plugin)) {
            $plugin['ignoredEvents'] = self::OBSERVER_IGNORED_EVENTS;
            $plugin['method']['params'] = [
                [
                    'name' => 'eventName'
                ],
                [
                    'name' => 'data',
                    'isDefaultValueAvailable' => true,
                    'defaultValue' => '[]',
                ]
            ];
            $plugin['renderedMethods'][] = $this->renderMethod(
                $moduleBlock,
                self::METHOD_BEFORE,
                array_replace_recursive(
                    $plugin,
                    ['method' => ['pluginMethodName' => 'beforeDispatch']]
                )
            );
            $plugin['renderedMethods'][] = $this->renderMethod(
                $moduleBlock,
                self::METHOD_AFTER,
                array_replace_recursive(
                    $plugin,
                    ['method' => ['pluginMethodName' => 'afterDispatch']]
                )
            );

            $this->moduleFileWriter->createFileFromTemplate(
                $moduleBlock,
                $this->templatesPath . DIRECTORY_SEPARATOR . self::OBSERVER_EVENT_PLUGIN_TPL,
                $basePath . $plugin['path'],
                $plugin
            );
        }
    }

    /**
     * Generates a class with a list of subscribed plugin webhook methods.
     *
     * @param ModuleBlock $moduleBlock
     * @param string $basePath
     * @return void
     * @throws FileSystemException
     */
    private function generatePluginList(ModuleBlock $moduleBlock, string $basePath): void
    {
        $this->moduleFileWriter->createFileFromTemplate(
            $moduleBlock,
            $this->templatesPath . DIRECTORY_SEPARATOR . 'webhooksList.phtml',
            $basePath . '/WebhooksList/Plugin.php',
            [
                'name' => 'Plugin',
                'plugins' => $moduleBlock->getModule()->getPlugins()
            ]
        );
    }

    /**
     * Generates a class with a list of all subscribed observer webhook methods.
     *
     * @param ModuleBlock $moduleBlock
     * @param string $basePath
     * @return void
     * @throws FileSystemException
     */
    private function generateObserverList(ModuleBlock $moduleBlock, string $basePath): void
    {
        if (empty($moduleBlock->getModule()->getObserverEvents())) {
            return;
        }

        $this->moduleFileWriter->createFileFromTemplate(
            $moduleBlock,
            $this->templatesPath . DIRECTORY_SEPARATOR . 'webhooksList.phtml',
            $basePath . '/WebhooksList/Observer.php',
            [
                'name' => 'Observer',
                'observers' => $moduleBlock->getModule()->getObserverEvents()
            ]
        );
    }

    /**
     * Renders a method of a plugin class.
     *
     * @param BlockInterface $block
     * @param string $methodType
     * @param array $dictionary
     * @return string
     */
    private function renderMethod(BlockInterface $block, string $methodType, array $dictionary = []): string
    {
        return $this->templateEngine->render(
            $block,
            $this->templatesPath . DIRECTORY_SEPARATOR . 'methods' . DIRECTORY_SEPARATOR . $methodType . '.phtml',
            $dictionary
        );
    }
}
