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

use Magento\Framework\Json\EncoderInterface;
use Magento\Framework\Registry;
use Mirasvit\Sorting\Service\PinnedProductService;

/**
 * Adds pinned products support (injects the pinned products hidden field, data, and JS initialization)
 * when the Mirasvit Merchandiser module replaces the standard category products grid.
 */
class AddPinnedProductsToMerchandiserPlugin
{
    private $pinnedProductService;

    private $registry;

    private $jsonEncoder;

    public function __construct(
        PinnedProductService $pinnedProductService,
        Registry             $registry,
        EncoderInterface     $jsonEncoder
    ) {
        $this->pinnedProductService = $pinnedProductService;
        $this->registry             = $registry;
        $this->jsonEncoder          = $jsonEncoder;
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterToHtml($subject, string $result): string
    {
        $category = $this->registry->registry('category');

        if (!$category || !$category->getId()) {
            return $result;
        }

        $categoryId       = (int)$category->getId();
        $pinnedProductIds = $this->pinnedProductService->getProductIds($categoryId);

        $pinnedProducts = [];
        foreach ($pinnedProductIds as $productId) {
            $pinnedProducts[$productId] = "0";
        }

        $pinnedProductsJson = $this->jsonEncoder->encode($pinnedProducts);

        $pinnedProductsHtml = <<<HTML
<input type="hidden" name="pinned_product_ids" id="pinned_product_ids" data-form-part="category_form" value=""/>
<script type="text/javascript">
    window.pinnedProductsData = {$pinnedProductsJson};
</script>
<script type="text/x-magento-init">
{
    "*": {
        "Mirasvit_Sorting/js/catalog/category/pinned-products": {}
    }
}
</script>
HTML;

        return $result . $pinnedProductsHtml;
    }
}
