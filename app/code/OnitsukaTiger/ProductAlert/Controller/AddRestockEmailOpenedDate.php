<?php

namespace OnitsukaTiger\ProductAlert\Controller;

use Magento\Framework\Event\ObserverInterface;
use \Magento\Framework\Event\Observer;

class AddRestockEmailOpenedDate implements ObserverInterface
{
    protected $_storeManager;

    /**
     * AddRestockEmailOpenedDate constructor.
     */
    public function __construct(
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        protected \Magento\ProductAlert\Model\StockFactory $stockFactory,
        protected \Magento\Framework\App\RequestInterface $request
    ) {
        $this->_storeManager = $storeManager;
    }

    /**
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        $request = $observer->getEvent()->getRequest();
        $restockFlag = $this->request->getParam('restock', false);
        $productId = $this->request->getParam('pid', false);
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $customerSession = $objectManager->get('Magento\Customer\Model\Session');
        $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/restockOpenedNote.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);
        $logger->info('==========================Restock Product Opened Log Start============================');
        $logger->info('RestockFlag: ' . $restockFlag);
        $logger->info('Product ID: ' . $productId);
        $logger->info('Store ID:' . $this->_storeManager->getStore()->getId());
        $logger->info('Customer Session: ' . $customerSession->isLoggedIn());

        if ($restockFlag && $this->_storeManager->getStore()->getId() == 5 && $customerSession->isLoggedIn()) {
            $logger->info('=====Inside If Start=====');
            $customerId = $customerSession->getCustomerId();
            $logger->info('Customer ID: ' . $customerId);
            $collection = $this->stockFactory->create()->getCollection();
            $collection->addFieldToFilter('customer_id', ['eq' => $customerId]);
            $collection->addFieldToFilter('product_id', $productId);
            $collection->addFieldToFilter('store_id', $this->_storeManager->getStore()->getId());
            //$collection->addFieldToFilter('status', 0);
            $logger->info('Product Alert Data: ' . print_r($collection->getData(), true));
            if ($collection && $collection->getSize()) {
                $productAlert = $collection->getFirstItem();
                $openedDate = date('Y-m-d H:i:s');
                $productAlert->setOpenedDate($openedDate);
                $productAlert->setIsOpened(1);
                $productAlert->save();
            }
        }
        $logger->info('==========================Restock Product Opened Log End============================');
    }
}
