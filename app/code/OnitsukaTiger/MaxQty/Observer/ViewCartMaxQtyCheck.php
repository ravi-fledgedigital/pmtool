<?php

namespace OnitsukaTiger\MaxQty\Observer;

use Magento\Customer\Model\Session;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;

class ViewCartMaxQtyCheck implements ObserverInterface
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
        private \Magento\Checkout\Model\Cart $cart,
        private \Magento\Framework\Message\ManagerInterface $messageManager,
        private \Magento\Framework\UrlInterface $urlInterface,
        private \Magento\Catalog\Model\ProductRepository $productRepository
    ) {
        $this->_session = $session;
    }

    /**
     * @param Observer $observer
     * @return void
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if ($this->helper->isModuleEnabled()) {
            $fullActionName = $observer->getEvent()->getRequest()->getFullActionName();
            if ($this->_session->isLoggedIn()) {
                //$customerQuote = $this->quoteRepository->getForCustomer($this->_session->getCustomer()->getId());
                $customerQuote = $this->quoteFactory->create()->loadByCustomer($this->_session->getCustomer());
                $quoteItems = $customerQuote->getAllItems();
            } else {
                $quoteItems = $this->cart->getQuote()->getAllItems();
            }
            $maxQty = (int)$this->helper->getMaxAllowedQty();
            $itemCountArray = [];
            $redirectUrl = $this->urlInterface->getUrl('checkout/cart');
            foreach ($quoteItems as $item) {
                $product = $item->getProduct();
                if ($product->getTypeId() == 'configurable' && $product->getIsValidateMaxQty()) {
                    if ($item->getQty() > $maxQty) {
                        $this->messageManager->addError(__('The requested qty exceeds the maximum qty allowed in shopping cart'));
                        if ($fullActionName == 'checkout_index_index') {
                            $observer->getControllerAction()
                                ->getResponse()
                                ->setRedirect($redirectUrl);
                        }
                        break;
                    } else {
                        if (!array_key_exists($item->getProductId(), $itemCountArray)) {
                            $itemCountArray[$item->getProductId()] = $item->getQty();
                        } else {
                            $itemCountArray[$item->getProductId()] = $item->getQty() + $itemCountArray[$item->getProductId()];
                        }
                    }
                }
                $product = $this->productRepository->getById($item->getProductId());
                if ($product->getForceOosToggle()) {
                    $this->messageManager->addError(__('Some of the products in your shopping cart are out of stock. Please update your cart and try again.'));
                    if ($fullActionName == 'checkout_index_index') {
                        $observer->getControllerAction()
                            ->getResponse()
                            ->setRedirect($redirectUrl);
                    }
                    break;
                }
            }
            if (!empty($itemCountArray)) {
                foreach ($itemCountArray as $qty) {
                    if ($qty > $maxQty) {
                        $this->messageManager->addError(__('The requested qty exceeds the maximum qty allowed in shopping cart'));
                        if ($fullActionName == 'checkout_index_index') {
                            $observer->getControllerAction()
                                ->getResponse()
                                ->setRedirect($redirectUrl);
                        }
                        break;
                    }
                }
            }
        }
    }
}
