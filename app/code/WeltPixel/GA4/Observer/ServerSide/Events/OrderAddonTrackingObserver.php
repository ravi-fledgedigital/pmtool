<?php
namespace WeltPixel\GA4\Observer\ServerSide\Events;

use Magento\Framework\Event\ObserverInterface;
use WeltPixel\GA4\Model\OrdersAddonPushedFactory;
use WeltPixel\GA4\Model\OrdersAddonTypesFactory;
use WeltPixel\GA4\Model\AddonValidator;
use Magento\Sales\Model\Order;
use Magento\Framework\Serialize\Serializer\Json;
use WeltPixel\GA4\Model\OrdersPushedPayload;

class OrderAddonTrackingObserver implements ObserverInterface
{
    /**
     * @var OrdersAddonPushedFactory
     */
    protected $ordersAddonPushedFactory;

    /**
     * @var OrdersAddonTypesFactory
     */
    protected $ordersAddonTypesFactory;

    /**
     * @var AddonValidator
     */
    protected $addonValidator;

    /**
     * @var \WeltPixel\GA4\Logger\Logger
     */
    protected $logger;

    /**
     * @var Json
     */
    protected $json;

    /** @var OrdersPushedPayload */
    protected $ordersPushedPayload;

    /**
     * @var \WeltPixel\GA4\Helper\ServerSideTracking
     */
    protected $ga4Helper;

    /** @var \WeltPixel\GA4\Api\ServerSide\Events\PurchaseBuilderInterface */
    protected $purchaseBuilder;



    /**
     * @param OrdersAddonPushedFactory $ordersAddonPushedFactory
     * @param OrdersAddonTypesFactory $ordersAddonTypesFactory
     * @param AddonValidator $addonValidator
     * @param \WeltPixel\GA4\Logger\Logger $logger
     * @param \WeltPixel\GA4\Helper\ServerSideTracking $ga4Helper
     * @param \WeltPixel\GA4\Api\ServerSide\Events\PurchaseBuilderInterface $purchaseBuilder
     * @param Json $json
     * @param OrdersPushedPayload $ordersPushedPayload
     */
    public function __construct(
        OrdersAddonPushedFactory $ordersAddonPushedFactory,
        OrdersAddonTypesFactory $ordersAddonTypesFactory,
        AddonValidator $addonValidator,
        \WeltPixel\GA4\Logger\Logger $logger,
        \WeltPixel\GA4\Helper\ServerSideTracking $ga4Helper,
        \WeltPixel\GA4\Api\ServerSide\Events\PurchaseBuilderInterface $purchaseBuilder,
        Json $json,
        OrdersPushedPayload $ordersPushedPayload
    ) {
        $this->ordersAddonPushedFactory = $ordersAddonPushedFactory;
        $this->ordersAddonTypesFactory = $ordersAddonTypesFactory;
        $this->addonValidator = $addonValidator;
        $this->logger = $logger;
        $this->ga4Helper = $ga4Helper;
        $this->purchaseBuilder = $purchaseBuilder;
        $this->json = $json;
        $this->ordersPushedPayload = $ordersPushedPayload;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @return self
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        try {
            /** @var Order $order */
            $order = $observer->getEvent()->getOrder();
            if (!$order) {
                return $this;
            }

            // Only proceed if this is a new order
            if (!$this->isNewOrder($order)) {
                return $this;
            }

            $orderId = $order->getId();
            if (!$orderId) {
                return $this;
            }

            $this->orderPushPayloadGA4Save($order);

            // Get enabled addons
            $enabledAddons = $this->addonValidator->getEnabledAddons($order->getStoreId());
            if (empty($enabledAddons)) {
                return $this;
            }

            // Create the main order tracking entry
            $ordersAddonPushed = $this->ordersAddonPushedFactory->create();
            $ordersAddonPushed->setData([
                'order_id' => $orderId,
                'created_at' => $order->getCreatedAt()
            ]);
            $ordersAddonPushed->save();

            // Create entries for each enabled addon
            foreach ($enabledAddons as $addonHelper) {
                try {
                    $addonType = $addonHelper::ADDON_TYPE_NAME;
                    $ordersAddonTypes = $this->ordersAddonTypesFactory->create();
                    $ordersAddonTypes->setData([
                        'pushed_id' => $ordersAddonPushed->getId(),
                        'addon_type' => $addonType,
                        'is_pushed' => 0
                    ]);
                    $ordersAddonTypes->save();
                } catch (\Exception $e) {
                    $this->logger->error('GA4 Error: Failed to process addon type. Error: ' . $e->getMessage());
                    continue;
                }
            }
        } catch (\Exception $e) {
            $this->logger->error('GA4 Error: Failed to process order tracking. Error: ' . $e->getMessage());
        }

        return $this;
    }

    /**
     * Check if the order is being created for the first time
     * @param Order $order
     * @return bool
     */
    protected function isNewOrder($order)
    {
        // Check if the order is new
        if ($order->isObjectNew()) {
            return true;
        }

        // Additional check: if the order was just created (state change to 'new')
        $originalData = $order->getOrigData();
        if (empty($originalData) && $order->getState() === Order::STATE_NEW) {
            return true;
        }

        // Check if this is the first time the order is getting its ID
        if (!$order->getOrigData('entity_id') && $order->getId()) {
            return true;
        }

        return false;
    }

    public function orderPushPayloadGA4Save($order)
    {
        if  ($this->ga4Helper->shouldEventBeTracked(\WeltPixel\GA4\Model\Config\Source\ServerSide\TrackingEvents::EVENT_PURCHASE)) {
            if ($order && $this->isFreeOrderTrackingAllowedForGoogleAnalytics($order)) {
                $purchaseEvent = $this->purchaseBuilder->getPurchaseEvent($order);
                $purchaseParams = $purchaseEvent->getParams($this->ga4Helper->getDebugCollectEnabled());
                $payload = $this->json->serialize($purchaseParams);

                // Save payload to weltpixel_ga4_orders_pushed_payload table
                try {
                    $this->ordersPushedPayload->setData([
                        'order_id' => $order->getId(),
                        'order_payload' => $payload
                    ]);
                    $this->ordersPushedPayload->save();
                } catch (\Exception $e) {
                }
            }
        }
    }

    /**
     * @return bool
     */
    protected function isFreeOrderTrackingAllowedForGoogleAnalytics($order)
    {
        $excludeFreeOrder = $this->ga4Helper->excludeFreeOrderFromPurchaseForGoogleAnalytics();
        return $this->isFreeOrderAllowed($order, $excludeFreeOrder);
    }

    /**
     * @param $order
     * @param bool $excludeFreeOrder
     * @return bool
     */
    protected function isFreeOrderAllowed($order, $excludeFreeOrder)
    {
        if (!$excludeFreeOrder) {
            return true;
        }

        $orderTotal = $order->getGrandtotal();
        if ($orderTotal > 0) {
            return true;
        }

        return false;
    }
}
