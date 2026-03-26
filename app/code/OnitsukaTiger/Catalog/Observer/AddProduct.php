<?php

namespace OnitsukaTiger\Catalog\Observer;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\DataObject;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;
use OnitsukaTiger\Fixture\Helper\Data;

class AddProduct implements ObserverInterface
{
    private CheckoutSession $checkoutSession;

    private Data $helperData;

    public function __construct(
        CheckoutSession $checkoutSession,
        Data            $helperData,
        private StoreManagerInterface $storeManager,
        private \Magento\Framework\Message\ManagerInterface $messageManager
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->helperData = $helperData;
    }

    /**
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function execute(Observer $observer)
    {
        $product = $observer->getProduct();
        $isProductComingSoon = 0;
        $currentTime = date('Y-m-d H:i:s');
        $launchDate = $product->getLaunchDate();
        if (!empty($launchDate) && strtotime($launchDate) > strtotime($currentTime)) {
            $isProductComingSoon = 1;
        }
        if ($isProductComingSoon == 1) {
            //$this->messageManager->addErrorMessage(__('You can not add Coming soon product'));
            throw new LocalizedException(__('You can not add Coming soon product'));
        }
        if ($this->helperData->getConfig('catalog/them_customize/qty_validate_sales')) {
            $product = $observer->getProduct();
            $info = $observer->getInfo();
            $request = $this->getRequest($info);
            $cartCandidates = $product->getTypeInstance()->prepareForCartAdvanced($request, $product, 'full');
            $error = -1;
            if ($product) {
                /**
                 * Error message
                 */
                if (is_string($cartCandidates) || $cartCandidates instanceof \Magento\Framework\Phrase) {
                    return (string)$cartCandidates;
                }
                /**
                 * If prepare process return one object
                 */
                if (!is_array($cartCandidates)) {
                    $cartCandidates = [$cartCandidates];
                }
                foreach ($cartCandidates as $candidate) {
                    if ($candidate->getTypeId() == 'simple') {
                        $error = $this->validateProductQtySales($candidate, $info);
                    }
                }
            }
            if ($error) {
                throw new \Magento\Framework\Exception\LocalizedException(__('Only %1 Left Can Purchase', max($error, 0)));
                return false;
            }
        }
    }

    /**
     * @param $product
     * @param $request
     * @return int
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function validateProductQtySales($product, $request): int
    {
        $error = 0;
        $qtyQuoteItems = [];
        foreach ($this->checkoutSession->getQuote()->getAllItems() as $item) {
            $qtyQuoteItems[$item->getItemId()] = $item->getQty();
            if ($item->getProductId() == $product->getEntityId()) {
                $maxQtySales = (int)$product->getMaxSaleQty();
                if ($maxQtySales < (int)$request['qty']) {
                    $error = $maxQtySales;
                } else {
                    $qty = $maxQtySales - (int)$qtyQuoteItems[$item->getParentItemId()];
                    if ($qty > 0) {
                        if ($qty < (int)$request['qty']) {
                            $error = $qty;
                        }
                    } else {
                        $error = -1;
                    }
                }
            }
        }
        if (!$this->checkoutSession->getQuote()->getAllItems() || !$error) {
            if ($product->getMaxSaleQty()) {
                $maxQtySales = (int)$product->getMaxSaleQty();
                if ($maxQtySales < (int)$request['qty']) {
                    $error = $maxQtySales;
                }
            }
        }
        return $error;
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