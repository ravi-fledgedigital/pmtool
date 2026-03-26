<?php

namespace OnitsukaTigerKorea\GA4\Plugin;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Framework\Exception\NoSuchEntityException;
use OnitsukaTigerKorea\GA4\Helper\Data as HelperData;
use WeltPixel\GA4\Helper\Data;

class DataPlugin
{
    /**
     * @var ProductRepositoryInterface
     */
    protected ProductRepositoryInterface $productRepository;

    /**
     * @var HelperData
     */
    protected HelperData $helperData;

    /**
     * @var Configurable
     */
    protected Configurable $configurableProduct;

    /**
     * Data constructor.
     * @param ProductRepositoryInterface $productRepository
     * @param HelperData $helperData
     * @param Configurable $configurableProduct
     */
    public function __construct(
        ProductRepositoryInterface $productRepository,
        HelperData                 $helperData,
        Configurable               $configurableProduct
    )
    {
        $this->productRepository = $productRepository;
        $this->helperData = $helperData;
        $this->configurableProduct = $configurableProduct;
    }

    /**
     * @param Data $subject
     * @param $result
     * @return mixed
     * @throws NoSuchEntityException
     */
    public function afterAddToCartPushData(Data $subject, $result): mixed
    {
        if (!$this->helperData->isKoreaWebsite()) {
            return $result;
        }
        return $this->addDataLayerProductDetail($result);
    }

    /**
     * @param Data $subject
     * @param callable $proceed
     * @param $product
     * @param $buyRequest
     * @param $wishlistItem
     * @return array
     * @throws NoSuchEntityException
     */
    public function aroundAddToWishListPushData(Data $subject, callable $proceed, $product, $buyRequest, $wishlistItem): array
    {
        if (!$this->helperData->isKoreaWebsite()) {
            return $proceed($product, $buyRequest, $wishlistItem);
        }

        if (isset($buyRequest['super_attribute'])) {
            $child = $this->getChildProductByAttribute($buyRequest['super_attribute'], $product);
        }

        $result = $proceed($child ?? $product, $buyRequest, $wishlistItem);
        return $this->addDataLayerProductDetail($result);
    }

    /**
     * @param $attribute
     * @param $configurableProduct
     * @return Product|null
     */
    private function getChildProductByAttribute($attribute, $configurableProduct): ?Product
    {
        $child = $this->configurableProduct->getProductByAttributes($attribute, $configurableProduct);
        if ($child) {
            return $child;
        }
        return null;
    }

    /**
     * @param $data
     * @return array
     * @throws NoSuchEntityException
     */
    private function addDataLayerProductDetail($data): array
    {
        foreach ($data['ecommerce']['items'] as $key => &$item) {
            if (isset($item['item_id'])) {
                $product = $this->productRepository->get($item['item_id'], false, $this->helperData->getCurrentStore()->getId());
                $data['ecommerce']['items'][$key]['originalPrice'] = HelperData::formatPrice($product->getPriceInfo()->getPrice('regular_price')->getValue());
                $data['ecommerce']['items'][$key]['baseImage'] = $this->helperData->getImage($product, 'product_page_main_image')->getImageUrl();
                $data['ecommerce']['items'][$key]['googleShopProductType'] = $product->getData('google_shop_product_type');
            }
        }
        return $data;
    }
}
