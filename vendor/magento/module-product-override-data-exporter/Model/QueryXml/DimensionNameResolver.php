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

namespace Magento\ProductOverrideDataExporter\Model\QueryXml;

use Magento\ProductOverrideDataExporter\Model\ViewTableMaintainer;
use Magento\QueryXml\Model\DB\NameResolver;

/**
 * Resolver for source names
 *
 * Deprecated. This class is not used anymore.
 * @deprecated
 * @see \Magento\ProductOverrideDataExporter\Model\Query\DimensionalPermissionsIndexQuery
 */
class DimensionNameResolver extends NameResolver
{
    /**
     * @param ViewTableMaintainer $viewTableMaintainer
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function __construct(
        ViewTableMaintainer $viewTableMaintainer
    ) {}

    /**
     * Returns element for name
     * Deprecated. Dimensional view index tables are not created anymore. Using custom query instead
     *
     * @param array $elementConfig
     * @return string
     * @deprecated
     * @see \Magento\ProductOverrideDataExporter\Model\Query\DimensionalPermissionsIndexQuery
     */
    public function getName($elementConfig): string
    {
        return parent::getName($elementConfig);
    }

    /**
     * Return alias
     * Deprecated. Dimensional view index tables are not created anymore. Using custom query instead
     *
     * @param array $elementConfig
     * @return string
     * @deprecated
     * @see \Magento\ProductOverrideDataExporter\Model\Query\DimensionalPermissionsIndexQuery
     */
    public function getAlias($elementConfig)
    {
        return parent::getAlias($elementConfig);
    }
}
