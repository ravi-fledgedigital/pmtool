<?php

namespace OnitsukaTiger\MaxQty\Observer;

use Magento\Customer\Model\Session;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;

class MaxQtyCheck implements ObserverInterface
{
    /**
     * @var Session
     */
    private Session $_session;

    /**
     * @param Session $session
     * @param \Magento\Quote\Api\CartRepositoryInterface $quoteRepository
     * @param \OnitsukaTiger\MaxQty\Helper\Data $helper
     * @param \Magento\Checkout\Model\Cart $cart
     */
    public function __construct(
        Session $session,
        private \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        private \OnitsukaTiger\MaxQty\Helper\Data $helper,
        private \Magento\Quote\Model\QuoteFactory $quoteFactory,
        private \Magento\Checkout\Model\Cart $cart
    ) {
        $this->_session = $session;
    }

    /**  
     * Observer to check the cart item qty before updating the cart.
     *
     * @param Observer $observer
     * @throws LocalizedException
     */
    public function execute(Observer $observer)
    {
        if ($this->helper->isModuleEnabled()) {
            $requestInfo = $observer->getInfo();
            $product = $observer->getProduct();
            if ($product->getIsValidateMaxQty()) {
                $maxQty = (int)$this->helper->getMaxAllowedQty();

                if ($requestInfo['qty'] > $maxQty) {
                    throw new LocalizedException(__('The requested qty exceeds the maximum qty allowed in shopping cart'));
                }

                $this->checkForMaxQuantity($requestInfo['product'], $requestInfo['qty']);
            }
        }
    }

    /**
     * Check for the max qty and throw exception.
     *
     * @param $parentProductId
     * @param $requestedQty
     * @return void
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function checkForMaxQuantity($parentProductId, $requestedQty)
    {
        $itemCountArray = [];
        if ($this->_session->isLoggedIn()) {
            //$customerQuote = $this->quoteRepository->getForCustomer($this->_session->getCustomer()->getId());
            $customerQuote = $this->quoteFactory->create()->loadByCustomer($this->_session->getCustomer());
            $quoteItems = $customerQuote->getAllVisibleItems();
        } else {
            $quoteItems = $this->cart->getQuote()->getAllVisibleItems();
        }

        foreach ($quoteItems as $item) {
            $product = $item->getProduct();
            if (!$product->getIsValidateMaxQty()) {
                continue;
            }
            if (!array_key_exists($item->getProductId(), $itemCountArray)) {
                $itemCountArray[$item->getProductId()] = $item->getQty();
            } else {
                $itemCountArray[$item->getProductId()] = $item->getQty() + $itemCountArray[$item->getProductId()];
            }
        }
        $maxQty = (int)$this->helper->getMaxAllowedQty();
        $itemQty = (isset($itemCountArray[$parentProductId])) ? $requestedQty + $itemCountArray[$parentProductId] : false;
        if ($itemQty && $itemQty > $maxQty) {
            throw new LocalizedException(__('The requested qty exceeds the maximum qty allowed in shopping cart'));
        }
    }
}
