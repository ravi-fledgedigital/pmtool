<?php

namespace Seoulwebdesign\Toast\Observer;

use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Seoulwebdesign\Toast\Helper\Data;
use Seoulwebdesign\Toast\Model\Message;

class SendCustomerRegisterMessage implements ObserverInterface
{
    /**
     * @var Data
     */
    protected $_helper;

    /**
     * The constructor
     *
     * @param Data $helper
     */
    public function __construct(Data $helper)
    {
        $this->_helper = $helper;
    }

    /**
     * Execute observer
     *
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        if ($this->_helper->getIsEnabled()) {
            /** @var $customer CustomerInterface */
            $customer = $observer->getEvent()->getCustomer();
            $this->_helper->sendMessage(Message::CUSTOMER_REGISTERED, [
                'customer' => $customer, 'storeId' => $customer->getStoreId()
            ]);
        }
    }
}
