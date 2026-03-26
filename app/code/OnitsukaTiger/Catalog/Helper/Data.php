<?php

declare(strict_types=1);

namespace OnitsukaTiger\Catalog\Helper;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Eav\Api\AttributeRepositoryInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\InventoryConfigurationApi\Api\Data\StockItemConfigurationInterface;
use Magento\InventoryConfigurationApi\Api\GetStockItemConfigurationInterface;
use Magento\InventoryConfigurationApi\Exception\SkuIsNotAssignedToStockException;
use Magento\InventoryReservationsApi\Model\GetReservationsQuantityInterface;
use Magento\InventorySalesApi\Api\Data\ProductSalableResultInterface;
use Magento\InventorySalesApi\Model\GetStockItemDataInterface;
use Magento\InventorySalesApi\Model\StockByWebsiteIdResolverInterface;
use Magento\Swatches\Model\SwatchAttributesProvider;
use OnitsukaTiger\Catalog\Model\Product;
use OnitsukaTigerKorea\ConfigurableProduct\Helper\Data as ConfigurableProductData;

/**
 * Class Data
 * @package OnitsukaTiger\Catalog\Helper
 */
class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    const ENABLE = 1;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $_storeManager;

    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    private $_productRepository;

    /**
     * @var GetStockItemDataInterface
     */
    private $getStockItemData;

    /**
     * @var GetReservationsQuantityInterface
     */
    private $getReservationsQuantity;

    /**
     * @var GetStockItemConfigurationInterface
     */
    private $getStockItemConfiguration;

    /**
     * @var StockByWebsiteIdResolverInterface
     */
    private $stockByWebsiteId;

    /**
     * @var \Magento\Framework\Json\EncoderInterface
     */
    private $jsonEncoder;

    /**
     * @var SwatchAttributesProvider|null
     */
    private $swatchAttributesProvider;

    /**
     * @var ConfigurableProductData
     */
    private $configurableProductData;

    /**
     * @var null
     */
    private $childProduct;

    private Configurable $configurable;

    private CheckoutSession $checkoutSession;

    /**
     * @var AttributeRepositoryInterface
     */
    private $attributeRepository;

    /**
     * Data constructor.
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param GetStockItemDataInterface $getStockItemData
     * @param GetReservationsQuantityInterface $getReservationsQuantity
     * @param GetStockItemConfigurationInterface $getStockItemConfiguration
     * @param StockByWebsiteIdResolverInterface $stockByWebsiteId
     * @param ConfigurableProductData $configurableProductData
     * @param \Magento\Framework\App\Helper\Context $context
     */
    public function __construct(
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        GetStockItemDataInterface $getStockItemData,
        GetReservationsQuantityInterface $getReservationsQuantity,
        GetStockItemConfigurationInterface $getStockItemConfiguration,
        StockByWebsiteIdResolverInterface $stockByWebsiteId,
        \Magento\Framework\Json\EncoderInterface $jsonEncoder,
        SwatchAttributesProvider  $swatchAttributesProvider,
        ConfigurableProductData $configurableProductData,
        Configurable $configurable,
        CheckoutSession $checkoutSession,
        \Magento\Framework\App\Helper\Context $context,
        AttributeRepositoryInterface $attributeRepository
    ) {
        $this->_storeManager = $storeManager;
        $this->stockByWebsiteId = $stockByWebsiteId;
        $this->_productRepository = $productRepository;
        $this->getStockItemData = $getStockItemData;
        $this->jsonEncoder = $jsonEncoder;
        $this->getReservationsQuantity = $getReservationsQuantity;
        $this->getStockItemConfiguration = $getStockItemConfiguration;
        $this->swatchAttributesProvider = $swatchAttributesProvider;
        $this->configurableProductData = $configurableProductData;
        $this->childProduct = null;
        $this->configurable = $configurable;
        $this->checkoutSession = $checkoutSession;
        $this->attributeRepository = $attributeRepository;
        parent::__construct($context);
    }

    /**
     * @param $product
     * @return null
     */
    private function getChildProduct($product)
    {
        if (!$this->childProduct) {
            return $product->getTypeInstance()->getUsedProductIds($product);
        }
        return $this->childProduct;
    }

    /**
     * @param $product
     * @return array
     */
    protected function getChildProductSize($product): array
    {
        return $this->configurable->getChildrenIds($product->getId());
    }

    public function getJsonProduct($product)
    {
        $products = [];
        if ($product->getTypeId() == Configurable::TYPE_CODE) {
            foreach ($this->getChildProduct($product) as $child) {
                $child = $this->_productRepository->getById($child, true, $this->_storeManager->getStore()->getId());
                $maxQty = $this->getMaxQuantity($child->getSku());
                $products[$child->getId()]['maxQty'] = $maxQty;
                $products[$child->getId()]['maxQtySales'] = $child->getMaxSaleQty();
                $products[$child->getId()]['sku'] = $child->getSku();
            }
        }
        return $this->jsonEncoder->encode($products);
    }

    /**
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function validateProductSalesQty($item, $qty)
    {
        $errors = [];
        $product = $this->_productRepository->getById($item->getProductId(), false, null, true);
        $cartCandidates = $product->getTypeInstance()->prepareForCartAdvanced($item->getBuyRequest(), $product, 'full');
        /**
         * Error message
         */
        if (is_string($cartCandidates) || $cartCandidates instanceof \Magento\Framework\Phrase) {
            return (string)$cartCandidates;
        }
        /**
         * If prepare process return one object
         */
        if (!is_array($cartCandidates)) {
            $cartCandidates = [$cartCandidates];
        }
        foreach ($cartCandidates as $candidate) {
            if ($candidate->getTypeId() == 'simple') {
                $error = $this->validateProductQtySales($candidate, $qty);
                if ($error) {
                    $errors[] = $this->validateProductQtySales($candidate, $qty);
                }
            }
        }
        $message = '';
        if ($errors) {
            foreach ($errors as $error) {
                if ($error) {
                    if ($message) {
                        $message .= '<br>';
                    }
                    $message .= __(
                        'The Product %1 Only %2 Left Can Purchase',
                        $error['productName'],
                        $error['qty']
                    )->render();
                }
            }
            if ($message) {
                throw new \Magento\Framework\Exception\ValidatorException(__($message));
            }
        }
    }

    /**
     * @param $product
     * @param $request
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function validateProductQtySales($product, $qtyRequest): ?array
    {
        $return = null;
        $qtyQuoteItems = [];
        foreach ($this->checkoutSession->getQuote()->getAllItems() as $item) {
            $qtyQuoteItems[$item->getItemId()] = $item->getQty();
            if ($item->getProductId() == $product->getEntityId()) {
                if (!$product->getMaxSaleQty()) {
                    continue;
                }
                $maxQtySales = (int)$product->getMaxSaleQty();
                if ($maxQtySales < (int)$qtyRequest) {
                    $return = [
                        'qty'=> $maxQtySales,
                        'productName'=> $product->getName(),
                    ];
                } else {
                    $qty = $maxQtySales - (int)$qtyQuoteItems[$item->getParentItemId()];
                    if ($qty >= 0) {
                        if ($qty < (int)$qtyRequest) {
                            $return = [
                                'qty'=> $qty,
                                'productName'=> $product->getName(),
                            ];
                        }
                    } else {
                        $return = [
                            'qty'=> 0,
                            'productName'=> $product->getName(),
                        ];
                    }
                }
            }
        }
        if (!$this->checkoutSession->getQuote()->getAllItems() || !$return) {
            if ($product->getMaxSaleQty()) {
                if ($product->getMaxSaleQty()) {
                    $maxQtySales = (int)$product->getMaxSaleQty();
                    if ($maxQtySales < (int)$qtyRequest) {
                        $return = [
                            'qty'=> $maxQtySales,
                            'productName'=> $product->getName(),
                        ];
                    }
                }
            }
        }
        return $return;
    }

    /**
     * @param $sku
     * @return float|ProductSalableResultInterface|mixed
     * @throws LocalizedException
     * @throws SkuIsNotAssignedToStockException
     */
    public function getMaxQuantity($sku)
    {
        $stockId = (int)$this->stockByWebsiteId->execute((int)$this->_storeManager->getStore()->getWebsiteId())->getStockId();
        $stockItemData = $this->getStockItemData->execute($sku, $stockId);
        if (null === $stockItemData) {
            return 0;
        }

        try {
            /** @var StockItemConfigurationInterface $stockItemConfiguration */
            $stockItemConfiguration = $this->getStockItemConfiguration->execute($sku, $stockId);
        } catch (SkuIsNotAssignedToStockException $exception) {
            /*$this->logger->critical($exception);*/
            return 0;
        }

        $qtyWithReservation = $stockItemData[GetStockItemDataInterface::QUANTITY] +
            $this->getReservationsQuantity->execute($sku, $stockId);
        return $qtyWithReservation - $stockItemConfiguration->getMinQty();
    }

    /**
     * @param $sku
     * @return ProductInterface
     * @throws NoSuchEntityException
     */
    public function getProduct($sku)
    {
        return $this->_productRepository->get($sku);
    }

    /**
     * @param $product
     * @return string
     * @throws NoSuchEntityException
     */
    public function getAttributeProduct($product): string
    {
        $products = [];
        if ($product->getTypeId() == Configurable::TYPE_CODE) {
            $attributeSwatch = $this->getSwatchAttribute($product);
            foreach ($this->getChildProductSize($product) as $childIds) {
                foreach ($childIds as $child) {
                    $child = $this->_productRepository->getById($child);
                    $attributeData = null;
                    $sizeForDisplay = $this->configurableProductData->enableShowSizeForDisplay($child->getStoreId());
                    foreach ($attributeSwatch as $attribute) {
                        if ($attribute === Product::COLOR_CODE) {
                            $attribute = $child->getResource()->getAttribute(Product::COLOR);
                            $attributeData[Product::COLOR] = $attribute->getSource()->getOptionText($child->getColor());
                        } elseif ($sizeForDisplay == self::ENABLE && ($attribute === 'size' || $attribute === 'qa_size')) {
                            $attributeData[Product::SIZE_FOR_DISPLAY] = $child->getResource()->getAttribute(Product::SIZE_FOR_DISPLAY)->getFrontend()->getValue($child);
                            $attributeData['size'] = $child->getResource()->getAttribute($attribute)->getFrontend()->getValue($child);
                        } else {
                            $attributeData[$attribute] = $child->getResource()->getAttribute($attribute)->getFrontend()->getValue($child);
                        }
                    }
                    $products[$child->getId()] = $attributeData;
                }
            }
        }
        return $this->jsonEncoder->encode($products);
    }

    public function getDefaultChildColor($product)
    {
        $defaultChildColor = $selectedColor = null;
        if ($product->getTypeId() == Configurable::TYPE_CODE) {
            $attrDefaultColor = $product->getResource()->getAttribute(Product::DEFAULT_CHILD_PRODUCT_COLOR);
            $attrColor = $product->getResource()->getAttribute(Product::COLOR);
            $attrColorCode = $product->getResource()->getAttribute(Product::COLOR_CODE);
            $defaultChildColor = $attrDefaultColor->getSource()->getOptionText($product->getDefaultChildProductColor());
            foreach ($this->getChildProduct($product) as $child) {
                $child = $this->_productRepository->getById($child);
                $childColor = $attrColor->getSource()->getOptionText($child->getColor());
                if ($childColor == $defaultChildColor && !$selectedColor) {
                    $selectedColor = $attrColorCode->getSource()->getOptionText($child->getColorCode());
                }
            }
        }
        return $selectedColor;
    }

    /**
     * @param $product
     * @return array
     */
    public function getSwatchAttribute($product) : array
    {
        $swatchAttributes = [];
        foreach ($this->swatchAttributesProvider->provide($product) as $attributeId => $attributeDataArray) {
            $swatchAttributes[] = $attributeDataArray['attribute_code'];
        }
        return $swatchAttributes;
    }

    /**
     * Get color attribute id value
     *
     * @return int|null
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getColorAttributeId()
    {
        $attribute = $this->attributeRepository->get(\Magento\Catalog\Model\Product::ENTITY, 'color_code');
        return $attribute->getAttributeId();
    }

    /**
     * Get size attribute id value
     *
     * @return int|null
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getSizeAttributeId()
    {
        $attribute = $this->attributeRepository->get(\Magento\Catalog\Model\Product::ENTITY, 'qa_size');
        return $attribute->getAttributeId();
    }

    /**
     * Get store id value
     *
     * @return int
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getStoreId()
    {
        return $this->_storeManager->getStore()->getId();
    }
}
