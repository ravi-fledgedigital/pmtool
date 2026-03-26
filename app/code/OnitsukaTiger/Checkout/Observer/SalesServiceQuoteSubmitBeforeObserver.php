<?php
namespace OnitsukaTiger\Checkout\Observer;

use Magento\Framework\Event\ObserverInterface;
use OnitsukaTiger\Fixture\Helper\Data;

/**
 * Verify product qty limit sales
 */
class SalesServiceQuoteSubmitBeforeObserver implements ObserverInterface
{
    private Data $helperData;

    private \Magento\Framework\TranslateInterface $translate;

    public function __construct(
        Data            $helperData,
        \Magento\Framework\TranslateInterface $translate,
        private \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
    ) {
        $this->helperData = $helperData;
        $this->translate = $translate;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if ($this->helperData->getConfig('catalog/them_customize/qty_validate_sales')) {
            $quote = $observer->getEvent()->getQuote();
            $result = $this->validateQuote($quote);
            if ($result) {
                $message = '';
                foreach ($result as $error) {
                    if ($error && !$message) {
                        $messageText = 'Only %1 Left Can Purchase';
                        if (array_key_exists('ValidateSalesLimit', $this->translate->getData())) {
                            $messageText = $this->translate->getData()['ValidateSalesLimit'];
                        }
                        $message .= __(
                            $messageText,
                            $error['qty']
                        )->render();
                        break;
                    }
                }
                if ($message) {
                    throw new \Magento\Framework\Exception\LocalizedException(
                        __($message)
                    );
                }
            }
        }
    }

    /**
     * @param $quote
     * @return array
     */
    public function validateQuote($quote)
    {
        $return = [];
        foreach ($quote->getAllItems() as $item) {
            $qtyQuoteItems[$item->getItemId()] = $item->getQty();
            if ($item->getParentItemId() != null) {
                try {
                    $product = $this->productRepository->getById($item->getProductId());
                    if ($product && $product->getId() && $product->getMaxSaleQty()) {
                        $maxQtySales = (int)$product->getMaxSaleQty();
                        $qty = $maxQtySales - (int)$qtyQuoteItems[$item->getParentItemId()];
                        if ($qty < 0) {
                            $return[] = [
                                'qty'=> $maxQtySales,
                                'productName'=> $product->getName(),
                            ];
                        }
                    }
                } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
                }
            }
        }
        return $return;
    }
}