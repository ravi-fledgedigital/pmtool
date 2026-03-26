<?php
namespace WeltPixel\GA4\Observer\ServerSide\Events;

use Magento\Framework\Event\ObserverInterface;
use WeltPixel\GA4\Model\OrdersAddonPushed;
use WeltPixel\GA4\Model\OrdersAddonTypes;
use WeltPixel\GA4\Logger\Logger;

class AddonPushObserver implements ObserverInterface
{
    /**
     * @var OrdersAddonPushed
     */
    protected $ordersAddonPushed;

    /**
     * @var OrdersAddonTypes
     */
    protected $ordersAddonTypes;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @param OrdersAddonPushed $ordersAddonPushed
     * @param OrdersAddonTypes $ordersAddonTypes
     * @param Logger $logger
     */
    public function __construct(
        OrdersAddonPushed $ordersAddonPushed,
        OrdersAddonTypes $ordersAddonTypes,
        Logger $logger
    ) {
        $this->ordersAddonPushed = $ordersAddonPushed;
        $this->ordersAddonTypes = $ordersAddonTypes;
        $this->logger = $logger;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @return self
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        try {
            $orderId = $observer->getData('order_id');
            $addonType = $observer->getData('addon_type');

            if (!$orderId || !$addonType) {
                $this->logger->error('GA4 Error: Missing required parameters for addon push update.', [
                    'order_id' => $orderId ?? 'null',
                    'addon_type' => $addonType ?? 'null'
                ]);
                return $this;
            }

            // Find the pushed_id for the order
            $pushedId = $this->ordersAddonPushed->getPushedIdByOrderId($orderId);

            if (!$pushedId) {
                $this->logger->error('GA4 Error: No addon pushed record found for order.', [
                    'order_id' => $orderId
                ]);
                return $this;
            }

            // Update the addon type record
            $this->ordersAddonTypes->updatePushStatus($pushedId, $addonType);

        } catch (\Exception $e) {
            $this->logger->error('GA4 Error: Failed to update addon push status.', [
                'order_id' => $orderId ?? 'unknown',
                'addon_type' => $addonType ?? 'unknown',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }

        return $this;
    }
}
