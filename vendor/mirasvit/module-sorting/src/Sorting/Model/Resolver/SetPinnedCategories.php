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

namespace Mirasvit\Sorting\Model\Resolver;

use Exception;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;

class SetPinnedCategories extends AbstractPinnedMutation
{
    public function resolve(Field $field, $context, ResolveInfo $info, ?array $value = null, ?array $args = null): array
    {
        try {
            $this->validateAccess($context);

            if (!isset($args['input']['product_id'])) {
                throw new GraphQlInputException(__('Product ID is required.'));
            }

            if (!isset($args['input']['category_ids'])) {
                throw new GraphQlInputException(__('Category IDs are required.'));
            }

            $productId   = (int)$args['input']['product_id'];
            $categoryIds = array_map('intval', $args['input']['category_ids']);

            $this->pinnedProductService->saveCategoryIds($productId, $categoryIds);
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }

        return [
            'success'      => true,
            'message'      => __('Pinned categories updated successfully.'),
            'product_id'   => $productId,
            'category_ids' => $this->pinnedProductService->getCategoryIds($productId),
        ];
    }
}
