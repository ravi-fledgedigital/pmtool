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

namespace Mirasvit\Brand\Ui\BrandPage\Form\Source;

use Magento\Framework\Data\OptionSourceInterface;
use Mirasvit\Brand\Model\Config\BrandPageConfig;

class SeoDescriptionPosition implements OptionSourceInterface
{
    public function toOptionArray(): array
    {
        $data = [
            BrandPageConfig::FROM_DEFAULT_POSITION     => BrandPageConfig::FROM_DEFAULT,
            BrandPageConfig::DISABLED_POSITION         => BrandPageConfig::DISABLED_SEO_POSITION,
            BrandPageConfig::BOTTOM_SEO_POSITION       => BrandPageConfig::BOTTOM_OF_THE_PAGE,
            BrandPageConfig::PRODUCT_LIST_SEO_POSITION => BrandPageConfig::UNDER_PRODUCT_LIST,
        ];

        $options = [];
        foreach ($data as $key => $value) {
            $options[] = [
                'label' => $value,
                'value' => $key,
            ];
        }

        return $options;
    }
}
