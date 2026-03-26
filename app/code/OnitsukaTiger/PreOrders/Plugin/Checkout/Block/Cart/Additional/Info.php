<?php

namespace OnitsukaTiger\PreOrders\Plugin\Checkout\Block\Cart\Additional;

use OnitsukaTiger\PreOrders\Helper\Source\RegistryNameInterface;
use Magento\Framework\Registry;
use Magento\Checkout\Block\Cart\Additional\Info as CheckoutCartAdditionalInfoBlock;
use Magento\Quote\Model\Quote\Item as QuoteItem;
use OnitsukaTiger\PreOrders\Helper\PreOrder;
use OnitsukaTiger\PreOrders\Helper\Data;
use Magento\Store\Model\StoreManagerInterface;

class Info
{
    /**
     * @var Registry
     */
    private $registry;

    /**
     * @var PreOrder
     */
    protected $preOrder;

    /**
     * @var Data
     */
    protected $helperIsModuleEnable;

      /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * Info constructor.
     *
     * @param Data $helperIsModuleEnable
     * @param Registry $registry
     * @param PreOrder $preOrder
     */
    public function __construct(
        Registry $registry,
        Data $helperIsModuleEnable,
        StoreManagerInterface $storeManager,
        PreOrder $preOrder
    ) {
        $this->registry = $registry;
        $this->helperIsModuleEnable = $helperIsModuleEnable;
        $this->storeManager = $storeManager;
        $this->preOrder = $preOrder;
    }

    /**
     * Before set item
     *
     * @param CheckoutCartAdditionalInfoBlock $subject
     * @param QuoteItem $quoteItem
     */
    public function beforeSetItem(
        CheckoutCartAdditionalInfoBlock $subject,
        QuoteItem $quoteItem
    ) {
        if ($this->registry->registry(RegistryNameInterface::CURRENT_QUOTE_ITEM)) {
            $this->registry->unregister(RegistryNameInterface::CURRENT_QUOTE_ITEM);
        }
        $this->registry->register(RegistryNameInterface::CURRENT_QUOTE_ITEM, $quoteItem);
    }

    /**
     * After tohtml
     *
     * @param CheckoutCartAdditionalInfoBlock $subject
     * @param string $result
     * @return mixed|string
     */
    public function afterToHtml(
        CheckoutCartAdditionalInfoBlock $subject,
        $result
    ) {
        $storeId = $this->preOrder->getStoreId();
        // echo "storeId--->".$storeId;exit;
        if($this->helperIsModuleEnable->isModuleEnabled($storeId)){
            $quoteItem = $this->registry->registry(RegistryNameInterface::CURRENT_QUOTE_ITEM);
            $isPreOrder = $this->preOrder->isQuoteItemPreOrder($quoteItem);
            if ($isPreOrder) {
                $result = $result . '<b>'.__('* Pre-order').'</b>';
            }
        }
        return $result;
    }
}
