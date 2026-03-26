<?php

namespace OnitsukaTigerKorea\Search\Block;

use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Framework\View\Element\Template;
use Magento\Store\Model\StoreManagerInterface;
use OnitsukaTigerKorea\Search\Helper\Data;
use Vaimo\OTScene7Integration\Api\Scene7ImageAssetProviderInterface;

class Search extends Template
{
    protected $productCollectionFactory;
    protected $storeManager;
    private Data $dataHelper;
    private \OnitsukaTiger\Catalog\Model\Scene7ImageAssetProvider $imageAssetProvider;
    private Scene7ImageAssetProviderInterface $assetProvider;
    private \Magento\Catalog\Api\ProductRepositoryInterface $productRepository;

    public function __construct(
        Template\Context $context,
        CollectionFactory $productCollectionFactory,
        StoreManagerInterface $storeManager,
        Data $dataHelper,
        \OnitsukaTiger\Catalog\Model\Scene7ImageAssetProvider $imageAssetProvider,
        Scene7ImageAssetProviderInterface $assetProvider,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        array $data = []
    ) {
        $this->productCollectionFactory = $productCollectionFactory;
        $this->storeManager = $storeManager;
        $this->dataHelper = $dataHelper;
        parent::__construct($context, $data);
        $this->imageAssetProvider = $imageAssetProvider;
        $this->assetProvider = $assetProvider;
        $this->productRepository = $productRepository;
    }

    /**
     * Return product name and URL for rotating display.
     */
    public function getRotatingProducts(): array
    {
        if (!$this->dataHelper->isEnabled()) {
            return [];
        }

        $skuList = $this->dataHelper->getConfiguredSkus();

        if (empty($skuList)) {
            return [];
        }

        $products = [];
        $currencyCode = $this->storeManager->getStore()->getCurrentCurrencyCode();
        $currencySymbol = $this->storeManager->getStore()->getCurrentCurrency()->getCurrencySymbol();
        if (!$currencySymbol) {
            $currencySymbol = $currencyCode;
        }
        foreach ($skuList as $sku) {
            try {
                $product = $this->productRepository->get($sku);
                $images = $this->imageAssetProvider->getProductAvailableImages($product);

                $thumb = null;
                foreach ($images as $image) {
                    $thumb = $this->assetProvider->getAssetByFilename($image, 'product_page_image_small')->getUrl();
                    break;
                }
                $price = null;
                if ($product->getTypeId() === \Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE) {
                    /** @var \Magento\ConfigurableProduct\Model\Product\Type\Configurable $configurableType */
                    $configurableType = $product->getTypeInstance();
                    $usedProducts = $configurableType->getUsedProducts($product);

                    $minPrice = null;
                    foreach ($usedProducts as $childProduct) {
                        if (!$childProduct->isSalable()) {
                            continue;
                        }
                        $childPrice = $childProduct->getFinalPrice();
                        if ($minPrice === null || $childPrice < $minPrice) {
                            $minPrice = $childPrice;
                        }
                    }
                    $price = $minPrice ?? $product->getFinalPrice();
                } else {
                    $price = $product->getFinalPrice();
                }
                $formattedPrice = $currencySymbol . number_format($price, 0);
                $products[] = [
                    'name'  => $product->getName(),
                    'url'   => $product->getProductUrl(),
                    'price' => $formattedPrice,
                    'image' => $thumb
                ];
            } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
                $this->_logger->warning("SKU not found: $sku");
                continue;
            } catch (\Exception $e) {
                $this->_logger->error("Error loading SKU $sku: " . $e->getMessage());
                continue;
            }
        }

        return $products;
    }
}
