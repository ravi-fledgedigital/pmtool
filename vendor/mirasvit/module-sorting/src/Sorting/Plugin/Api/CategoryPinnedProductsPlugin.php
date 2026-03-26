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

use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Api\Data\CategoryExtensionFactory;
use Magento\Catalog\Api\Data\CategoryInterface;
use Mirasvit\Sorting\Service\PinnedProductService;

/**
 * @see CategoryRepositoryInterface::get()
 * @see CategoryRepositoryInterface::save()
 */
class CategoryPinnedProductsPlugin
{
    private $categoryExtensionFactory;

    private $pinnedProductService;

    public function __construct(
        CategoryExtensionFactory $categoryExtensionFactory,
        PinnedProductService     $pinnedProductService
    ) {
        $this->categoryExtensionFactory = $categoryExtensionFactory;
        $this->pinnedProductService     = $pinnedProductService;
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGet(
        CategoryRepositoryInterface $subject,
        CategoryInterface           $category
    ): CategoryInterface {
        $pinnedProductIds = $this->pinnedProductService->getProductIds((int)$category->getId());

        return $this->setPinnedProducts($category, $pinnedProductIds);
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterSave(
        CategoryRepositoryInterface $subject,
        CategoryInterface           $category,
        CategoryInterface           $inputCategory
    ): CategoryInterface {
        $inputExtensionAttributes = $inputCategory->getExtensionAttributes();

        if ($inputExtensionAttributes === null) {
            return $category;
        }

        $pinnedProductIds = $inputExtensionAttributes->getPinnedProductIds();
        if ($pinnedProductIds !== null) {
            $this->pinnedProductService->saveProductIds((int)$category->getId(), $pinnedProductIds);

            $this->setPinnedProducts($category, $pinnedProductIds);
        }

        return $category;
    }

    private function setPinnedProducts(CategoryInterface $category, array $pinnedProductIds): CategoryInterface
    {
        $extensionAttributes = $category->getExtensionAttributes();
        if ($extensionAttributes === null) {
            $extensionAttributes = $this->categoryExtensionFactory->create();
        }

        $extensionAttributes->setPinnedProductIds($pinnedProductIds);
        $category->setExtensionAttributes($extensionAttributes);

        return $category;
    }
}
