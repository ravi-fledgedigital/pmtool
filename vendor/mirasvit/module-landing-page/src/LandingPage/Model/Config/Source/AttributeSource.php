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
 * @package   mirasvit/module-landing-page
 * @version   1.1.0
 * @copyright Copyright (C) 2026 Mirasvit (https://mirasvit.com/)
 */


declare(strict_types=1);

namespace Mirasvit\LandingPage\Model\Config\Source;

use Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory;
use Magento\Framework\Data\OptionSourceInterface;

class AttributeSource implements OptionSourceInterface
{
    private $collectionFactory;

    public function __construct(CollectionFactory $collectionFactory)
    {
        $this->collectionFactory = $collectionFactory;
    }

    public function toOptionArray(): array
    {
        $result     = [];
        $attributes = $this->collectionFactory->create()->addFieldToFilter(
            ['is_filterable', 'is_filterable_in_search'],
            [[1, 2], 1]
        );

        foreach ($attributes as $attribute) {
            if (!$attribute->getDefaultFrontendLabel()) {
                continue;
            }

            if (count($attribute->getOptions()) == 0) {
                continue;
            }

            if (count($attribute->getOptions()) == 1) {
                $options = $attribute->getOptions();
                foreach ($options as $option) {
                    if (!$option->getValue()) {
                        continue 2;
                    }
                }
            }

            $result[] = [
                'label' => $attribute->getDefaultFrontendLabel(),
                'value' => $attribute->getAttributeId(),
            ];
        }

        return $result;
    }
}

