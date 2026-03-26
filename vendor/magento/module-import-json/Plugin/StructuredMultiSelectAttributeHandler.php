<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ImportJson\Plugin;

use Magento\CatalogImportExport\Model\Import\Product;

/**
 * Handles parsing of multi-select attributes passed as structured data such as arrays.
 */
class StructuredMultiSelectAttributeHandler
{
    /**
     * Do not parse multi-select values if they are already an array.
     *
     * @param Product $subject
     * @param callable $proceed
     * @param array|string $values
     * @param string $delimiter
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundParseMultiselectValues(
        Product      $subject,
        callable     $proceed,
        $values,
        $delimiter = ''
    ): array {
        if (is_array($values)) {
            return $values;
        }
        return $proceed($values, $delimiter);
    }
}
