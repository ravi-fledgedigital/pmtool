<?php
namespace OnitsukaTiger\Worldpay\Model\Order;

class Service extends \Sapient\Worldpay\Model\Order\Service
{
    /**
     * Get order by id
     *
     * @param int $orderId
     * @return \Sapient\Worldpay\Model\Order
     */
    public function getById($orderId)
    {
        $this->mageorder->reset();
        return parent::getById($orderId);
    }

    /**
     * If order is success send email and mark order as processing
     */
    public function redirectOrderSuccess()
    {
        $order = $this->getAuthorisedOrder();
        if ($order) {
            $magentoorder = $order->getOrder();
            $this->removeAuthorisedOrder();
            $this->emailsender->send($magentoorder);
        }
    }

}
