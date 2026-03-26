<?php

namespace OnitsukaTiger\CategoryShopby\Helper;

use Amasty\Shopby\Model\Source\RenderCategoriesLevel;
use Magento\Store\Model\ScopeInterface;
use Amasty\Shopby\Model\Category\Attribute\Frontend\Image as ImageModel;

/**
 * Class Category
 * @package OnitsukaTiger\CategoryShopby\Helper
 */
class Category extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * @var \Amasty\ShopbyBase\Model\Category\Manager\Proxy
     */
    protected $categoryManager;

    /**
     * @var \Magento\Catalog\Api\CategoryRepositoryInterface
     */
    protected $categoryRepository;

    /**
     * Category constructor.
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Amasty\ShopbyBase\Model\Category\Manager $categoryManager
     * @param \Magento\Catalog\Api\CategoryRepositoryInterface $categoryRepository
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Amasty\ShopbyBase\Model\Category\Manager $categoryManager,
        \Magento\Catalog\Api\CategoryRepositoryInterface $categoryRepository
    ) {
        parent::__construct($context);
        $this->categoryManager = $categoryManager;
        $this->categoryRepository = $categoryRepository;
    }

    /**
     * @param $categoryId
     * @return \Magento\Catalog\Api\Data\CategoryInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getCategory($categoryId)
    {
        return $this->categoryRepository->get($categoryId, $this->categoryManager->getCurrentStoreId());
    }
}
