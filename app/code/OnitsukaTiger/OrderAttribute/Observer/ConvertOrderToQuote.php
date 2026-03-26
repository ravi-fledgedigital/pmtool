<?php
/**
* @author OnitsukaTiger Team
* @copyright Copyright (c) 2022 OnitsukaTiger (https://www.onitsukatiger.com)
* @package Custom Checkout Fields for Magento 2
*/

namespace OnitsukaTiger\OrderAttribute\Observer;

use Magento\Framework\Event\ObserverInterface;

/**
 * event sales_convert_order_to_quote
 * name ConvertOrderAttributesToQuoteAttributes
 */
class ConvertOrderToQuote implements ObserverInterface
{
    /**
     * @var \Magento\Quote\Api\Data\CartExtensionFactory
     */
    private $cartExtensionFactory;

    /**
     * @var \OnitsukaTiger\OrderAttribute\Model\Entity\Adapter\Order\Adapter
     */
    private $orderAdapter;

    public function __construct(
        \Magento\Quote\Api\Data\CartExtensionFactory $cartExtensionFactory,
        \OnitsukaTiger\OrderAttribute\Model\Entity\Adapter\Order\Adapter $orderAdapter
    ) {
        $this->cartExtensionFactory = $cartExtensionFactory;
        $this->orderAdapter = $orderAdapter;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     *
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /**
         * @var \Magento\Quote\Model\Quote $quote
         * @var \Magento\Sales\Model\Order $order
         */
        $quote = $observer->getEvent()->getQuote();
        $order = $observer->getEvent()->getOrder();

        $orderExtensionAttributes = $order->getExtensionAttributes();
        if (!$orderExtensionAttributes || !$orderExtensionAttributes->getOnitsukaTigerOrderAttributes()) {
            $this->orderAdapter->addExtensionAttributesToOrder($order);
            $orderExtensionAttributes = $order->getExtensionAttributes();
        }
        if ($orderExtensionAttributes->getOnitsukaTigerOrderAttributes()) {
            $customAttributes = $orderExtensionAttributes->getOnitsukaTigerOrderAttributes();
            $quoteExtensionAttributes = $quote->getExtensionAttributes();
            if (empty($quoteExtensionAttributes)) {
                $quoteExtensionAttributes = $this->cartExtensionFactory->create();
            }
            $quoteExtensionAttributes->setOnitsukaTigerOrderAttributes($customAttributes);
            $quote->setExtensionAttributes($quoteExtensionAttributes);
        }
    }
}
