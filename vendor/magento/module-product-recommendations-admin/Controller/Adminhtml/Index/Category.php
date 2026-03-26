<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ProductRecommendationsAdmin\Controller\Adminhtml\Index;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Catalog\Model\ResourceModel\Category\Collection as CategoryCollection;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\Json as ResultJson;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Controller responsible for getting all category data for exclusions
 */
class Category extends Action implements HttpPostActionInterface
{
    /**
     * @see _isAllowed()
     */
    public const ADMIN_RESOURCE = 'Magento_ProductRecommendationsAdmin::product_recommendations';

    /**
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param CollectionFactory $categoryFactory
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        private Context $context,
        private readonly JsonFactory       $resultJsonFactory,
        private readonly CollectionFactory $categoryFactory,
        private readonly StoreManagerInterface $storeManager
    ) {
        parent::__construct($context);
    }

    /**
     * Execute category controller call
     *
     * {@inheritDoc}
     *
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function execute(): ResultJson
    {
        $jsonResult = $this->resultJsonFactory->create();
        $storeViewCode = $this->getRequest()->getParam('storeViewCode', '');
        $result = $this->getCategories($storeViewCode);
        return $jsonResult->setData($result);
    }

    /**
     * Get Categories by store view code
     *
     * @param string|null $storeViewCode
     * @return array
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getCategories(?string $storeViewCode): array
    {
        $storeViewId = $storeViewCode ? $this->getStoreViewIdFromCode($storeViewCode) : null;

        /** @var CategoryCollection $items */
        $items = $this->categoryFactory->create();
        $items->addAttributeToSelect(['name', 'url_key', 'url_path']);
        $items->setStoreId($storeViewId);
        if ($storeViewId) {
            $rootId = \Magento\Catalog\Model\Category::TREE_ROOT_ID;
            $storeRootId = $this->getRootCategoryId($storeViewId);
            $items->addPathsFilter("{$rootId}/{$storeRootId}/");
        }

        $categories = [];
        foreach ($items as $category) {
            $urlKey = $category->getUrlKey();
            if ($urlKey) {
                $categories[] = [
                    'name' => $category->getName(),
                    'urlKey' => $urlKey,
                    'urlPath' => $category->getUrlPath()
                ];
            }
        }
        return $categories;
    }

    /**
     * Get the store view id from the store view code
     *
     * @param string $storeViewCode
     * @return int
     * @throws NoSuchEntityException
     */
    private function getStoreViewIdFromCode(string $storeViewCode): int
    {
        return (int) $this->storeManager->getStore($storeViewCode)->getId();
    }

    /**
     * Returns the root category ID for the store view
     *
     * @param int $storeViewId
     * @return int
     * @throws NoSuchEntityException
     */
    private function getRootCategoryId(int $storeViewId): int
    {
        return (int) $this->storeManager->getStore($storeViewId)->getRootCategoryId();
    }
}
