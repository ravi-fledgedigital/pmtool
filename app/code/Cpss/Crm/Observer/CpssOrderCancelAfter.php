<?php
namespace Cpss\Crm\Observer;

use Cpss\Crm\Model\CpssApiRequest;

class CpssOrderCancelAfter implements \Magento\Framework\Event\ObserverInterface
{
    protected $cpssApiRequest;
    public function __construct(
        CpssApiRequest $cpssApiRequest
    ) {
        $this->cpssApiRequest = $cpssApiRequest;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $order = $observer->getEvent()->getOrder();
        try {
            $usedPoint = $order->getUsedPoint();
            $usedPointRefunded = $order->getUsedPointRefunded();
            // check if credit has points to return, skip if none
            if (empty($usedPoint) || $usedPoint <= 0 || $usedPointRefunded > 0 || !$order->hasInvoices()) {
                return $this;
            }

            $this->cpssApiRequest->addPoint(
                $order->getIncrementId(),
                $order->getCustomerId(),
                $usedPoint
            );
            $order->setUsedPointRefunded($usedPoint);
            $order->save();
        } catch (\Exception $e) {
        }
    }
}
