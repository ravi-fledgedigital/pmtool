<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Product Tabs for Magento 2
 */

namespace Amasty\CustomTabs\Plugin\Catalog\Block\Product\View;

use Amasty\CustomTabs\Api\Data\TabsInterface;
use Amasty\CustomTabs\Block\Product\View\ProductTab;
use Amasty\CustomTabs\Model\Source\Type;
use Amasty\CustomTabs\Model\Tabs\Loader\SaveHandler;
use Magento\Catalog\Block\Product\View\Description;
use Magento\Catalog\Block\Product\View\Details;
use Magento\Catalog\Model\Product;

class DetailsPlugin
{
    /**
     * @var \Amasty\CustomTabs\Model\ConfigProvider
     */
    private $configProvider;

    /**
     * @var \Magento\Framework\Registry
     */
    private $coreRegistry;

    /**
     * @var \Amasty\CustomTabs\Model\Tabs\ResourceModel\CollectionFactory
     */
    private $collectionFactory;

    /**
     * @var \Magento\Framework\View\LayoutInterface
     */
    private $layout;

    /**
     * @var \Amasty\CustomTabs\Model\Tabs\ResourceModel\RuleIndex
     */
    private $ruleIndex;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var Product
     */
    protected $product = null;

    /**
     * @var bool
     */
    protected $isGroupSortedFunctionCalled = false;

    /**
     * @var int
     */
    protected $tabCount = 0;

    public function __construct(
        \Amasty\CustomTabs\Model\ConfigProvider $configProvider,
        \Magento\Framework\Registry $coreRegistry,
        \Amasty\CustomTabs\Model\Tabs\ResourceModel\CollectionFactory $collectionFactory,
        \Magento\Framework\View\LayoutInterface $layout,
        \Amasty\CustomTabs\Model\Tabs\ResourceModel\RuleIndex $ruleIndex,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        $this->configProvider = $configProvider;
        $this->coreRegistry = $coreRegistry;
        $this->collectionFactory = $collectionFactory;
        $this->layout = $layout;
        $this->ruleIndex = $ruleIndex;
        $this->storeManager = $storeManager;
    }

    /**
     * @param $subject
     * @param string $groupName
     * @param string $callback
     *
     * @return array
     */
    public function beforeGetGroupSortedChildNames($subject, string $groupName, string $callback)
    {
        $this->isGroupSortedFunctionCalled = true;
        return [$groupName, $callback];
    }

    /**
     * @param Details $subject
     * @param array $childNamesSortOrder
     *
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function afterGetGroupSortedChildNames(
        $subject,
        $childNamesSortOrder
    ) {
        if ($this->isModuleEnabled()) {
            $childNamesSortOrder = $this->overrideTabs($childNamesSortOrder);
            $this->tabCount = count($childNamesSortOrder);
        }

        return $childNamesSortOrder;
    }

    /**
     * @param Description $subject
     * @param array $childNames
     *
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function afterGetGroupChildNames(
        $subject,
        $childNames
    ) {
        if ($this->isModuleEnabled() && !$this->isGroupSortedFunctionCalled) {
            $childNames = $this->overrideTabs($childNames);
            $this->tabCount = count($childNames);
        }

        return $childNames;
    }

    /**
     * @param array $childNamesSortOrder
     *
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function overrideTabs(array $childNamesSortOrder)
    {
        if ($this->isChangeDefaultTabsAllowed()) {
            $childNamesSortOrder = $this->getOnlyNotObservedTabs($childNamesSortOrder);
        }

        $childNamesSortOrder = $this->addCustomProductTabs($childNamesSortOrder);
        ksort($childNamesSortOrder, SORT_NUMERIC);

        return $childNamesSortOrder;
    }

    /**
     * @param array $childNamesSortOrder
     *
     * @return array
     */
    protected function getOnlyNotObservedTabs($childNamesSortOrder)
    {
        $existingTabs = $this->getExistingTabs();
        $childNamesSortOrder = array_filter($childNamesSortOrder, function ($value, $key) use ($existingTabs) {
            return !in_array($value, $existingTabs);
        }, ARRAY_FILTER_USE_BOTH);

        return $childNamesSortOrder;
    }

    /**
     * @return array
     */
    protected function getExistingTabs()
    {
        return $this->collectionFactory->create()
            ->getExistingTabs();
    }

    /**
     * @param array $childNamesSortOrder
     *
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function addCustomProductTabs($childNamesSortOrder)
    {
        $customTabs = $this->getCustomTabs();
        $delta = count($customTabs);
        /** @var TabsInterface $customTab */
        foreach ($customTabs as $customTab) {
            $sortOrder = $customTab->getSortOrder();
            if ($sortOrder === null) {
                $sortOrder = PHP_INT_MAX - $delta--;
            }

            while (isset($childNamesSortOrder[$sortOrder])) {
                $sortOrder++;
            }
            $alias = $this->createBlock($customTab);
            $childNamesSortOrder[$sortOrder] = $alias;
        }

        return $childNamesSortOrder;
    }

    /**
     * @return \Magento\Framework\DataObject[]
     */
    protected function getCustomTabs()
    {
        $collection = $this->collectionFactory->create()
            ->getCustomTabByParams(
                $this->getAffectedTypes(),
                $this->getTabIdsByProduct(),
                (int)$this->storeManager->getStore()->getId()
            );

        return $collection->getItems();
    }

    /**
     * @return array
     */
    protected function getAffectedTypes()
    {
        $types = [Type::CUSTOM];
        if ($this->isChangeDefaultTabsAllowed()) {
            $types = [Type::CUSTOM, Type::MAGENTO, Type::ANOTHER_MODULES];
        }

        return $types;
    }

    /**
     * @return array
     */
    protected function getTabIdsByProduct()
    {
        return $this->ruleIndex->getAppliedTabs(
            $this->getProduct()->getId(),
            $this->storeManager->getStore()->getId()
        );
    }

    /**
     * @param TabsInterface $customTab
     *
     * @return string
     */
    protected function createBlock(TabsInterface $customTab)
    {
        $nameInLayout = ProductTab::NAME_IN_LAYOUT . $customTab->getTabId();
        $alias = $nameInLayout;
        if ($customTab->getTabAnchor()) {
            $mainBlock = $this->layout->getBlock(SaveHandler::TABS_NAME_IN_LAYOUT);
            if ($mainBlock) {
                $mainBlock->unsetChild($customTab->getTabAnchor());
                $alias = $customTab->getTabAnchor();
            }
        }

        /** @var \Amasty\CustomTabs\Block\Product\View\ProductTab $block */
        $block = $this->layout->getBlock($nameInLayout);
        if (!$block) {
            $block = $this->layout->addBlock(
                ProductTab::class,
                $nameInLayout,
                SaveHandler::TABS_NAME_IN_LAYOUT,
                $alias
            );
        }

        $block->setTab($customTab);

        return $nameInLayout;
    }

    /**
     * @return Product
     */
    public function getProduct()
    {
        if (!$this->product) {
            $this->product = $this->coreRegistry->registry('product');
        }
        return $this->product;
    }

    /**
     * @return bool
     */
    protected function isModuleEnabled()
    {
        return $this->configProvider->isEnabled();
    }

    /**
     * @return bool
     */
    protected function isChangeDefaultTabsAllowed()
    {
        return $this->configProvider->isChangeDefaultTabsAllowed();
    }

    /**
     * @param Details $subject
     * @param string $html
     *
     * @return string
     */
    public function afterToHtml($subject, $html)
    {
        if ($this->tabCount
            && !$this->configProvider->isAccordionView()
            && trim((string)$html)
        ) {
            $html .= sprintf(
                '<style>
                    @media only screen and (min-width: 768px) {
                        [id^="tab-label-amcustomtabs_tabs"][data-role="collapsible"] {
                            max-width: %s;
                        }
                    }
                </style>',
                (100 / $this->tabCount) . '%'
            );
            $this->tabCount = 0;//prevent duplicating
        }

        if ($this->configProvider->isAccordionView()) {
            $additionalClasses = $this->configProvider->getIsOpenAllTabs()
                ? 'amtabs-accordion-view amtabs-open'
                : 'amtabs-accordion-view';
            $html = str_replace(
                'product data items',
                'product data items ' . $additionalClasses,
                $html
            );
        }

        return $html;
    }
}
