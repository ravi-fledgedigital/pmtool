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

use Exception;
use Magento\Catalog\Model\Category;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Mirasvit\Sorting\Service\PinnedProductService;
use Psr\Log\LoggerInterface;

/**
 * Save pinned products data when category is saved.
 * @see Category::save
 */
class SaveCategoryPinnedProductsObserver implements ObserverInterface
{
    private $request;

    private $pinnedProductService;

    private $jsonSerializer;

    private $logger;

    public function __construct(
        RequestInterface     $request,
        PinnedProductService $pinnedProductService,
        Json                 $jsonSerializer,
        LoggerInterface      $logger
    ) {
        $this->request              = $request;
        $this->pinnedProductService = $pinnedProductService;
        $this->jsonSerializer       = $jsonSerializer;
        $this->logger               = $logger;
    }

    public function execute(Observer $observer): void
    {
        /** @var Category $category */
        $category   = $observer->getEvent()->getCategory();
        $categoryId = (int)$category->getId();

        if (!$categoryId) {
            return;
        }

        $pinnedProducts = $this->request->getParam('pinned_product_ids');

        if (empty($pinnedProducts)) {
            return;
        }

        try {
            $pinnedProductsData = $this->jsonSerializer->unserialize($pinnedProducts);

            if (!is_array($pinnedProductsData)) {
                return;
            }

            $currentPinnedProducts = $this->pinnedProductService->getProductIds($categoryId);
            $newPinnedProductIds   = array_keys($pinnedProductsData);
            $toUnpin               = array_diff($currentPinnedProducts, $newPinnedProductIds);
            $toPin                 = array_diff($newPinnedProductIds, $currentPinnedProducts);

            foreach ($toUnpin as $productId) {
                $categoryIds = $this->pinnedProductService->getCategoryIds((int)$productId);
                $categoryIds = array_diff($categoryIds, [$categoryId]);
                $this->pinnedProductService->saveCategoryIds((int)$productId, $categoryIds);
            }

            foreach ($toPin as $productId) {
                $categoryIds = $this->pinnedProductService->getCategoryIds((int)$productId);
                if (!in_array($categoryId, $categoryIds, true)) {
                    $categoryIds[] = $categoryId;
                    $this->pinnedProductService->saveCategoryIds((int)$productId, $categoryIds);
                }
            }
        } catch (Exception $e) {
            $this->logger->error(
                'Error saving pinned products for category: ' . $e->getMessage(),
                ['category_id' => $categoryId]
            );
        }
    }
}
