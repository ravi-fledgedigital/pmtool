<?php

namespace OnitsukaTiger\Omise\Cron;

use Magento\Framework\Stdlib\DateTime\TimezoneInterface;

class UpdateBlankOrderStatus
{
    /**
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Sales\Model\OrderFactory $orderFactory
     */
    public function __construct(
        private \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
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
        $hours = $this->getConfigHours();
        $seconds = $hours * 3600;
        $oneHourAgo = date("Y-m-d H:i:s", (time() - $seconds));
        $orderCollection = $this->orderFactory->create()->getCollection()
            ->addFieldToFilter('state', ['null' => true])
            ->addFieldToFilter('status', ['null' => true])
            ->addFieldToFilter('store_id', ['in' => [3, 4]])
            ->addFieldToFilter('created_at', ['gteq' => $oneHourAgo]);

        if ($orderCollection->getSize() > 0) {
            foreach ($orderCollection as $order) {
                $order->setState('pending_payment');
                $order->setStatus('pending_payment');
                $order->save();
            }
        }
    }

    /**
     * Get config hours.
     *
     * @return mixed
     */
    private function getConfigHours()
    {
        $websiteScope = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE;
        return $this->scopeConfig->getValue("payment/omise/omise_hours_to_update_blank_order_status", $websiteScope, 2);
    }
}
