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

class SetPinnedProducts extends AbstractPinnedMutation
{
    public function resolve(Field $field, $context, ResolveInfo $info, ?array $value = null, ?array $args = null): array
    {
        try {
            $this->validateAccess($context);

            if (!isset($args['input']['category_id'])) {
                throw new GraphQlInputException(__('Category ID is required.'));
            }

            if (!isset($args['input']['product_ids'])) {
                throw new GraphQlInputException(__('Product IDs are required.'));
            }

            $categoryId = (int)$args['input']['category_id'];
            $productIds = array_map('intval', $args['input']['product_ids']);

            $this->pinnedProductService->saveProductIds($categoryId, $productIds);
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }

        return [
            'success'     => true,
            'message'     => __('Pinned products updated successfully.'),
            'category_id' => $categoryId,
            'product_ids' => $this->pinnedProductService->getProductIds($categoryId),
        ];
    }
}
