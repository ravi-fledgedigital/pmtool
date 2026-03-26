<?php
declare(strict_types=1);

namespace OnitsukaTiger\EmailTemplate\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class AddCreditMemoVarEmailObserver implements ObserverInterface
{

    /**
     * Observer execute
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        $transport = $observer->getTransport();
        $transportObject = $observer->getData('transportObject');

        /**
         * Model Order
         *
         * @var \Magento\Sales\Model\Order|null $order
         */
        $order = null;
        if (is_object($transport)) {
            $order = $transport->getOrder();
        } elseif (is_array($transport) && array_key_exists('order', $transport)) {
            $order = $transport['order'];
        }

        /**
         * Model Creditmemo
         *
         * @var \Magento\Sales\Model\Order\Creditmemo|null $creditmemo
         */
        $creditmemo = null;
        if (is_object($transport)) {
            $creditmemo = $transport->getCreditMemo();
        } elseif (is_array($transport) && array_key_exists('creditmemo', $transport)) {
            $creditmemo = $transport['creditmemo'];
        }

        $vars = [
            'creditmemo_created_at_formatted' => $this->formatterCreatedAt($creditmemo->getCreatedAt()),
            'order_created_at_formatted' => $order->getCreatedAtFormatted(2),
        ];
        $transportObject->addData($vars);
    }

    /**
     * Formatter Create At
     *
     * @param {string} $date
     * @return string
     */
    public function formatterCreatedAt($date)
    {
        return date("d-M-Y", strtotime($date));
    }
}
