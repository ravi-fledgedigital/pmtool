<?php

namespace OnitsukaTiger\Checkout\Controller\Cart;

use Magento\Checkout\Model\Cart as CustomerCart;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Data\Form\FormKey\Validator;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

class Delete extends \Magento\Checkout\Controller\Cart\Delete
{
    public LoggerInterface $logger;
    public UrlInterface $url;

    public function __construct(
        Context $context,
        ScopeConfigInterface $scopeConfig,
        Session $checkoutSession,
        StoreManagerInterface $storeManager,
        Validator $formKeyValidator,
        CustomerCart $cart,
        LoggerInterface $logger,
        UrlInterface $url
    ) {
        $this->logger = $logger;
        $this->url = $url;
        parent::__construct($context, $scopeConfig, $checkoutSession, $storeManager, $formKeyValidator, $cart);
    }

    public function execute()
    {
        $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/shipping_cart.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);

        $logger->info("============== Start Deletion Process ===============");
        if (!$this->_formKeyValidator->validate($this->getRequest())) {
            $logger->info('Form key validation failed.');
            $redirectResult = $this->resultRedirectFactory->create()->setPath('*/*/');
            $logger->info('Redirecting due to form key failure.' . $redirectResult);
            return $redirectResult;
        }
        $id = (int)$this->getRequest()->getParam('id');
        $logger->info("Requested item ID for deletion: " . $id);

        if ($id) {
            try {
                $logger->info("Attempting to remove item ID from cart.");

                $this->cart->removeItem($id);
                $this->cart->getQuote()->setTotalsCollectedFlag(false);
                $this->cart->save();

                $logger->info("Item ID removed successfully. Cart saved.");
            } catch (\Exception $e) {
                $logger->info("Error while removing item ID : " . $e->getMessage());

                $this->messageManager->addErrorMessage(__('We can\'t remove the item.'));
                $this->logger->critical($e);
            }
        } else {
            $logger->info('No item ID provided in request.');
        }
        $defaultUrl = $this->url->getUrl() . 'checkout/cart';
        $logger->info("Default URL : " . $defaultUrl);
        $redirectUrl = $this->_redirect->getRedirectUrl($defaultUrl);

        $logger->info("Redirecting user to : " . $redirectUrl);
        $logger->info("============== End Deletion Process ===============");
    
        return $this->resultRedirectFactory->create()->setUrl($defaultUrl);
    }
}
