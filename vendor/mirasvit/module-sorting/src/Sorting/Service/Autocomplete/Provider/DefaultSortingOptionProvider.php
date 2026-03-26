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

namespace Mirasvit\Sorting\Service\Autocomplete\Provider;

use Magento\Framework\App\ObjectManager;

class DefaultSortingOptionProvider
{
    private const PRODUCT_LIST_SERVICE_CLASS = 'Mirasvit\SearchAutocomplete\Service\ProductListService';

    public function execute(): ?array
    {
        if (!class_exists(self::PRODUCT_LIST_SERVICE_CLASS)) {
            return null;
        }

        $obj                = ObjectManager::getInstance();
        $productListService = $obj->get(self::PRODUCT_LIST_SERVICE_CLASS);
        $defaultSorting     = $productListService->getDefaultOrder();
        $defaultSortingCode = $productListService->getDefaultOrderCode();

        return [
            'order'     => $defaultSorting,
            'direction' => 'desc',
            'name'      => ucfirst($defaultSortingCode),
            'code'      => $defaultSortingCode,
        ];
    }
}
