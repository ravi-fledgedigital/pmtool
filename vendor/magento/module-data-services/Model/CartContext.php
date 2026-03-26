<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\DataServices\Model;

use Magento\Catalog\Helper\Product as ProductHelper;
use Magento\Checkout\Helper\Data as CheckoutHelper;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Quote\Model\Cart\CartTotalRepository;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Item;

/**
 * Model for Cart Context
 */
class CartContext implements CartContextInterface
{
    /**
     * @param CheckoutSession $checkoutSession
     * @param ProductHelper $productHelper
     * @param CheckoutHelper $checkoutHelper
     * @param CartTotalRepository $cartTotalRepository
     */
    public function __construct(
        private CheckoutSession $checkoutSession,
        private ProductHelper $productHelper,
        private CheckoutHelper $checkoutHelper,
        private CartTotalRepository $cartTotalRepository,
    ) {}

    /**
     * @inheritDoc
     */
    public function getContextData() : array
    {
        $quote = $this->checkoutSession->getQuote();
        if (!$quote->getId()) {
            return [];
        }
        $items = $this->getCartItems($quote);
        $totals = $this->cartTotalRepository->get($quote->getId());

        $context = [
            'id' => $quote->getId(),
            'totalQuantity' => count($items),
            'prices' => [
                'subtotalExcludingTax' => [
                    'value' => (float) $totals->getSubtotal(),
                ],
                'subtotalIncludingTax' => [
                    'value' => (float) $totals->getSubtotalInclTax(),
                ],
            ],
            'possibleOnepageCheckout' => $this->checkoutHelper->canOnepageCheckout(),
            'giftMessageSelected' => $quote->getGiftMessageId() ? true : false,
            'giftWrappingSelected' => false,
            'items' => $items,
            'discountAmount' => $totals->getDiscountAmount(),
        ];

        return $context;
    }

    /**
     * Get cart items from quote
     *
     * @param Quote $quote
     * @return array
     */
    private function getCartItems(Quote $quote) : array
    {
        $context = [];
        $items = $quote->getAllVisibleItems();

        foreach ($items as $item) {
            $context[] = [
                'id' => $item->getItemId(),
                'formattedPrice' => (float) $item->getBasePrice(),
                'quantity' => $item->getQty(),
                'prices' => [
                    'price' => [
                        'value' => (float) $item->getBasePrice()
                    ]
                ],
                'product' => $this->getItemProductData($item),
                'discountAmount' => $this->priceAsFloat($item->getTotalDiscountAmount()),
            ];
        }

        return $context;
    }

    /**
     * Returns price as a float if price is non-null; returns null, otherwise.
     *
     * @param $price
     * @returns float|null
     */
    private function priceAsFloat($price): ?float
    {
        return is_null($price) ? null : (float) $price;
    }

    /**
     * Get product data for cart item context
     *
     * @param Item $item
     * @return array
     */
    private function getItemProductData(Item $item) : array
    {
        $product = $item->getProduct();
        return [
            'productType' => $item->getProductType(),
            'productId' => $product->getId(),
            'name' => $item->getName(),
            'sku' => $item->getSku(),
            'mainImageUrl' => $this->productHelper->getImageUrl($product),
            'pricing' => [
                'regularPrice' => $this->priceAsFloat($product->getPrice()),
                'minimalPrice' => $this->priceAsFloat($product->getMinimalPrice()),
                'specialPrice' => $this->priceAsFloat($product->getSpecialPrice()),
            ],
        ];
    }
}
