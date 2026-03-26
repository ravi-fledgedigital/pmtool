<?php

/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */

declare(strict_types=1);

namespace Vaimo\AEPProductFileExport\Model\Export\Mapping;

use Vaimo\AEPProductFileExport\Model\Export\MappingInterface;

class AdobeExperiencePlatform implements MappingInterface
{
    use ProductCallbacksTrait;

    private const DATE_FORMAT = 'Y-m-d\TH:i:s.Z\Z';

    /**
     * @return string[][]
     */
    public function getMapping(): array
    {
        return [
            'skuStoreViewCode' => ['type' => self::MAPPING_TYPE_CALLBACK, 'value' => 'getSkuStoreViewCode'],
            'ean' => ['type' => self::MAPPING_TYPE_CALLBACK, 'value' => 'getEan'],
            'sku' => ['type' => self::MAPPING_TYPE_ATTRIBUTE, 'value' => 'sku'],
            'parentSku' => ['type' => self::MAPPING_TYPE_CALLBACK, 'value' => 'getParentSku'],
            'storeViewCode' => ['type' => self::MAPPING_TYPE_ATTRIBUTE, 'value' => '_store'],
            'styleNumber' => ['type' => self::MAPPING_TYPE_ATTRIBUTE, 'value' => 'style_number'],
            'productName' => ['type' => self::MAPPING_TYPE_ATTRIBUTE, 'value' => 'name'],
            'productDesc' => ['type' => self::MAPPING_TYPE_CALLBACK, 'value' => 'getDescription'],
            'productType' => ['type' => self::MAPPING_TYPE_ATTRIBUTE, 'value' => 'product_group'],
            'productSubType1' => ['type' => self::MAPPING_TYPE_ATTRIBUTE, 'value' => 'product_group'],
            'productSubType2' => ['type' => self::MAPPING_TYPE_CALLBACK, 'value' => 'getProductSubType2'],
            'productGenderType' => ['type' => self::MAPPING_TYPE_ATTRIBUTE, 'value' => 'gender'],
            'productKidsType' => ['type' => self::MAPPING_TYPE_CALLBACK, 'value' => 'getProductKidsType'],
            'productSeries' => ['type' => self::MAPPING_TYPE_ATTRIBUTE, 'value' => 'series'],
            'productFeatures' => ['type' => self::MAPPING_TYPE_ATTRIBUTE, 'value' => 'features'],
            'productFabric' => ['type' => self::MAPPING_TYPE_ATTRIBUTE, 'value' => 'fabric'],
            'sleeveType' => ['type' => self::MAPPING_TYPE_ATTRIBUTE, 'value' => 'sleeve_type'],
            'productMaterial' => ['type' => self::MAPPING_TYPE_ATTRIBUTE, 'value' => 'material_code'],
            'shoeStyle' => ['type' => self::MAPPING_TYPE_ATTRIBUTE, 'value' => 'shoe_style'],
            'veganFlag' => ['type' => self::MAPPING_TYPE_ATTRIBUTE, 'value' => 'vegan_flag'],
            'size' => ['type' => self::MAPPING_TYPE_ATTRIBUTE, 'value' => 'ot_size'],
            'price' => ['type' => self::MAPPING_TYPE_ATTRIBUTE, 'value' => 'price'],
            'currencyCode' => ['type' => self::MAPPING_TYPE_CALLBACK, 'value' => 'getCurrencyCode'],
            'productColor' => ['type' => self::MAPPING_TYPE_ATTRIBUTE, 'value' => 'color'],
            'productColorSearch' => ['type' => self::MAPPING_TYPE_ATTRIBUTE, 'value' => 'color_for_search'],
            'productSeason' => ['type' => self::MAPPING_TYPE_ATTRIBUTE, 'value' => 'season'],
            'modifiedDate' => ['type' => self::MAPPING_TYPE_CALLBACK, 'value' => 'getUpdatedAt'],
            'product_url' => ['type' => self::MAPPING_TYPE_ATTRIBUTE, 'value' => 'product_url'],
            'image' => ['type' => self::MAPPING_TYPE_ATTRIBUTE, 'value' => 'image'],
        ];
    }
}
