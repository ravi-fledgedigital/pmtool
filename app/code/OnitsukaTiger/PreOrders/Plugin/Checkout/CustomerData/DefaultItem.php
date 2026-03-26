<?php

namespace OnitsukaTiger\PreOrders\Plugin\Checkout\CustomerData;

use OnitsukaTiger\PreOrders\Helper\Source\RegistryNameInterface;
use OnitsukaTiger\PreOrders\Helper\PreOrder;
use OnitsukaTiger\PreOrders\Helper\Data;
use Magento\Framework\Registry;
use Magento\Checkout\CustomerData\DefaultItem as CheckoutCustomerDataDefaultItem;
use Magento\Quote\Model\Quote\Item as QuoteItem;

class DefaultItem
{
    /**
     * @var PreOrder
     */
    protected $preOrder;

    /**
     * @var Registry
     */
    private $registry;

   /**
     * @var Data
     */
    protected $helperIsModuleEnable;

    /**
     * DefaultItem constructor.
     *
     * @param SettingsHelper $settingsHelper
     * @param Registry $registry
     * @param Stock $stock
     */
    public function __construct(
        Registry $registry,
        Data $helperIsModuleEnable,
        PreOrder $preOrder
    ) {
        $this->registry = $registry;
        $this->helperIsModuleEnable = $helperIsModuleEnable;
        $this->preOrder = $preOrder;
    }

    /**
     * Before get item data
     *
     * @param CheckoutCustomerDataDefaultItem $subject
     * @param QuoteItem $item
     */
    public function beforeGetItemData(
        CheckoutCustomerDataDefaultItem $subject,
        QuoteItem $item
    ) {
        if ($this->registry->registry(RegistryNameInterface::CURRENT_QUOTE_ITEM)) {
            $this->registry->unregister(RegistryNameInterface::CURRENT_QUOTE_ITEM);
        }
        $this->registry->register(RegistryNameInterface::CURRENT_QUOTE_ITEM, $item);
    }

    /**
     * After get item data
     *
     * @param CheckoutCustomerDataDefaultItem $subject
     * @param array $result
     * @return mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function afterGetItemData(
        CheckoutCustomerDataDefaultItem $subject,
        $result
    ) {
        $isPreOrder = false;
        $storeId = $this->preOrder->getStoreId();
        if($this->helperIsModuleEnable->isModuleEnabled($storeId)){
            $quoteItem = $this->registry->registry(RegistryNameInterface::CURRENT_QUOTE_ITEM);
            if ($quoteItem) {
                $isPreOrder = $this->preOrder->isQuoteItemPreOrder($quoteItem);
                if ($quoteItem) {
                    $isPreOrder = $this->preOrder->isQuoteItemPreOrder($quoteItem);
                }
            }
        }
        $result['is_pre_order'] = $isPreOrder;

        return $result;
    }
}
