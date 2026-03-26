<?php
namespace OnitsukaTiger\Cms\Block\PLP;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Registry;

class ProductList extends Template
{
    private const XML_PATH_IS_ENABLED    = 'onitsukatiger_cms/general/enabled';
    private const XML_PATH_CATEGORY_IDS  = 'onitsukatiger_cms/general/apply_font_for_category';

    protected ScopeConfigInterface $scopeConfig;
    protected StoreManagerInterface $storeManager;
    protected Registry $registry;

    public function __construct(
        Context $context,
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager,
        Registry $registry,
        array $data = []
    ) {
        $this->scopeConfig   = $scopeConfig;
        $this->storeManager  = $storeManager;
        $this->registry      = $registry;
        parent::__construct($context, $data);
    }

    /**
     * Get current store ID
     */
    private function getCurrentStoreId(): int
    {
        return (int) $this->storeManager->getStore()->getId();
    }

    /**
     * Check if the module is enabled for current store.
     */
    public function isEnabled(): bool
    {
        return (bool) $this->scopeConfig->isSetFlag(
            self::XML_PATH_IS_ENABLED,
            ScopeInterface::SCOPE_STORE,
            $this->getCurrentStoreId()
        );
    }

    /**
     * Get comma-separated category IDs from config as an array for current store.
     *
     * @return int[] Array of category IDs
     */
    public function getCategoryIds(): array
    {
        $ids = $this->scopeConfig->getValue(
            self::XML_PATH_CATEGORY_IDS,
            ScopeInterface::SCOPE_STORE,
            $this->getCurrentStoreId()
        );

        if (empty($ids)) {
            return [];
        }

        return array_map('intval', array_map('trim', explode(',', $ids)));
    }

    /**
     * Determine whether to add custom class in body based on current category.
     */
    public function isAddNotoClassInBody(): bool
    {
        if (!$this->isEnabled()) {
            return false;
        }

        $category = $this->registry->registry('current_category');
        if ($category && $category->getId()) {
            return in_array((int) $category->getId(), $this->getCategoryIds(), true);
        }

        return false;
    }
}
