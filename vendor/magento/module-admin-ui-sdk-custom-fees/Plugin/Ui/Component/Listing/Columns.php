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

namespace Magento\AdminUiSdkCustomFees\Plugin\Ui\Component\Listing;

use Magento\AdminUiSdkCustomFees\Model\Cache\Cache;
use Magento\AdminUiSdkCustomFees\Ui\Component\Listing\Column\CustomColumn;
use Magento\CommerceBackendUix\Model\AuthorizationValidator;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns as BaseColumns;

/**
* Columns class to append custom columns to grid
*/
class Columns
{
    private const LABEL = 'label';
    private const ATTRIBUTE_CODE = 'attribute_code';
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
    private const CUSTOM_FEE_ID = 'id';

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
        if ($this->isNamespaceEligible($namespace)) {
            $this->addColumnsToGrid($subject, $this->cache->getOrderCustomFees());
        }
    }

    /**
     * Check if namespace is eligible for custom fees
     *
     * @param string $namespace
     * @return bool
     */
    private function isNamespaceEligible(string $namespace): bool
    {
        return $namespace === 'sales_order_view_invoice_grid'
            || $namespace === 'sales_order_view_creditmemo_grid'
            || $namespace === 'sales_order_invoice_grid'
            || $namespace === 'sales_order_creditmemo_grid';
    }

    /**
     * Add columns to grid
     *
     * @param BaseColumns $subject
     * @param array $customFees
     * @return void
     * @throws LocalizedException
     */
    private function addColumnsToGrid(BaseColumns $subject, array $customFees): void
    {
        foreach ($customFees as $customFee) {
            $newConfig = $this->buildGridConfig($customFee);
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
    private function buildGridConfig(array $customFee): array
    {
        return [
            self::LABEL => $customFee[self::LABEL],
            self::ATTRIBUTE_CODE => $customFee[self::CUSTOM_FEE_ID],
            self::DATA_TYPE => 'integer',
            self::FILTER => false,
            self::ALIGN => 'center',
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
