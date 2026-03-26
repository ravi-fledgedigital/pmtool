<?php
/**
 * Copyright © Adobe. All rights reserved.
 */
namespace OnitsukaTiger\PreOrders\Plugin\Checkout\Model;

use OnitsukaTiger\PreOrders\Api\Data\Source\CartTypeInterface;
use OnitsukaTiger\PreOrders\Api\Data\Source\RegistryNameInterface;
use Magento\Framework\Registry;
use Magento\Quote\Model\Quote as QuoteModel;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Catalog\Model\Product as ProductModel;
use Magento\Catalog\Model\Product\Type\AbstractType;
use OnitsukaTiger\PreOrders\Helper\PreOrder;

class Quote
{
    /**
     * @var Registry
     */
    private $registry;

    /**
     * @var CheckoutSession
     */
    private $checkoutSession;

    /**
     * @var Stock
     */
    protected $stock;

    /**
     * @var PreOrder
     */
    protected $perOrderHelper;

    /**
     * Quote constructor.
     *
     * @param Registry $registry
     * @param CheckoutSession $checkoutSession
     * @param PreOrder $perOrderHelper
     */
    public function __construct(
        Registry $registry,
        CheckoutSession $checkoutSession,
        PreOrder $perOrderHelper
    ) {
        $this->registry = $registry;
        $this->checkoutSession = $checkoutSession;
        $this->perOrderHelper = $perOrderHelper;
    }

    /**
     * Before method add product
     *
     * @param QuoteModel $subject
     * @param ProductModel $product
     * @param null $request
     * @param string $processMode
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function beforeAddProduct(
        QuoteModel $subject,
        ProductModel $product,
        $request = null,
        $processMode = AbstractType::PROCESS_MODE_FULL
    ) {
        if (!$this->registry->registry(RegistryNameInterface::CURRENT_CART_TYPE)) {
            $this->registry->register(RegistryNameInterface::CURRENT_CART_TYPE, $this->getCurrentCartType());
        }
    }

    /**
     * Get current cart type
     *
     * @return int
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function getCurrentCartType()
    {
        $quoteItems = $this->checkoutSession->getQuote()->getAllVisibleItems();
        $regularFlag = false;
        $preOrderFlag = false;
        if (count($quoteItems) === 0) {
            return CartTypeInterface::TYPE_EMPTY;
        }

        foreach ($quoteItems as $quoteItem) {
            if ($this->perOrderHelper->isQuoteItemPreOrder($quoteItem)) {
                $preOrderFlag = true;
            } else {
                $regularFlag = true;
            }
        }

        switch (true) {
            case ($regularFlag && $preOrderFlag):
                return CartTypeInterface::TYPE_MIXED;
            case ($preOrderFlag):
                return CartTypeInterface::TYPE_PRE_ORDER;
            default:
                return CartTypeInterface::TYPE_REGULAR;
        }
    }
}
