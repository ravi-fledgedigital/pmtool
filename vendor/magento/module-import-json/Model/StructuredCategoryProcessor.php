<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ImportJson\Model;

use Magento\CatalogImportExport\Model\Import\Product\CategoryProcessor;

/**
 * Returns IDs of categories passed as structured array data.
 */
class StructuredCategoryProcessor extends CategoryProcessor
{
    /**
     * Return IDs of categories passed as structured array data.
     *
     * @param string|array $categoriesString
     * @param string $categoriesSeparator
     * @return array
     */
    public function upsertCategories($categoriesString, $categoriesSeparator): array
    {
        if (is_array($categoriesString)) {
            return array_map([$this, 'upsertCategory'], $categoriesString);
        }

        return parent::upsertCategories($categoriesString, $categoriesSeparator);
    }
}
