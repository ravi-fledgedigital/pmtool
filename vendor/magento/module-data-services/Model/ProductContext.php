<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\DataServices\Model;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Helper\Product as ProductHelper;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Pricing\Price\FinalPrice;
use Magento\Catalog\Pricing\Price\RegularPrice;
use Magento\Catalog\Pricing\Price\SpecialPrice;
use Magento\Catalog\Pricing\Price\TierPrice;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\Pricing\PriceInfo\Factory as PriceInfoFactory;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Model for Product Context
 */
class ProductContext implements ProductContextInterface
{
    /**
     * @var ProductHelper
     */
    private $productHelper;

    /**
     * @var PriceCurrencyInterface $priceCurrency
     */
    private $priceCurrency;

    /**
     * @var PriceInfoFactory
     */
    private $priceInfoFactory;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @param ProductHelper $productHelper
     * @param PriceCurrencyInterface $priceCurrency
     * @param PriceInfoFactory $priceInfoFactory
     * @param StoreManagerInterface $storeManager
     * @param ProductRepositoryInterface $productRepository
     */
    public function __construct(
        ProductHelper $productHelper,
        PriceCurrencyInterface $priceCurrency,
        PriceInfoFactory $priceInfoFactory,
        StoreManagerInterface $storeManager,
        ProductRepositoryInterface $productRepository
    ) {
        $this->productHelper = $productHelper;
        $this->priceCurrency = $priceCurrency;
        $this->priceInfoFactory = $priceInfoFactory;
        $this->storeManager = $storeManager;
        $this->productRepository = $productRepository;
    }

    /**
     * Returns price as a float if price is non-null; returns null, otherwise.
     *
     * @param $price
     * @returns float|null
     */
    private function priceAsFloat($price): ?float
    {
        return is_null($price) ? null : (float) $this->priceCurrency->convertAndRound(
            $price,
            $this->storeManager->getStore()
        );
    }

    /**
     * @inheritdoc
     */
    public function getContextData(Product $product) : array
    {
        $parentProduct = $this->productRepository->getById($product->getId());
        $hasNoPrice = ($product->getPrice() == 0) && $product->canConfigure();
        $context = [
            'productId' => (int) $product->getId(),
            'name' => $product->getName(),
            'sku' => $product->getSku(),
            'topLevelSku' => $parentProduct->getSku(),
            'specialFromDate' => $product->getSpecialFromDate(),
            'specialToDate' => $product->getSpecialToDate(),
            'newFromDate' => $product->getNewsFromDate(),
            'newToDate' => $product->getNewsToDate(),
            'createdAt' => $product->getCreatedAt(),
            'updatedAt' => $product->getUpdatedAt(),
            'categories' => $product->getCategoryIds(),
            'productType' => $product->getTypeId(),
            'pricing' => $hasNoPrice ? null : [
                'regularPrice' => $this->priceAsFloat($product->getPrice()),
                'minimalPrice' => $this->priceAsFloat($product->getMinimalPrice()),
                'specialPrice' => $this->priceAsFloat($product->getSpecialPrice())
            ],
            'canonicalUrl' => $product->getUrlInStore(),
            'mainImageUrl' => $this->productHelper->getImageUrl($product)
        ];

        return $context;
    }
}
