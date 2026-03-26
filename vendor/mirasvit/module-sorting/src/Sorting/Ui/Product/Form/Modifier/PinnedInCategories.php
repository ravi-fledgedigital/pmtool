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
 * @package   mirasvit/module-sorting
 * @version   1.4.5
 * @copyright Copyright (C) 2026 Mirasvit (https://mirasvit.com/)
 */


declare(strict_types=1);

namespace Mirasvit\Sorting\Ui\Product\Form\Modifier;

use Magento\Catalog\Model\Locator\LocatorInterface;
use Magento\Catalog\Ui\Component\Product\Form\Categories\Options;
use Magento\Catalog\Ui\DataProvider\Product\Form\Modifier\AbstractModifier;
use Magento\Ui\Component\Form\Field;
use Mirasvit\Sorting\Service\PinnedProductService;

/**
 * Data provider modifier for pinned categories field of product page.
 */
class PinnedInCategories extends AbstractModifier
{
    public const FIELD_CODE = 'pinned_category_ids';

    private $options;

    private $pinnedProductService;

    private $locator;

    public function __construct(
        Options              $options,
        PinnedProductService $pinnedProductService,
        LocatorInterface     $locator
    ) {
        $this->options              = $options;
        $this->pinnedProductService = $pinnedProductService;
        $this->locator              = $locator;
    }

    public function modifyData(array $data): array
    {
        $product = $this->locator->getProduct();

        if (!$product || !$product->getId()) {
            return $data;
        }

        $productId   = (int)$product->getId();
        $categoryIds = $this->pinnedProductService->getCategoryIds($productId);

        if (!isset($data[$productId])) {
            $data[$productId] = [];
        }

        if (!isset($data[$productId][self::DATA_SOURCE_DEFAULT])) {
            $data[$productId][self::DATA_SOURCE_DEFAULT] = [];
        }

        $data[$productId][self::DATA_SOURCE_DEFAULT][self::FIELD_CODE] = array_map('strval', $categoryIds);

        return $data;
    }

    public function modifyMeta(array $meta): array
    {
        return array_replace_recursive(
            $meta,
            [
                'product-details' => [
                    'children' => [
                        self::FIELD_CODE => [
                            'arguments' => [
                                'data' => [
                                    'config' => [
                                        'label'            => __('Pin to the top in categories'),
                                        'componentType'    => Field::NAME,
                                        'formElement'      => 'select',
                                        'component'        => 'Magento_Catalog/js/components/new-category',
                                        'elementTmpl'      => 'ui/grid/filters/elements/ui-select',
                                        'dataScope'        => self::FIELD_CODE,
                                        'filterOptions'    => true,
                                        'chipsEnabled'     => true,
                                        'disableLabel'     => true,
                                        'levelsVisibility' => 1,
                                        'multiple'         => true,
                                        'visible'          => true,
                                        'sortOrder'        => 85,
                                        'options'          => $this->options->toOptionArray(),
                                        'notice'           => __('If a product is pinned to the top of a category but isn’t actually assigned to that category, it will not appear in that category’s top section.'),
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ]
        );
    }
}
