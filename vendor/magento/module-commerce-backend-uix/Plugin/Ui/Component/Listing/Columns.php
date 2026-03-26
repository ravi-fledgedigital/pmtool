<?php
/**
 * ADOBE CONFIDENTIAL
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
 */
declare(strict_types=1);

namespace Magento\CommerceBackendUix\Plugin\Ui\Component\Listing;

use Magento\CommerceBackendUix\Model\AuthorizationValidator;
use Magento\CommerceBackendUix\Model\Cache\Cache;
use Magento\CommerceBackendUix\Ui\Component\Listing\Column\CustomColumn;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns as BaseColumns;

class Columns
{
    private const MESH_ID = 'meshId';
    private const LABEL = 'label';
    private const ATTRIBUTE_CODE = 'attribute_code';
    private const COLUMN_ID = 'columnId';
    private const DATA_TYPE = 'data_type';
    private const TYPE = 'type';
    private const FILTER = 'filter';
    private const ALIGN = 'align';
    private const COMPONENT_TYPE = 'componentType';
    private const SORTABLE = 'sortable';
    private const DATA = 'data';
    private const JS_CONFIG = 'js_config';
    private const CONFIG = 'config';
    private const CONTEXT = 'context';
    private const COMPONENT = 'component';
    private const COMPONENT_CLASS = 'class';
    private const PROPERTIES = 'properties';
    private const COLUMN = 'column';

    /**
     * @param UiComponentFactory $uiComponentFactory
     * @param Cache $cache
     * @param AuthorizationValidator $authorization
     */
    public function __construct(
        private UiComponentFactory $uiComponentFactory,
        private Cache $cache,
        private AuthorizationValidator $authorization
    ) {
    }

    /**
     * Prepare columns to be added to grid
     *
     * @param BaseColumns $subject
     * @return void
     * @throws LocalizedException
     */
    public function afterPrepare(BaseColumns $subject): void
    {
        if (!$this->authorization->isAuthorized()) {
            return;
        }

        $namespace = $subject->getContext()->getNamespace();
        $registrations = $this->cache->getRegisteredColumns($namespace);
        $this->addColumnsToGrid($subject, $registrations);
    }

    /**
     * Add columns to grid
     *
     * @param BaseColumns $subject
     * @param array $registeredColumns
     * @return void
     * @throws LocalizedException
     */
    private function addColumnsToGrid(BaseColumns $subject, array $registeredColumns): void
    {
        $registeredColumnsProperties = $registeredColumns[self::PROPERTIES] ?? [];
        foreach ($registeredColumnsProperties as $columnProperties) {
            $newConfig = $this->buildGridConfig($columnProperties, $registeredColumns[self::DATA]);
            $arguments = $this->buildArguments($subject, $newConfig);

            $column = $this->uiComponentFactory->create(
                $newConfig[self::ATTRIBUTE_CODE],
                $newConfig[self::COMPONENT_TYPE],
                $arguments
            );
            $column->prepare();
            $subject->addComponent($newConfig[self::ATTRIBUTE_CODE], $column);
        }
    }

    /**
     * Build grid columns config
     *
     * @param array $properties
     * @param array $data
     * @return array
     */
    private function buildGridConfig(array $properties, array $data): array
    {
        return [
            self::MESH_ID => $data[self::MESH_ID],
            self::LABEL => $properties[self::LABEL],
            self::ATTRIBUTE_CODE => $properties[self::COLUMN_ID],
            self::DATA_TYPE => $properties[self::TYPE],
            self::FILTER => false,
            self::ALIGN => $properties[self::ALIGN],
            self::SORTABLE => false,
            self::COMPONENT_TYPE => self::COLUMN
        ];
    }

    /**
     * Build arguments array to build a column
     *
     * @param BaseColumns $subject
     * @param array $config
     * @return array
     */
    private function buildArguments(BaseColumns $subject, array $config): array
    {
        return [
            self::DATA => [
                self::JS_CONFIG => [
                    self::COMPONENT => 'Magento_Ui/js/grid/columns/column'
                ],
                self::CONFIG => $config,
            ],
            self::CONFIG => [
                self::COMPONENT_CLASS => CustomColumn::class
            ],
            self::CONTEXT => $subject->getContext()
        ];
    }
}
