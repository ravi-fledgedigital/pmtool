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

namespace Mirasvit\Sorting\Plugin\Backend;

use Magento\Catalog\Block\Adminhtml\Category\Tab\Product;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Mirasvit\Sorting\Service\PinnedProductService;

/**
 * Joins pinned products table to collection for sorting support.
 * @see Product::setCollection()
 */
class AddPinToTopFieldToCollectionPlugin
{
    public function beforeSetCollection(Product $subject, ?Collection $collection): array
    {
        $categoryId = (int)$subject->getRequest()->getParam('id', 0);

        if (!$categoryId || !$collection) {
            return [$collection];
        }

        if ($collection->getFlag('mst_pin_to_top_joined')) {
            return [$collection];
        }

        $collection->joinField(
            'pin_to_top',
            PinnedProductService::TABLE_NAME,
            'product_id',
            'product_id=entity_id',
            '{{table}}.category_id = ' . $categoryId,
            'left'
        );

        $collection->setFlag('mst_pin_to_top_joined', true);

        return [$collection];
    }
}
