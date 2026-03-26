<?php
/**
 * Mirasvit
 *
 * This source file is subject to the Mirasvit Software License, which is available at https://mirasvit.com/license/.
 * Do not edit or add to this file if you wish to upgrade the to newer versions in the future.
 * If you wish to customize this module for your needs.
 * Please refer to http://www.magentocommerce.com for more information.
 *
 * @category  Mirasvit
 * @package   mirasvit/module-navigation
 * @version   2.9.34
 * @copyright Copyright (C) 2026 Mirasvit (https://mirasvit.com/)
 */


declare(strict_types=1);


namespace Mirasvit\LayeredNavigationLiveSearch\Plugin\LiveSearch;


use Magento\LiveSearchAdapter\Model\AttributeMetadata;
use Mirasvit\LayeredNavigation\Model\Config\ExtraFilterConfigProvider;


class ModifyAttributeMetadataPlugin
{
    private $extraFiltersCodes = [
        ExtraFilterConfigProvider::ON_SALE_FILTER,
        ExtraFilterConfigProvider::NEW_FILTER,
        ExtraFilterConfigProvider::RATING_FILTER,
        ExtraFilterConfigProvider::STOCK_FILTER
    ];

    public function afterGetAttributesMetadata(AttributeMetadata $subject, array $result, array $attributeCodes)
    {
        foreach ($attributeCodes as $attributeCode) {
            if (isset($result[$attributeCode]) || !in_array($attributeCode, $this->extraFiltersCodes)) {
                continue;
            }

            switch ($attributeCode) {
                case ExtraFilterConfigProvider::STOCK_FILTER:
                    $result[$attributeCode]['options']['admin'] = [
                        1 => 'yes',
                        2 => 'no',
                    ];

                    break;
                case ExtraFilterConfigProvider::RATING_FILTER:
                    $result[$attributeCode]['options']['admin'] = ['0','1','2','3','4'];

                    break;
                default:
                    $result[$attributeCode]['options']['admin'] = [0 => 'no',  1 => 'yes'];
            }

        }

        return $result;
    }
}
