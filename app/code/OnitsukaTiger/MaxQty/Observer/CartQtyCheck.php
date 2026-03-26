<?php

namespace OnitsukaTiger\MaxQty\Observer;

use Magento\Customer\Model\Session;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;

class CartQtyCheck implements ObserverInterface
{
    /**
     * @var Session
     */
    private Session $_session;

    /**
     * @param Session $session
     * @param \OnitsukaTiger\MaxQty\Helper\Data $helper
     * @param \Magento\Quote\Api\CartRepositoryInterface $quoteRepository
     */
    public function __construct(
        Session $session,
        private \OnitsukaTiger\MaxQty\Helper\Data $helper,
        private \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        private \Magento\Quote\Model\QuoteFactory $quoteFactory,
        private \Magento\Framework\App\Request\Http $request
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
            $cart = $observer->getCart();
            $requestInfo = $observer->getInfo();
            $items = $cart->getQuote()->getAllVisibleItems();
            $maxQty = (int)$this->helper->getMaxAllowedQty();

            if ($requestInfo) {
                foreach ($items as $item) {
                    $product = $item->getProduct();
                    if (isset($requestInfo[$item->getId()]) && $product->getIsValidateMaxQty()) {
                        if ($requestInfo[$item->getId()]['qty'] > $maxQty) {
                            throw new LocalizedException(__('The requested qty exceeds the maximum qty allowed in shopping cart'));
                        } else {
                            $this->checkForMaxQuantity($item->getProductId(), $cart, $requestInfo[$item->getId()]['qty'], $maxQty, $item->getId(), $item->getQty(), $item->getSku());
                        }
                    }
                }
            }
        }
    }

    /**
     * Check for the max qty and throw exception.
     *
     * @param $parentProductId
     * @param $cart
     * @param $requestedQty
     * @param $maxQty
     * @param $itemId
     * @param $originalQty
     * @param $requestSku
     * @return void
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function checkForMaxQuantity($parentProductId, $cart, $requestedQty, $maxQty, $itemId, $originalQty, $requestSku)
    {
        $itemCountArray = [];

        if ($this->_session->isLoggedIn()) {
            $customerQuote = $this->quoteFactory->create()->loadByCustomer($this->_session->getCustomer());
            $quoteItems = $customerQuote->getAllVisibleItems();
        } else {
            $quoteItems = $cart->getQuote()->getAllVisibleItems();
        }

        $fullActionName = $this->request->getFullActionName();

        foreach ($quoteItems as $item) {
            $product = $item->getProduct();
            if (!$product->getIsValidateMaxQty()) {
                continue;
            }

            $productId = $item->getProductId();

            if ($fullActionName == "checkout_cart_updatePost" && $productId == $parentProductId) {
                $itemCountArray[$productId] = $requestedQty;
            } else {
                if (!isset($itemCountArray[$productId])) {
                    $itemCountArray[$productId] = $item->getQty();
                } else {
                    $itemCountArray[$productId] += $item->getQty();
                }
            }
        }

        $itemQty = $itemCountArray[$parentProductId] ?? 0;

        if ($itemQty > $maxQty) {
            throw new LocalizedException(__('The requested qty exceeds the maximum qty allowed in shopping cart'));
        }
    }
}
