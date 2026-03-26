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

namespace Magento\BundleStaging\Plugin\CatalogStaging\Model\Product;

use Exception;
use Magento\BundleStaging\Model\Product\BundleProductCopier;
use Magento\BundleStaging\Model\Product\SelectionCopier;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product\Type;
use Magento\CatalogStaging\Model\ResourceModel\AttributeCopier;

/**
 * Plugin Class for Copy Bundle product options, selections and relations
 */
class CopyBundleProductPlugin
{
    /**
     * @param BundleProductCopier $bundleProductCopier
     * @param SelectionCopier $selectionCopier
     */
    public function __construct(
        private readonly BundleProductCopier $bundleProductCopier,
        private readonly SelectionCopier $selectionCopier
    ) {
    }

    /**
     * Copy Bundle product option and selection for staging
     *
     * @param AttributeCopier $subject
     * @param bool $result
     * @param string $entityType
     * @param array $entityData
     * @param string $from
     * @param string $to
     * @return bool
     * @throws Exception
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterCopy(
        AttributeCopier $subject,
        bool $result,
        $entityType,
        array $entityData,
        $from,
        $to
    ): bool {
        if ($entityType === ProductInterface::class && $entityData['type_id'] === Type::TYPE_BUNDLE) {
            $this->bundleProductCopier->copy($entityData, (int)$from, (int)$to);
            $this->selectionCopier->copy($entityData, (int)$from, (int)$to);
        }
        return $result;
    }
}
