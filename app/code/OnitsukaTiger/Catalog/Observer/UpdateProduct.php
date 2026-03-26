<?php

namespace OnitsukaTiger\Catalog\Observer;

use Magento\Catalog\Api\Data\ProductInterfaceFactory;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\DataObject;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use OnitsukaTiger\Fixture\Helper\Data;

class UpdateProduct implements ObserverInterface
{
    private Data $helperData;

    private ProductInterfaceFactory $productFactory;

    private CheckoutSession $checkoutSession;

    public function __construct(
        ProductInterfaceFactory $productFactory,
        CheckoutSession $checkoutSession,
        Data            $helperData,
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->productFactory = $productFactory;
        $this->helperData = $helperData;
    }

    /**
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function execute(Observer $observer)
    {
        if ($this->helperData->getConfig('catalog/them_customize/qty_validate_sales')) {
            $info = $observer->getInfo();
            $errors = [];
            foreach ($info->getData() as $itemId => $itemInfo) {
                $qty = isset($itemInfo['qty']) ? (double)$itemInfo['qty'] : false;
                if ($qty > 0) {
                    $errors[] = $this->validateProductQtySales($itemId, $qty);
                }
            }
            $message = '';
            if ($errors) {
                foreach ($errors as $error) {
                    if ($error && !$message) {
                        $message .= __(
                            'The Product %1 Only %2 Left Can Purchase',
                            $error['productName'],
                            $error['qty']
                        )->render();
                    }
                }
                if ($message) {
                    throw new \Magento\Framework\Exception\LocalizedException(__($message));
                    return false;
                }
            }
        }
    }

    public function validateProductQtySales($itemId, $qtyRequest): array
    {
        $return = [];
        $qtyQuoteItems = [];
        foreach ($this->checkoutSession->getQuote()->getAllItems() as $item) {
            $qtyQuoteItems[$item->getItemId()] = $item->getQty();
            if ($item->getParentItemId() == $itemId) {
                $product = $this->productFactory->create()->load($item->getProductId());
                if($product->getMaxSaleQty()) {
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
     * @param $requestInfo
     * @return DataObject
     */
    public function getRequest($requestInfo): \Magento\Framework\DataObject
    {
        $request = null;
        if ($requestInfo instanceof \Magento\Framework\DataObject) {
            $request = $requestInfo;
        } elseif (is_numeric($requestInfo)) {
            $request = new \Magento\Framework\DataObject(['qty' => $requestInfo]);
        } elseif (is_array($requestInfo)) {
            $request = new \Magento\Framework\DataObject($requestInfo);
        }
        return $request;
    }
}
