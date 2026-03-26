<?php
namespace OnitsukaTigerKorea\Sales\Plugin;

use Magento\Framework\Event\ManagerInterface;
use Magento\Sales\Model\Order;

class IgnoreEmailCancel {

    /**
     * @var ManagerInterface
     */
    protected $eventManager;

    public function __construct(
        ManagerInterface $eventManager
    ) {
        $this->eventManager = $eventManager;
    }

    /**
     * @param Order $subject
     * @param callable $proceed
     * @return callable|Order
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function aroundCancel(Order $subject, callable $proceed)
    {
        if ($subject->getState() == $subject::STATE_NEW || $subject->getState() == $subject::STATE_PENDING_PAYMENT) {
            if ($subject->canCancel()) {
                $subject->getPayment()->cancel();
                $subject->registerCancellation();

                $this->eventManager->dispatch('order_cancel_after', ['order' => $subject, 'not_send_cancel_mail' => true]);
            }

            return $subject;
        } else {
            return $proceed();
        }

    }
}
