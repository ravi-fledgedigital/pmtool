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
 * @package   mirasvit/module-cataloglabel
 * @version   2.5.7
 * @copyright Copyright (C) 2026 Mirasvit (https://mirasvit.com/)
 */


declare(strict_types=1);


namespace Mirasvit\CatalogLabel\Block\Product;


use Magento\Catalog\Api\Data\CategoryInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Store\Api\Data\StoreInterface;
use Mirasvit\CatalogLabel\Api\Data\LabelInterface;
use Magento\CatalogInventory\Model\Stock\ItemFactory;
use Mirasvit\CatalogLabel\Api\Data\PlaceholderInterface;
use Mirasvit\CatalogLabel\Helper\Data as LabelDataHelper;
use Mirasvit\CatalogLabel\Helper\ProductData as ProductDataHelper;
use Mirasvit\CatalogLabel\Model\ResourceModel\Label\CollectionFactory;
use Mirasvit\CatalogLabel\Model\ConfigProvider;
use Mirasvit\CatalogLabel\Repository\PlaceholderRepository;
use Mirasvit\Core\Helper\ParseVariables;
use Magento\Framework\Registry;
use Magento\Framework\Pricing\Helper\Data as PricingDataHelper;
use Magento\Customer\Model\Session;
use Magento\Framework\View\Element\Template\Context;
use Mirasvit\CatalogLabel\Model\Label\Display;
use Magento\Framework\DataObject;
use Magento\Framework\View\Element\Template;
use Mirasvit\CatalogLabel\Model\ResourceModel\Label\Collection;
use Mirasvit\CatalogLabel\Model\ResourceModel\Label\Display\Collection as DisplayCollection;
use Mirasvit\CatalogLabel\Model\ResourceModel\Label\Display\CollectionFactory as DisplayCollectionFactory;


/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Label extends Template
{
    const DISCOUNT_PERCENT  = 'percent';
    const DISCOUNT_ABSOLUTE = 'absolute';

    private $labels = [];

    private $placeholders = [];

    private $stockItemFactory;

    private $placeholderRepository;

    private $labelCollectionFactory;

    private $config;

    private $mstcoreParsevariables;

    private $registry;

    private $pricingHelper;

    private $productDataHelper;

    private $context;

    private $customerSession;

    private $helperData;

    private $displayCollectionFactory;

    /**
     * @var LabelInterface[]
     */
    protected $labelObjectsArray;

    /**
     * @var array
     */
    protected $labelPosition
        = [
            'TL'    => 0,
            'TR'    => 0,
            'TC'    => 0,
            'ML'    => 0,
            'MR'    => 0,
            'MC'    => 0,
            'BL'    => 0,
            'BR'    => 0,
            'BC'    => 0,
            'EMPTY' => 0,
        ];

    /**
     * @var bool
     */
    protected $multipleImages = false;

    /**
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)                                                            $data
     */
    public function __construct(
        DisplayCollectionFactory $displayCollectionFactory,
        ItemFactory $stockItemFactory,
        PlaceholderRepository $placeholderRepository,
        CollectionFactory $labelCollectionFactory,
        ConfigProvider $config,
        LabelDataHelper $helperData,
        ParseVariables $mstcoreParsevariables,
        Registry $registry,
        PricingDataHelper $pricingHelper,
        Session $customerSession,
        ProductDataHelper $productDataHelper,
        Context $context,
        array $data = []
    ) {
        $this->displayCollectionFactory = $displayCollectionFactory;
        $this->stockItemFactory         = $stockItemFactory;
        $this->placeholderRepository    = $placeholderRepository;
        $this->labelCollectionFactory   = $labelCollectionFactory;
        $this->config                   = $config;
        $this->mstcoreParsevariables    = $mstcoreParsevariables;
        $this->registry                 = $registry;
        $this->pricingHelper            = $pricingHelper;
        $this->context                  = $context;
        $this->customerSession          = $customerSession;
        $this->helperData               = $helperData;
        $this->productDataHelper        = $productDataHelper;

        parent::__construct($context, $data);
    }

    /**
     * @param DisplayCollection|Display[] $displays
     */
    public function setLabelObjectsArray($displays): void
    {
        $labelPosition = [
            'TL'    => 0,
            'TR'    => 0,
            'TC'    => 0,
            'ML'    => 0,
            'MR'    => 0,
            'MC'    => 0,
            'BL'    => 0,
            'BR'    => 0,
            'BC'    => 0,
            'EMPTY' => 0,
        ];

        $this->labelObjectsArray = [
            'TL'    => new \Magento\Framework\DataObject(),
            'TR'    => new \Magento\Framework\DataObject(),
            'TC'    => new \Magento\Framework\DataObject(),
            'ML'    => new \Magento\Framework\DataObject(),
            'MR'    => new \Magento\Framework\DataObject(),
            'MC'    => new \Magento\Framework\DataObject(),
            'BL'    => new \Magento\Framework\DataObject(),
            'BR'    => new \Magento\Framework\DataObject(),
            'BC'    => new \Magento\Framework\DataObject(),
            'EMPTY' => new \Magento\Framework\DataObject(),
        ];

        foreach ($displays as $display) {
            /** @var Display $display */
            if ($image = $display->getImage()) {
                $h = 50;
                $w = 50;

                if (file_exists($this->config->getBaseMediaPath() . $image)) {
                    list($w, $h) = getimagesize($this->config->getBaseMediaPath() . $image);
                }

                $this->labelObjectsArray[$display->getPosition()]
                    ->setData(
                        $labelPosition[$display->getPosition()],
                        new DataObject([
                            'image'  => $display->getImage(),
                            'width'  => $w,
                            'height' => $h,
                        ])
                    );
                $labelPosition[$display->getPosition()] += 1;
            }
        }
    }

    public function setLabelPositionCount(Display $display): void
    {
        $this->labelPosition[$display->getPosition()] += 1;
    }

    public function getLabelPositionCount(Display $display): int
    {
        return (int)$this->labelPosition[$display->getPosition()];
    }

    /**
     * @param \Magento\Catalog\Model\Product $product
     *
     * @return $this
     */
    public function setProduct(ProductInterface $product): self
    {
        parent::setProduct($product);

        if (count($product->getMediaGalleryImages()) > 1) {
            $this->multipleImages = true;
        }

        $stockItem = $this->stockItemFactory->create()->setProduct($product);

        if ($stockItem) {
            $this->setQty(intval($stockItem->getQty()));
            $this->setRf($product->getPrice() - $product->getFinalPrice());

            if ($product->getPrice() > 0) {
                $this->setRfc(ceil($product->getPrice() - $product->getFinalPrice()));
                $this->setRfp(intval(($product->getPrice() - $product->getFinalPrice()) / $product->getPrice() * 100));
            }
        }

        return $this;
    }

    /**
     * @return DisplayCollection|Display[]
     */
    public function getDisplays()
    {
        $product = $this->getProduct();
        $key     = 'mst_label_displays-' . $product->getId() . $this->getPlaceholderCode();

        // should we use registry in this function?
        $result = $this->registry->registry($key);
        if (!is_array($result)) {
            $result = [];

            if ($orderDisplays = $product->getData('mst_product_display_ids')) { // displays from index (product listing)
                $orderDisplays = explode('|', $orderDisplays);
                $withOrders    = [];

                foreach ($orderDisplays as $od) {
                    $od = explode('-', $od);
                    $withOrders[$od[1]] = $od[0];
                }

                asort($withOrders);

                $ids = implode(',', array_keys($withOrders));

                $result = explode(',', $ids);
            } else { // fallback (product view, widgets, custom collections)
                $placeholder = $this->getPlaceholder($this->getPlaceholderCode());

                if (!$placeholder) { // placeholder deleted but its code used in manual label placing
                    return [];
                }

                $labels      = $this->getLabels(
                    (int)$placeholder->getId(),
                    (int)$this->customerSession->getCustomerGroupId(),
                    $this->context->getStoreManager()->getStore()
                );

                /** @var LabelInterface $label */
                foreach ($labels as $label) {
                    $result = array_merge($result, $label->getDisplayIds($this->getProduct()));
                }
            }

            if (!$result || !count(array_filter($result))) {
                return [];
            }

            $displayIds = $result;

            $result = $this->displayCollectionFactory->create()
                ->addFieldToFilter('display_id', ['in' => $displayIds]);

            $result->getSelect()->order('FIELD(display_id,' . implode(',', $displayIds) . ')');

            $type = $this->getType();

            $fields = [
                $type . '_image',
                $type . '_title',
                $type . '_description',
            ];

            $conditions = [
                ['notnull' => true],
                ['notnull' => true],
                ['notnull' => true],
            ];

            // exclude empty displays to avoid the creation of unnecessary DOM elements
            $result->addFieldToFilter($fields, $conditions);

            foreach ($result as $display) {
                $display->setType($this->getType());
                $this->_prepareDisplay($display);
            }

            $this->registry->unregister($key);
            $this->registry->register($key, $result);
        }

        return $result;
    }

    public function getPlaceholder(string $code): ?PlaceholderInterface
    {
        if (!empty($this->placeholders[$code])) {
            return $this->placeholders[$code];
        }

        $this->placeholders[$code] = $this->placeholderRepository->getByCode($this->getPlaceholderCode());

        return $this->placeholders[$code];
    }

    public function getLabels(int $placeholderId, int $customerGroupId, StoreInterface $store): Collection
    {
        $key = $placeholderId . '_' . $customerGroupId . '_' . $store->getId();

        if (!empty($this->labels[$key])) {
            return $this->labels[$key];
        }

        if ($this->getLabel()) {
            $labels = [$this->getLabel()];
        } else {
            $labelIds = $this->getProduct()->getData('mst_product_label_id');

            $labels = $this->labelCollectionFactory->create()
                ->addActiveFilter()
                ->addCustomerGroupFilter((int)$customerGroupId)
                ->addStoreFilter((int)$store->getId())
                ->addFieldToFilter('placeholder_id', $placeholderId);

            if ($labelIds) {
                $labels->addFieldToFilter(
                        'label_id',
                        ['in' => array_unique(explode(',', (string)$labelIds))]
                    );
            }

            //it will be applied only if more than one labels are in the same position
            $labels->getSelect()->order('sort_order ASC');
        }

        $this->labels[$key] = $labels;

        return $this->labels[$key];
    }

    public function getImageSizeHtml(string $position, ?int $positionCount = null, ?string $type = null): string
    {
        $currentPositionObject      = $this->labelObjectsArray[$position];
        $currentPositionObjectArray = $currentPositionObject->toArray();
        $currentLabelObject         = $currentPositionObject->getData($positionCount);

        $w = $currentLabelObject->getWidth();
        $h = $currentLabelObject->getHeight();

        $baseStyle = 'width: ' . $w . 'px; height: ' . $h . 'px;';
        $halfW     = 'margin-left: ' . -$w / 2 . 'px;';
        $halfH     = 'margin-top: ' . -$h / 2 . 'px;';

        if (in_array($position, ['BL', 'BR', 'BC']) && $this->multipleImages && $type == 'view') {
            $baseStyle .= ' margin-bottom: 115px; ';
        }

        $imageSpace = 10; //space between images if we use more then one image in the same position
        if ($positionCount) {
            $width = [];

            foreach ($currentPositionObjectArray as $positionKey => $positionData) {
                if ($positionKey == $positionCount) {
                    break;
                }

                $width[] = $positionData->getWidth();
            }

            $leftShiftW  = 'margin-left: ' . (($imageSpace * $positionCount) + array_sum($width)) . 'px;';
            $rightShiftW = 'margin-right: ' . (($imageSpace * $positionCount) + array_sum($width)) . 'px;';

            $leftShiftCenterW = '';

            if (in_array($position, ['MC', 'TC', 'BC'])) {
                $widthArray       = $this->_getWidthArray($currentPositionObjectArray);
                $leftShiftCenterW = 'margin-left: ' . (
                        (-(array_sum($widthArray) / 2) - ($imageSpace * (count($widthArray) - 1))) +
                        $widthArray[0] + (array_sum($width) - $widthArray[0]) + ($imageSpace * $positionCount)
                    ) . 'px;';
            }

            switch ($position) {
                case 'MC':
                    return $baseStyle . $halfH . $leftShiftCenterW;
                    break;

                case 'TC':
                    return $baseStyle . $leftShiftCenterW;
                    break;

                case 'BC':
                    return $baseStyle . $leftShiftCenterW;
                    break;

                case 'ML':
                    return $baseStyle . $halfH . $leftShiftW;
                    break;

                case 'MR':
                    return $baseStyle . $halfH . $rightShiftW;
                    break;

                case 'TR':
                    return $baseStyle . $rightShiftW;
                    break;

                case 'BR':
                    return $baseStyle . $rightShiftW;
                    break;

                case 'TL':
                    return $baseStyle . $rightShiftW;
                    break;

                case 'BL':
                    return $baseStyle . $leftShiftW;
                    break;

                default:
                    return $baseStyle;
                    break;
            }
        } else {
            $leftShiftFirstLabelCenterW = '';
            if (count($currentPositionObjectArray) > 1 && in_array($position, ['MC', 'TC', 'BC'])) {
                $widthArray                 = $this->_getWidthArray($currentPositionObjectArray);
                $leftShiftFirstLabelCenterW = 'margin-left: ' .
                    (-(array_sum($widthArray) / 2) - ($imageSpace * (count($widthArray) - 1))) . 'px;';
            }

            switch ($position) {
                case 'MC':
                    if (count($currentPositionObjectArray) > 1) {
                        return $baseStyle . $halfH . $leftShiftFirstLabelCenterW;
                    } else {
                        return $baseStyle . $halfH . $halfW;
                    }
                    break;

                case 'TC':
                case 'BC':
                    if (count($currentPositionObjectArray) > 1) {
                        return $baseStyle . $leftShiftFirstLabelCenterW;
                    } else {
                        return $baseStyle . $halfW;
                    }
                    break;

                case 'MR':
                case 'ML':
                    return $baseStyle . $halfH;
                    break;

                default:
                    return $baseStyle;
                    break;
            }
        }
    }

    protected function _getWidthArray(array $currentPositionObjectArray): array
    {
        $widthArray = [];

        foreach ($currentPositionObjectArray as $positionData) {
            $widthArray[] = $positionData->getWidth();
        }

        return $widthArray;
    }

    protected function _prepareDisplay(Display $display): Display
    {
        $display->setData($this->getType() . '_title', $this->_formatTxt((string)$display->getTitle()));

        $description = str_replace('[br]', '', (string)$display->getDescription());

        $display->setData($this->getType() . '_description', $this->_formatTxt($description));

        return $display;
    }

    protected function _formatTxt(string $txt): string
    {
        $txt = $this->productDataHelper->setProduct($this->getProduct())
            ->processVariables($txt);

        return $this->mstcoreParsevariables->parse($txt, ['product' => $this->getProduct()]);
    }

    /**
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function _getSpecPriceMinusPricePercent(ProductInterface $product): ?float
    {
        $price     = $product->getPrice();
        $specPrice = $product->getSpecialPrice();

        $specPriceMinusPricePercent = 0;
        $specialPriceFromDate       = $product->getSpecialFromDate();
        $specialPriceToDate         = $product->getSpecialToDate();

        $today          = time();
        $inDateInterval = false;

        if ($specPrice) {
            if (
                ($today >= strtotime($specialPriceFromDate) && $today <= strtotime($specialPriceToDate))
                || ($today >= strtotime($specialPriceFromDate) && $specialPriceToDate === null)
            ) {
                $inDateInterval = true;
            }
        }

        if (!$inDateInterval) {
            return null;
        }

        if ($specPrice && $specPrice != 0 && $specPrice < $price) {
            $specPriceMinusPricePercent = 100 - (($specPrice * 100) / $price);
        }

        if ($specPriceMinusPricePercent > 0 && $specPriceMinusPricePercent < 1) {
            $specPriceMinusPricePercent = 1;
        }

        if ($specPriceMinusPricePercent == 0) {
            return null;
        }

        return $specPriceMinusPricePercent;
    }

    protected function _getActualPrice(): ?float
    {
        $product               = $this->getProduct();
        $price                 = $product->getFinalPrice();
        $formattedSpecialPrice = $this->pricingHelper->currency($price, true, false);

        if ($price == 0) {
            return null;
        }

        return $formattedSpecialPrice;
    }

    protected function _getParentCategory(): CategoryInterface
    {
        $category = $this->registry->registry('current_category');

        if ($category) {
            $thisCategory = $category->getName();

            return $thisCategory;
        }
    }

    public function getFullActionCode(): string
    {
        return $this->helperData->getFullActionCode();
    }

    public function isProductList(): bool
    {
        $fullActionCode     = $this->getFullActionCode();
        $productListActions = [
            'catalog_category_view',
            'catalogsearch_result_index',
            'catalogsearch_advanced_result',
            'cms_index_index'
        ];

        return in_array($fullActionCode, $productListActions) ? true : false;
    }
}
