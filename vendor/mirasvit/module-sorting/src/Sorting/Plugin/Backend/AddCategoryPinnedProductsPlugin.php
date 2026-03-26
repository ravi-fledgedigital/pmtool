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

use Magento\Catalog\Block\Adminhtml\Category\AssignProducts;
use Magento\Framework\Json\EncoderInterface;
use Magento\Framework\Registry;
use Mirasvit\Sorting\Service\PinnedProductService;

/**
 * Add pinned products JSON data to AssignProducts block.
 *
 * @see AssignProducts::toHtml
 */
class AddCategoryPinnedProductsPlugin
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

    public function afterToHtml(AssignProducts $subject, string $result): string
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

        $scriptToAdd = <<<SCRIPT
<script type="text/javascript">
    window.pinnedProductsData = {$pinnedProductsJson};
</script>
SCRIPT;

        return $scriptToAdd . $result;
    }
}
