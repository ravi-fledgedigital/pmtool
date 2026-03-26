<?php

namespace Seoulwebdesign\Toast\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Seoulwebdesign\Toast\Helper\Data;

class SendCustomMessage implements ObserverInterface
{
    /**
     * @var Data
     */
    protected $_helper;

    /**
     * SendOrderRefundMessage constructor.
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
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(Observer $observer)
    {

        /**
         * How to use
         * @var $eventManager \Magento\Framework\Event\ManagerInterface
         */
        /*
        $eventManager->dispatch(
            'toast_send_custom_message',
            ['toastMessageId' => $id, 'phone' => $phone, 'params'=>$params]
        );
        /*/

        if ($this->_helper->getIsEnabled()) {
            $toastMessageId = $observer->getEvent()->getToastMessageId();
            $phone = $observer->getEvent()->getPhone();
            $params = $observer->getEvent()->getParams();

            $this->_helper->sendRawMessage($phone, $toastMessageId, $params);
        }
    }
}
