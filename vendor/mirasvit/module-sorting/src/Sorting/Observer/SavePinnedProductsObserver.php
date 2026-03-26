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

namespace Mirasvit\Sorting\Observer;

use Magento\Catalog\Model\Product;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Mirasvit\Sorting\Service\PinnedProductService;
use Mirasvit\Sorting\Ui\Product\Form\Modifier\PinnedInCategories;

/**
 * Save pinned categories data when product is saved.
 * @see Product::_afterSave
 */
class SavePinnedProductsObserver implements ObserverInterface
{
    private $request;

    private $pinnedProductService;

    public function __construct(
        RequestInterface     $request,
        PinnedProductService $pinnedProductService
    ) {
        $this->request              = $request;
        $this->pinnedProductService = $pinnedProductService;
    }

    public function execute(Observer $observer): void
    {
        /** @var Product $product */
        $product       = $observer->getEvent()->getProduct();
        $productParams = $this->request->getParam('product');

        if (!is_array($productParams)) {
            return;
        }

        $value = $productParams[PinnedInCategories::FIELD_CODE] ?? [];

        if (is_array($value)) {
            $categoryIds = array_map('intval', array_filter($value));
            $this->pinnedProductService->saveCategoryIds((int)$product->getId(), $categoryIds);
        }
    }
}
