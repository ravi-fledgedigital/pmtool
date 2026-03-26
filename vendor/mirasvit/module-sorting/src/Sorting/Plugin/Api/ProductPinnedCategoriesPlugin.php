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

namespace Mirasvit\Sorting\Plugin\Api;

use Magento\Catalog\Api\Data\ProductExtensionFactory;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\Data\ProductSearchResultsInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Mirasvit\Sorting\Service\PinnedProductService;

/**
 * @see ProductRepositoryInterface::get()
 * @see ProductRepositoryInterface::getById()
 * @see ProductRepositoryInterface::getList()
 * @see ProductRepositoryInterface::save()
 */
class ProductPinnedCategoriesPlugin
{
    private $productExtensionFactory;

    private $pinnedProductService;

    public function __construct(
        ProductExtensionFactory $productExtensionFactory,
        PinnedProductService    $pinnedProductService
    ) {
        $this->productExtensionFactory = $productExtensionFactory;
        $this->pinnedProductService    = $pinnedProductService;
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGet(
        ProductRepositoryInterface $subject,
        ProductInterface           $product
    ): ProductInterface {
        return $this->addPinnedCategories($product);
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetById(
        ProductRepositoryInterface $subject,
        ProductInterface           $product
    ): ProductInterface {
        return $this->addPinnedCategories($product);
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetList(
        ProductRepositoryInterface    $subject,
        ProductSearchResultsInterface $searchResults
    ): ProductSearchResultsInterface {
        foreach ($searchResults->getItems() as $product) {
            $this->addPinnedCategories($product);
        }

        return $searchResults;
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterSave(
        ProductRepositoryInterface $subject,
        ProductInterface           $product,
        ProductInterface           $inputProduct
    ): ProductInterface {
        $inputExtensionAttributes = $inputProduct->getExtensionAttributes();

        if ($inputExtensionAttributes === null) {
            return $product;
        }

        $pinnedCategoryIds = $inputExtensionAttributes->getPinnedCategoryIds();
        if ($pinnedCategoryIds !== null) {
            $this->pinnedProductService->saveCategoryIds((int)$product->getId(), $pinnedCategoryIds);
            $this->setPinnedCategories($product, $pinnedCategoryIds);
        }

        return $product;
    }

    private function addPinnedCategories(ProductInterface $product): ProductInterface
    {
        $pinnedCategoryIds = $this->pinnedProductService->getCategoryIds((int)$product->getId());

        return $this->setPinnedCategories($product, $pinnedCategoryIds);
    }

    private function setPinnedCategories(ProductInterface $product, array $pinnedCategoryIds): ProductInterface
    {
        $extensionAttributes = $product->getExtensionAttributes();
        if ($extensionAttributes === null) {
            $extensionAttributes = $this->productExtensionFactory->create();
        }

        $extensionAttributes->setPinnedCategoryIds($pinnedCategoryIds);
        $product->setExtensionAttributes($extensionAttributes);

        return $product;
    }
}
