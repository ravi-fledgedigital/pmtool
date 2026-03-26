<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Product Tabs for Magento 2
 */

namespace Amasty\CustomTabs\Block\Product\View;

use Amasty\CustomTabs\Api\Data\TabsInterface;
use Amasty\CustomTabs\Model\RotationFactory;
use Amasty\CustomTabs\Model\Tabs\Loader\SaveHandler;
use Magento\Catalog\Block\Product\ProductList\Crosssell;
use Magento\Catalog\Block\Product\ProductList\Item\AddTo\Compare;
use Magento\Catalog\Block\Product\ProductList\Item\Container;
use Magento\Catalog\Block\Product\ProductList\Related;
use Magento\Catalog\Block\Product\ProductList\Upsell;
use Magento\Catalog\Model\Product;
use Magento\Catalog\ViewModel\Product\Listing\PreparePostData;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\Module\Manager;
use Magento\Framework\Phrase;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\BlockInterface;
use Magento\Framework\View\Element\Template;
use Magento\TargetRule\Block\Catalog\Product\ProductList\Related as ProductListRelated;
use Magento\TargetRule\Block\Catalog\Product\ProductList\Upsell as ProductListUpsell;
use Magento\TargetRule\Block\Checkout\Cart\Crosssell as CartCrosssell;
use Magento\Widget\Model\Template\Filter;

class ProductTab extends Template implements IdentityInterface
{
    public const NAME_IN_LAYOUT = 'amcustomtabs_tabs_';
    public const REVIEW_TAB_SELECTOR = '#tab-label-reviews';

    public const PRODUCTS_BLOCKS = [
        'related' => [
            'catalog' => Related::class,
            'rule' => ProductListRelated::class // @phpstan-ignore class.notFound
        ],
        'upsell' => [
            'catalog' => Upsell::class,
            'rule' => ProductListUpsell::class // @phpstan-ignore class.notFound
        ],
        'crosssell' => [
            'catalog' => Crosssell::class,
            'rule' => CartCrosssell::class // @phpstan-ignore class.notFound
        ]
    ];

    /**
     * @var Product
     */
    protected $product = null;

    /**
     * @var TabsInterface
     */
    protected $tab;

    /**
     * @var Filter
     */
    private $filter;

    /**
     * @var Registry
     */
    private $registry;

    /**
     * @var Manager
     */
    private $moduleManager;

    /**
     * @var RotationFactory
     */
    private $rotationFactory;

    /**
     * @var PriceCurrencyInterface
     */
    private $priceCurrency;

    public function __construct(
        Manager $moduleManager,
        PriceCurrencyInterface $priceCurrency,
        Template\Context $context,
        Filter $filter,
        Registry $registry,
        RotationFactory $rotationFactory,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->filter = $filter;
        $this->registry = $registry;
        $this->priceCurrency = $priceCurrency;
        $this->moduleManager = $moduleManager;
        $this->rotationFactory = $rotationFactory;
    }

    /**
     * @return string
     */
    public function toHtml(): string
    {
        $html = '';
        if ($this->getTab()) {
            $html = $this->getTab()->getContent();
            if ($html) {
                $html = $this->parseVariables($html);
                $html = $this->parseWysiwyg($html);
            }

            $html = trim($this->addProductBlocks($html));
        }

        $html = $html == '<p></p>' || $html == '<p>N/A</p>'
            ? ''
            : $html;

        if ($html) {
            $this->setData('title', $this->getTabTitle());
            $html = sprintf(
                '<div class="am-custom-tab am-custom-tab-%s">%s</div>',
                $this->getTab()->getTabId(),
                $html
            );

            $html = $this->fixReviewTabBug($html);
        }

        return $html;
    }

    /**
     * @param string $html
     *
     * @return string
     */
    protected function fixReviewTabBug(string $html): string
    {
        if (strpos($html, self::REVIEW_TAB_SELECTOR) !== false) {
            $html = str_replace(
                self::REVIEW_TAB_SELECTOR,
                self::REVIEW_TAB_SELECTOR . ', #tab-label-' . self::NAME_IN_LAYOUT . $this->getTab()->getTabId(),
                $html
            );
        }

        return $html;
    }

    /**
     * @return string
     */
    public function getTabTitle(): string
    {
        $title = '';
        if ($this->getTab()) {
            $title = $this->getTab()->getTabTitle();
            $title = $this->escapeHtml($title, ['span', 'b', 'p']);
            if (strpos($title, SaveHandler::DEFAULT_TITLE_VARIABLE) !== false) {
                $parentTitle = $this->getTitleFromParent();
                $title = str_replace(SaveHandler::DEFAULT_TITLE_VARIABLE, (string)$parentTitle, $title);
            }
        }

        return $title;
    }

    /**
     * @return string
     */
    protected function getTitleFromParent()
    {
        $title = '';
        $nameInLayout = $this->getTab()->getNameInLayout();
        $block = $this->_layout->getBlock($nameInLayout);
        if ($block) {
            $title = $block->getData('title');
        }

        return $title;
    }

    /**
     * @param string $content
     * @return string
     */
    protected function addProductBlocks(string $content): string
    {
        $types = [TabsInterface::RELATED_ENABLED, TabsInterface::UPSELL_ENABLED, TabsInterface::CROSSSELL_ENABLED];
        foreach ($types as $type) {
            if (!$this->getTab()->getData($type)) {
                continue;
            }

            $html = null;
            switch ($type) {
                case TabsInterface::RELATED_ENABLED:
                    $html = $this->getHtml($this->getProductBlock('related'));
                    $html = preg_replace(
                        '@(data-mage-init=\'{")(relatedProducts)(.*?)(\')@s',
                        sprintf(
                            '$1Amasty_CustomTabs/js/related-products"
                            :{"relatedCheckbox":".am-custom-tab-%1$s .am-tab-related.checkbox",
                            "selectAllLink":"[data-role=\"select-all\"], [role=\"select-all\"]"}}$4',
                            $this->getTab()->getTabId()
                        ),
                        $html
                    );
                    break;
                case TabsInterface::UPSELL_ENABLED:
                    $html = $this->getHtml($this->getProductBlock('upsell'));
                    break;
                case TabsInterface::CROSSSELL_ENABLED:
                    $html = $this->getHtml($this->getProductBlock('crosssell'));
                    break;
            }

            if ($html) {
                $content .= $html;
            }
        }

        return $content;
    }

    /**
     * @param string $type
     *
     * @return BlockInterface|null
     */
    public function getProductBlock(string $type): ?BlockInterface
    {
        $productBlock = null;
        $blocks = self::PRODUCTS_BLOCKS;
        $tabId = $this->getTab()->getTabId();
        if (isset($blocks[$type])) {
            if ($this->moduleManager->isEnabled('Magento_TargetRule')) {
                $productBlock = $this->_layout->createBlock(
                    $blocks[$type]['rule'],
                    'amcustomtabs.tabs.catalog.product.' . $type . $tabId,
                    [
                        'data' => [
                            'type'     => $type . '-rule',
                            'template' => 'Magento_Catalog::product/list/items.phtml',
                            'rotation' => $this->rotationFactory->get()
                        ]
                    ]
                );
            } else {
                $productBlock = $this->_layout->createBlock(
                    $blocks[$type]['catalog'],
                    'amcustomtabs.tabs.catalog.product.' . $type . $tabId,
                    [
                        'data' => [
                            'type'     => $type,
                            'template' => 'Magento_Catalog::product/list/items.phtml'
                        ]
                    ]
                );
            }

            $addToName = 'amcustomtabs.tabs.' . $type . '.product.addto';
            $addTo = $this->_layout->getBlock($addToName);
            if (!$addTo) {
                $addTo = $this->_layout->createBlock(
                    Container::class,
                    $addToName
                );
            }

            $compareName = 'amcustomtabs.tabs.' . $type . '.product.addto.compare';
            $compare = $this->_layout->getBlock($compareName);
            if (!$compare) {
                $compare = $this->_layout->createBlock(
                    Compare::class,
                    $compareName,
                    [
                        'data' => [
                            'template' => 'Magento_Catalog::product/list/addto/compare.phtml'
                        ]
                    ]
                );
            }

            $addTo->setChild('compare', $compare);
            $productBlock->setChild('addto', $addTo);
            $productBlock->setViewModel($this->getViewModel());
            $this->_layout->getBlock(SaveHandler::TABS_NAME_IN_LAYOUT)
                ->setChild('', $productBlock);
        }

        return $productBlock;
    }

    /**
     * @param BlockInterface|null $block
     *
     * @return string
     */
    protected function getHtml(?BlockInterface $block)
    {
        return $block ? $block->toHtml() : '';
    }

    /**
     * @param string $content
     * @return string
     */
    protected function parseWysiwyg(string $content): string
    {
        return $this->filter->filter($content);
    }

    /**
     * @param string $content
     *
     * @return string
     */
    private function parseVariables(string $content): string
    {
        if (strpos($content, SaveHandler::DEFAULT_CONTENT_VARIABLE) !== false) {
            $default = '';
            $nameInLayout = $this->getTab()->getNameInLayout();
            $block = $this->_layout->getBlock($nameInLayout);
            if ($block) {
                $default = $block->toHtml();
            }

            $content = str_replace(SaveHandler::DEFAULT_CONTENT_VARIABLE, $default, $content);
        }

        preg_match_all('@\{{(.+?) code="(.+?)"\}}@', $content, $matches);
        if (isset($matches[1]) && !empty($matches[1])) {
            foreach ($matches[1] as $key => $match) {
                $result = '';
                switch ($match) {
                    case 'amcustomtabs_attribute':
                        if ($this->getProduct() && isset($matches[2][$key])) {
                            $result = $this->getAttributeValue($this->getProduct(), $matches[2][$key]);
                        }
                        break;
                }

                $content = str_replace(
                    sprintf('{{%s code="%s"}}', $match, $matches[2][$key]),
                    (string)$result,
                    $content
                );
            }
        }

        return $content;
    }

    /**
     * @param \Magento\Catalog\Model\Product $product
     * @param string $attributeCode
     *
     * @return \Magento\Framework\Phrase|string
     */
    private function getAttributeValue($product, $attributeCode)
    {
        return $this->getValue($attributeCode, $product);
    }

    /**
     * @param string $attributeCode
     * @param $product
     * @return Phrase|string
     */
    public function getValue($attributeCode, $product)
    {
        $attribute = $product->getResource()->getAttribute($attributeCode);
        $value = $attribute ? $attribute->getFrontend()->getValue($product) : __('N/A')->render();

        return $value;
    }

    /**
     * @return TabsInterface
     */
    public function getTab(): TabsInterface
    {
        return $this->tab;
    }

    /**
     * @param TabsInterface $tab
     */
    public function setTab(TabsInterface $tab): void
    {
        $this->tab = $tab;
    }

    /**
     * @return Product
     */
    public function getProduct(): Product
    {
        if (!$this->product) {
            $this->product = $this->registry->registry('product');
        }
        return $this->product;
    }

    /**
     * @return string[]
     */
    public function getIdentities(): array
    {
        return $this->getTab() ? $this->getTab()->getIdentities() : [];
    }

    /**
     * Object manager for compatibility with old version
     *
     * @return PreparePostData|null
     */
    protected function getViewModel(): ?PreparePostData
    {
        $model = null;

        // @codingStandardsIgnoreLine
        $modelClass = '\Magento\Catalog\ViewModel\Product\Listing\PreparePostData';
        if (class_exists($modelClass)) {
            $model = ObjectManager::getInstance()
                ->create($modelClass);
        }

        return $model;
    }
}
