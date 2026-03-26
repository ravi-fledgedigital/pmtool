<?php

/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */

declare(strict_types=1);

namespace Vaimo\AEPProductFileExport\Model\Export\Mapping;

use Magento\Framework\Stdlib\DateTime\DateTime;
use Vaimo\AEPProductFileExport\Model\Export\AEPProduct;

trait ProductCallbacksTrait
{
    private DateTime $dateTime;

    public function __construct(DateTime $dateTime)
    {
        $this->dateTime = $dateTime;
    }

    /**
     * @param string[] $item
     * @return string
     */
    public function getSkuStoreViewCode(array $item): string
    {
        return $item[AEPProduct::COL_SKU] . '|' . $item[AEPProduct::COL_STORE];
    }

    /**
     * @param string[] $item
     * @return string
     */
    public function getEan(array $item): string
    {
        if ($item['product_type'] === 'simple') {
            return $item['sku'];
        }

        return '';
    }

    /**
     * @param string[] $item
     * @return string
     */
    public function getParentSku(array $item): string
    {
        if ($item['product_type'] === 'simple') {
            return $item['style_code'] ?? '';
        }

        return '';
    }

    /**
     * @param string[] $item
     * @return string
     */
    public function getDescription(array $item): string
    {
        $productDescription = \strip_tags($item['description'] ?? '');
        $result = \str_replace(["\n", "\t", "\r\n", "\r"], '', $productDescription);

        return \str_replace('"', "'", $result);
    }

    /**
     * @param string[] $item
     * @return string
     */
    public function getUpdatedAt(array $item): string
    {
        return $this->dateTime->date(self::DATE_FORMAT, $item['updated_at']);
    }
}
