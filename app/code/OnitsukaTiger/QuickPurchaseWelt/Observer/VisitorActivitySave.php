<?php

namespace OnitsukaTiger\QuickPurchaseWelt\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Session\SessionManagerInterface;
use Magento\Framework\Stdlib\Cookie\CookieMetadataFactory;
use Magento\Framework\Stdlib\CookieManagerInterface;

class VisitorActivitySave implements ObserverInterface
{
    /**
     * @var CookieManagerInterface
     */
    protected $cookieManager;

    /**
     * @var CookieMetadataFactory
     */
    protected $cookieMetadataFactory;

    /**
     * @var SessionManagerInterface
     */
    protected $sessionManager;

    /**
     * @param CookieManagerInterface $cookieManager
     * @param CookieMetadataFactory $cookieMetadataFactory
     * @param SessionManagerInterface $sessionManager
     */
    public function __construct(
        CookieManagerInterface $cookieManager,
        CookieMetadataFactory $cookieMetadataFactory,
        SessionManagerInterface $sessionManager
    ) {
        $this->cookieManager = $cookieManager;
        $this->cookieMetadataFactory = $cookieMetadataFactory;
        $this->sessionManager = $sessionManager;
    }

    /**
     *  Execute Function
     *
     * @param Observer $observer
     * @return void
     * @throws \Zend_Log_Exception
     */
    public function execute(Observer $observer)
    {
        $visitorData = $observer->getEvent()->getData('visitor');
        if (!empty($visitorData->getData()) && empty($visitorData->getData('customer_id'))) {
            $cookieMetadata = $this->cookieMetadataFactory
                ->createPublicCookieMetadata()
                ->setPath('/')
                ->setDuration(86400);

            $this->cookieManager->setPublicCookie(
                'visitor_id',
                $this->getVisitorId($visitorData->getData()),
                $cookieMetadata
            );
            $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/visitore_logs.log');
            $logger = new \Zend_Log();
            $logger->addWriter($writer);
            // phpcs:ignore
            $logger->info("Visitor Data" . print_r($visitorData->getData(), true));
        }
    }

    /**
     * Get Visitor ID
     *
     * @param mixed $visitorData
     * @return string
     */
    public function getVisitorId($visitorData)
    {
        $customerId = '';
        if (isset($visitorData['visitor_id'])) {
            $customerId = 'GUEST';
            $customerId .= '_' . $visitorData['visitor_id'];
        }
        return $customerId;
    }
}