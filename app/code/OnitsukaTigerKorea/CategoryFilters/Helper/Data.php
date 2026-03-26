<?php

namespace OnitsukaTigerKorea\CategoryFilters\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;

/**
 * Class Create Data
 */
class Data extends AbstractHelper
{
    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var array
     */
    public $productIdsArr;

    /**
     * @var _categoryFactory
     */
    protected $_categoryFactory;

    /**
     * @var \Magento\Framework\Registry $registry
     */
    protected $_registry;

    /**
     * @var \OnitsukaTigerKorea\CategoryFilters\Model\CategoryFiltersFactory $categoryFiltersFactory
     */
    protected $categoryFiltersFactory;

    /**
     * @var \OnitsukaTigerKorea\CategoryFilters\Model\RelationCategoryFiltersFactory $categoryFiltersFactory
     */
    protected $categoryFiltersRelationFactory;

    /**
     * @param Context $context
     * @param ScopeConfigInterface $scopeConfig
     * @param \OnitsukaTigerKorea\CategoryFilters\Model\CategoryFiltersFactory $categoryFiltersFactory
     * @param ..\Model\RelationCategoryFiltersFactory $categoryFiltersRelationFactory
     * @param \Magento\Catalog\Model\CategoryFactory $categoryFactory
     * @param \Magento\Framework\Registry $registry
     */
    public function __construct(
        Context $context,
        ScopeConfigInterface $scopeConfig,
        \OnitsukaTigerKorea\CategoryFilters\Model\CategoryFiltersFactory $categoryFiltersFactory,
        \OnitsukaTigerKorea\CategoryFilters\Model\RelationCategoryFiltersFactory $categoryFiltersRelationFactory,
        \Magento\Catalog\Model\CategoryFactory $categoryFactory,
        \Magento\Framework\Registry $registry
    ) {
        parent::__construct($context);
        $this->scopeConfig = $scopeConfig;
        $this->categoryFiltersFactory = $categoryFiltersFactory;
        $this->categoryFiltersRelationFactory = $categoryFiltersRelationFactory;
        $this->_categoryFactory = $categoryFactory;
        $this->_registry = $registry;
    }

    /**
     * Check Is Module Enabled
     *
     * @return bool
     */
    public function isModuleEnabled()
    {
        $isEnabled = $this->getConfigValue(
            "catalog/them_customize/category_filters"
        );
        return (bool) $isEnabled;
    }

    /**
     * Get Category tree id config
     *
     * @return int
     */
    public function getCategoryTreeId()
    {
        $getCategoryTreeId = $this->getConfigValue(
            "catalog/them_customize/category_tree_id"
        );
        return (int) $getCategoryTreeId;
    }

    /**
     * Get config value
     *
     * @param string $path
     * @return mixed
     */
    public function getConfigValue($path)
    {
        return $this->scopeConfig->getValue(
            $path,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get Current category details
     *
     * @return bool
     */
    public function getCurrentCategoryDetails()
    {
        $category = $this->_registry->registry("current_category");
        if ($category && $category->getId()) {
            $collection = $this->categoryFiltersRelationFactory
                ->create()
                ->getCollection();
            $collection->addFieldToFilter("parent_category_id", [
                "eq" => $category->getId(),
            ]);

            if ($collection && $collection->getSize() > 0) {
                return $collection;
            }
        }

        return false;
    }

    public function getCurrentCategory()
    {
        return $this->_registry->registry("current_category");
    }
    /**
     * Get Current category url by id
     *
     * @param int $categoryId
     * @return bool
     */
    public function getCategoryUrlById($categoryId)
    {
        $category = $this->_categoryFactory->create()->load($categoryId);

        if ($category && $category->getId()) {
            return $category->getUrl();
        }

        return "";
    }
}
