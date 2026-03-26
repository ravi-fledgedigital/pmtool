<?php

namespace Seoulwebdesign\Kakaopay\Observer\EmailStopper\Order;

use Seoulwebdesign\Kakaopay\Model\Ui\ConfigProvider;

class Email implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var
     */
    private $_current_order;

    /**
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        try {
            $order = $observer->getEvent()->getOrder();
            $this->_current_order = $order;

            $paymentMethod = $order->getPayment()->getMethodInstance()->getCode();

            if (in_array($paymentMethod, [
                ConfigProvider::CODE
            ])) {
                $this->stopNewOrderEmail($order);
            }
        } catch (\ErrorException $ee) {
        } catch (\Exception $ex) {
        }
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     */
    public function stopNewOrderEmail(\Magento\Sales\Model\Order $order)
    {
        $order->setCanSendNewEmailFlag(false);
        $order->setSendEmail(false);
        try {
            $order->save();
        } catch (\ErrorException $ee) {
        } catch (\Exception $ex) {
        }
    }
}
