<?php

namespace OnitsukaTiger\Worldpay\Cron;

use Magento\Framework\Stdlib\DateTime\TimezoneInterface;

class UpdateBlankOrderStatus
{
    /**
     * @param \Magento\Sales\Model\OrderFactory $orderFactory
     */
    public function __construct(
        private \Magento\Sales\Model\OrderFactory $orderFactory
    ) {
    }

    /**
     * Execute method.
     *
     * @return void
     */
    public function execute()
    {
        $seconds = 1 * 3600;
        $oneHourAgo = date("Y-m-d H:i:s", (time() - $seconds));
        $orderCollection = $this->orderFactory->create()->getCollection()
            ->addFieldToFilter('state', ['null' => true])
            ->addFieldToFilter('status', ['null' => true])
            ->addFieldToFilter('store_id', ['in' => [1]])
            ->addFieldToFilter('created_at', ['gteq' => $oneHourAgo]);

        if ($orderCollection->getSize() > 0) {
            foreach ($orderCollection as $order) {
                $order->setState('pending');
                $order->setStatus('pending');
                $order->save();
            }
        }
    }
}
