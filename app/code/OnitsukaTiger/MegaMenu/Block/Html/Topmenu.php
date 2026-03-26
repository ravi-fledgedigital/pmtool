<?php

namespace OnitsukaTiger\MegaMenu\Block\Html;

use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Framework\Data\Tree\Node;
use Magento\Framework\Data\Tree\Node\Collection;
use Magento\Framework\Data\Tree\NodeFactory;
use Magento\Framework\Data\TreeFactory;
use Magento\Framework\View\Element\Template;
use OnitsukaTiger\MegaMenu\Helper\Data;

class Topmenu extends \Magento\Theme\Block\Html\Topmenu
{

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Category\StateDependentCollectionFactory
     */
    protected $_categoryCollection;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var CategoryRepositoryInterface
     */
    protected $categoryRepository;

    /**
     * @var \Magento\Catalog\Helper\Output
     */
    protected $outputHelper;

    /**
     * @var \Magento\Framework\View\Design\ThemeInterface
     */
    protected $_design;
    /**
     * @var Data
     */
    protected $megaMenuHelper;

    /**
     * @param Template\Context $context
     * @param NodeFactory $nodeFactory
     * @param TreeFactory $treeFactory
     * @param \Magento\Catalog\Model\ResourceModel\Category\StateDependentCollectionFactory $categoryCollectionFactory
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Catalog\Helper\Output $outputHelper
     * @param \Magento\Framework\View\DesignInterface $theme
     * @param CategoryRepositoryInterface $categoryRepository
     * @param Data $megaMenuHelper
     * @param array $data
     */
    public function __construct(
        Template\Context                                                              $context,
        NodeFactory                                                                   $nodeFactory,
        TreeFactory                                                                   $treeFactory,
        \Magento\Catalog\Model\ResourceModel\Category\StateDependentCollectionFactory $categoryCollectionFactory,
        \Magento\Store\Model\StoreManagerInterface                                    $storeManager,
        \Magento\Catalog\Helper\Output                                                $outputHelper,
        \Magento\Framework\View\DesignInterface                                       $theme,
        CategoryRepositoryInterface                                                   $categoryRepository,
        Data                                                                          $megaMenuHelper,
        array                                                                         $data = []
    ) {
        parent::__construct($context, $nodeFactory, $treeFactory, $data);
        $this->_categoryCollection = $categoryCollectionFactory;
        $this->_storeManager = $storeManager;
        $this->outputHelper = $outputHelper;
        $this->_design = $theme->getDesignTheme();
        $this->categoryRepository = $categoryRepository;
        $this->megaMenuHelper = $megaMenuHelper;
    }

    /**
     * Recursively generates top menu html from data that is specified in $menuTree
     *
     * @param Node $menuTree
     * @param string $childrenWrapClass
     * @param int $limit
     * @param array $colBrakes
     * @return string
     */
    protected function _getHtml(
        \Magento\Framework\Data\Tree\Node $menuTree,
                                          $childrenWrapClass,
                                          $limit,
                                          $colBrakes = []
    ) {
        $html = '';

        $children = $menuTree->getChildren();
        $parentLevel = $menuTree->getLevel();
        $childLevel = $parentLevel === null ? 0 : $parentLevel + 1;
        $this->removeChildrenWithoutActiveParent($children, $childLevel);

        $counter = 1;
        $itemPosition = 1;
        $childrenCount = $children->count();

        $parentPositionClass = $menuTree->getPositionClass();
        $itemPositionClassPrefix = $parentPositionClass ? $parentPositionClass . '-' : 'nav-';
        $htmlBlock = '';

        $isEnabled = $this->megaMenuHelper->isModuleEnabled();
        $categoryIds = $this->megaMenuHelper->getCategoryIds();
        // @codingStandardsIgnoreStart
        if (strpos($this->_design->getThemePath(), 'onitsuka')) {
            $htmlBlock = $this->getLayout()->createBlock('Magento\Framework\View\Element\Template')->setTemplate('Magento_Search::form.mini.mobile.phtml')->toHtml();
        }
        // @codingStandardsIgnoreEnd
        foreach ($children as $child) {
            $child->setLevel($childLevel);
            $child->setIsFirst($counter == 1);
            $child->setIsLast($counter == $childrenCount);
            $child->setPositionClass($itemPositionClassPrefix . $counter);

            $outermostClassCode = '';
            $outermostClass = $menuTree->getOutermostClass();
            $category = $this->getCategory($child->getId());
            if (!$category->getShowAllItems()) {

                // start code for check category having no-link class
                $hasClassNoLink = false;
                if (!empty($category->getAdditionalClass())) {
                    $strArr = explode(' ', $category->getAdditionalClass());
                    // @codingStandardsIgnoreStart
                    if (!empty($strArr) && in_array("no-link", $strArr)) {
                        $hasClassNoLink = true;
                    }
                    // @codingStandardsIgnoreEnd
                }
                //  end code for check category having no-link class

                if ($childLevel == 0 && $outermostClass) {
                    if ($category->getIsMegaMenu()) {
                        $outermostClass .= " menu-thumbnail";
                    }
                    $outermostClassCode = ' class="' . $outermostClass . '" ';

                    // start code to add custom addtitional class
                    // @codingStandardsIgnoreStart
                    if (!empty($category->getAdditionalClass())) {
                        $outermostClassCode = ' class="' . $outermostClass . ' ' . $category->getAdditionalClass() . '" ';
                    }
                    // end code to add custom addtitional class
                    // @codingStandardsIgnoreEnd

                    $child->setClass($outermostClass);
                } elseif (!empty($category->getAdditionalClass())) {
                    $outermostClassCode = ' class="' . $category->getAdditionalClass() . '" ';
                }

                if (count($colBrakes) && $colBrakes[$counter]['colbrake']) {
                    $html .= '</ul></li><li class="column"><ul>';
                    // @codingStandardsIgnoreStart
                    if ($childLevel == 1) {
                        $html .= '<li class="search-box-mobile"> <span class="close-menu"></span>';
                        $html .= $htmlBlock;
                        $html .= '</li>';
                    }
                    // @codingStandardsIgnoreEnd
                }
                // @codingStandardsIgnoreStart
                if ($childLevel == 1 && $child->getIsFirst()) {
                    $html .= '<li class="search-box-mobile"> <span class="close-menu-sub"></span>';
                    $html .= '</li>';
                    $html .= $this->getLayout()
                        ->createBlock(\Magento\Cms\Block\Block::class)
                        ->setBlockId('gift_category_image')
                        ->toHtml();
                    $html .= $this->getLayout()
                        ->createBlock(\Magento\Cms\Block\Block::class)
                        ->setBlockId('premium_category_text')
                        ->toHtml();
                }
                $itemClass = $this->_getRenderedMenuItemAttributes($child);
                if ($childLevel == 1 && $isEnabled) {
                    $catId = $child->getId();
                    $trimmedCatId = preg_replace('/^category-node-/', '', $catId);
                    if (in_array($trimmedCatId, $categoryIds)) {
                        $position = strrpos($itemClass, '"');
                        $itemClass = substr_replace($itemClass, ' mobile-active', $position, 0);
                    }
                }
                // @codingStandardsIgnoreEnd
                $html .= '<li ' . $itemClass;
                if ($category->getIsMegaMenu()) {
                    $html .= 'megamenu-mobile="true"';
                    $html .= 'megamenu-parent="true"';
                    // @codingStandardsIgnoreStart
                    if ($category->getImage()) {
                        $html .= 'megamenu="true"';
                    }
                    // @codingStandardsIgnoreEnd
                }
                if ($category->getShowAllFirst()) {
                    $html .= 'megamenu-first="true"';
                }
                $html .= '>';
                $urlCategory = $child->getUrl();
                // @codingStandardsIgnoreStart
                if (strtolower($category->getName()) == 'brands') {
                    $urlCategory = 'javascript:void(0)';
                }

                // check if level 0 has 'no-link' class and remove category url srart
                if ($childLevel == 0 && $hasClassNoLink) {
                    $urlCategory = 'javascript:void(0)';
                }
                // check if level 0 has 'no-link' class and remove category url end
                // @codingStandardsIgnoreEnd
                $html .= '<a href="' . $urlCategory . '" ' . $outermostClassCode . '  alt="' .
                    $this->escapeHtml($category->getName()) . '" title="' .
                    $this->escapeHtml($category->getName()) . '">';
                if ($category->getIsMegaMenu()) {
                    $url = $this->getImageUrl($category->getImage());
                    $_imgHtml = '<img src="' . $url . '" alt="' . $this->escapeHtml($category->getName()) .
                        '" title="' . $this->escapeHtml($category->getName()) . '" class="image" /><br/>';
                    $html .= $category->getImage() ? $_imgHtml : '';
                    if($childLevel == 0) {
                        $html .= '<label>'.$this->escapeHtml($child->getName()).'</label>';
                    } else {
                        $html .= $this->escapeHtml($child->getName());
                    }
                } else {
                    if($childLevel == 0){
                        $html .= '<label>'.$this->escapeHtml($child->getName()).'</label>';
                    }else{
                        $html .= $this->escapeHtml($child->getName());
                    }
                }

                $html .= '</a>' . $this->_addSubMenu(
                        $child,
                        $childLevel,
                        $childrenWrapClass,
                        $limit
                    ) . '</li>';
                $itemPosition++;
                $counter++;
            }
        }

        if (count($colBrakes) && $limit) {
            $html = '<li class="column"><ul>' . $html . '</ul></li>';
        }

        return $html;
    }

    /**
     * Remove children from collection when the parent is not active
     *
     * @param Collection $children
     * @param int $childLevel
     * @return void
     */
    private function removeChildrenWithoutActiveParent(Collection $children, int $childLevel): void
    {
        /** @var Node $child */
        foreach ($children as $child) {
            if ($childLevel === 0 && $child->getData('is_parent_active') === false) {
                $children->delete($child);
            }
        }
    }

    /**
     * Get Category Data
     *
     * @param mmixed $categoryId
     * @return \Magento\Catalog\Api\Data\CategoryInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function getCategory($categoryId)
    {
        $storeId = $this->_storeManager->getStore()->getId();
        $categoryIdElements = explode('-', $categoryId);
        $category = $this->categoryRepository->get(end($categoryIdElements), $storeId);
        return $category;
    }

    /**
     * Get Image URL
     *
     * @param mixed $image
     * @return false|string
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getImageUrl($image)
    {
        $url = false;
        if ($image) {
            if (is_string($image)) {
                $store = $this->_storeManager->getStore();

                $isRelativeUrl = substr($image, 0, 1) === '/';

                $mediaBaseUrl = $store->getBaseUrl(
                    \Magento\Framework\UrlInterface::URL_TYPE_MEDIA
                );

                if ($isRelativeUrl) {
                    $url = $image;
                } else {
                    $url = $mediaBaseUrl
                        . ltrim(\Magento\Catalog\Model\Category\FileInfo::ENTITY_MEDIA_PATH, '/')
                        . '/'
                        . $image;
                }
            } else {
                throw new \Magento\Framework\Exception\LocalizedException(
                    __('Something went wrong while getting the image url.')
                );
            }
        }
        return $url;
    }

    /**
     * Returns array of menu item's classes
     *
     * @param Node $item
     * @return array
     */
    protected function _getMenuItemClasses(Node $item)
    {
        $classes = [
            'level' . $item->getLevel(),
            $item->getPositionClass(),
        ];
        /*$catId = $item->getId();
        $trimmedCatId = preg_replace('/^category-node-/', '', $catId);

        $isAllowedToAddClass = $this->megaMenuHelper->checkIsCategoryExists($trimmedCatId);

        // @codingStandardsIgnoreStart
        if ($item->getLevel() == 1 && $isAllowedToAddClass) {
            $classes[] = 'mobile-active';
        }
        // @codingStandardsIgnoreEnd*/

        if ($item->getIsCategory()) {
            $classes[] = 'category-item';
        }

        if ($item->getIsFirst()) {
            $classes[] = 'first';
        }

        if ($item->getIsActive()) {
            $classes[] = 'active';
        } elseif ($item->getHasActive()) {
            $classes[] = 'has-active';
        }

        if ($item->getIsLast()) {
            $classes[] = 'last';
        }

        if ($item->getClass()) {
            $classes[] = $item->getClass();
        }

        if ($item->hasChildren()) {
            $classes[] = 'parent';
        }

        return $classes;
    }
}